<?php

require_once __DIR__ . '/../config/database.php';

class Pedido
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Listado de pedidos con mesa, cantidad de items y total.
     *
     * @param string|null $vista 'activos' | 'hoy' | null (todos)
     */
    public function listar(string $vista = 'activos'): array
    {
        $sql = "SELECT
                    p.id,
                    p.estado,
                    p.total,
                    p.fecha_creacion,
                    m.numero AS mesa_numero,
                    u.nombre AS usuario_nombre,
                    (SELECT COUNT(*) FROM detalle_pedidos d WHERE d.pedido_id = p.id) AS items
                FROM pedidos p
                INNER JOIN mesas m ON m.id = p.mesa_id
                INNER JOIN usuarios u ON u.id = p.usuario_id";

        $condiciones = [];
        $parametros  = [];

        if ($vista === 'activos') {
            $condiciones[] = "p.estado IN ('pendiente','preparando','preparado')";
        }

        if ($vista === 'hoy') {
            $condiciones[] = 'DATE(p.fecha_creacion) = CURDATE()';
        }

        if ($condiciones) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY p.fecha_creacion DESC LIMIT 100";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
 * Pedidos activos con el detalle de sus productos.
 *
 * Es lo que necesita la cocina: no solo saber que la Mesa 3 pidió algo,
 * sino qué platos tiene que preparar.
 *
 * El orden es estrictamente por llegada (el más antiguo primero) y NUNCA
 * se reordena al cambiar el estado: una tarjeta se queda en su lugar hasta
 * que se cobra la mesa. Si se ordenara por estado, al marcar "preparando"
 * el pedido se movería y la cocina perdería de vista qué mesa llegó antes.
 */
public function paraCocina(): array
    {
        $pedidos = $this->listar('activos');

        if (!$pedidos) {
            return [];
        }

        // Un solo SELECT con todos los pedidos, en vez de uno por pedido
        $ids = array_column($pedidos, 'id');
        $marcadores = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->conexion->prepare(
            "SELECT d.pedido_id, d.cantidad, pr.nombre
             FROM detalle_pedidos d
             INNER JOIN productos pr ON pr.id = d.producto_id
             WHERE d.pedido_id IN ($marcadores)
             ORDER BY d.id"
        );
        $stmt->execute($ids);

        $porPedido = [];

        foreach ($stmt->fetchAll() as $fila) {
            $porPedido[(int) $fila['pedido_id']][] = [
                'nombre'   => $fila['nombre'],
                'cantidad' => (float) $fila['cantidad']
            ];
        }

        foreach ($pedidos as &$pedido) {
            $pedido['detalle'] = $porPedido[(int) $pedido['id']] ?? [];
        }
        unset($pedido);

        // Orden de llegada: primero el que entró antes. El id desempata
        // cuando dos pedidos tienen la misma marca de tiempo.
        usort($pedidos, static function (array $a, array $b): int {
            $comparacion = strcmp((string) $a['fecha_creacion'], (string) $b['fecha_creacion']);

            return $comparacion !== 0
                ? $comparacion
                : ((int) $a['id'] <=> (int) $b['id']);
        });

        return $pedidos;
    }

    /**
     * Detalle de un pedido con sus productos.
     */
    public function conDetalle(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT p.*, m.numero AS mesa_numero
             FROM pedidos p
             INNER JOIN mesas m ON m.id = p.mesa_id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        $pedido = $stmt->fetch();

        if (!$pedido) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            "SELECT d.cantidad, d.precio_unitario, d.subtotal, pr.nombre
             FROM detalle_pedidos d
             INNER JOIN productos pr ON pr.id = d.producto_id
             WHERE d.pedido_id = :id
             ORDER BY d.id"
        );
        $stmt->execute([':id' => $id]);

        $pedido['items'] = $stmt->fetchAll();

        return $pedido;
    }

    /**
     * Pedidos de una mesa, del más reciente al más antiguo.
     */
    public function porMesa(int $mesaId): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id, estado, total, fecha_creacion
             FROM pedidos
             WHERE mesa_id = :mesa
             ORDER BY fecha_creacion DESC, id DESC"
        );
        $stmt->execute([':mesa' => $mesaId]);

        return $stmt->fetchAll();
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT p.*, m.numero AS mesa_numero
             FROM pedidos p
             INNER JOIN mesas m ON m.id = p.mesa_id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        $pedido = $stmt->fetch();

        return $pedido ?: null;
    }

    /**
     * Reemplaza los productos de un pedido y recalcula el total.
     */
    public function actualizar(int $id, array $items): bool
    {
        $precios = $this->preciosDe($items);

        if (!$precios) {
            return false;
        }

        $total = 0;

        foreach ($precios as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare("SELECT usuario_id, mesa_id FROM pedidos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                $this->conexion->rollBack();

                return false;
            }

            $usuarioId = (int) ($pedido['usuario_id'] ?? 0);

            // Stock: solo se mueve la diferencia entre lo que había y lo nuevo
            $anteriores = $this->cantidadesPorProducto($id);
            $nuevas = [];

            foreach ($precios as $item) {
                $nuevas[$item['id']] = ($nuevas[$item['id']] ?? 0) + (float) $item['cantidad'];
            }

            $todos = array_unique(array_merge(array_keys($anteriores), array_keys($nuevas)));

            foreach ($todos as $idProducto) {
                $delta = ($nuevas[$idProducto] ?? 0) - ($anteriores[$idProducto] ?? 0);

                $error = $this->aplicarDeltaStock(
                    (int) $idProducto,
                    $delta,
                    'Ajuste pedido N° ' . $id,
                    $usuarioId
                );

                if ($error !== null) {
                    $this->conexion->rollBack();

                    return false;
                }
            }

            $stmt = $this->conexion->prepare("DELETE FROM detalle_pedidos WHERE pedido_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->conexion->prepare(
                "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (:pedido_id, :producto_id, :cantidad, :precio, :subtotal)"
            );

            foreach ($precios as $item) {
                $stmt->execute([
                    ':pedido_id'   => $id,
                    ':producto_id' => $item['id'],
                    ':cantidad'    => $item['cantidad'],
                    ':precio'      => $item['precio'],
                    ':subtotal'    => $item['precio'] * $item['cantidad']
                ]);
            }

            $stmt = $this->conexion->prepare(
                "UPDATE pedidos SET total = :total WHERE id = :id"
            );
            $stmt->execute([':total' => $total, ':id' => $id]);

            $this->conexion->commit();

            return true;

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return false;
        }
    }

    /**
     * Elimina un pedido con sus detalles.
     *
     * El stock que se había descontado vuelve al almacén, para que el
     * inventario no se quede corto por un pedido que ya no existe.
     */
    public function eliminar(int $id): bool
    {
        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare("SELECT mesa_id, usuario_id FROM pedidos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                $this->conexion->rollBack();

                return false;
            }

            $usuarioId = (int) ($pedido['usuario_id'] ?? 0);

            foreach ($this->cantidadesPorProducto($id) as $idProducto => $cantidad) {
                $this->aplicarDeltaStock(
                    (int) $idProducto,
                    -$cantidad,
                    'Eliminación pedido N° ' . $id,
                    $usuarioId
                );
            }

            $stmt = $this->conexion->prepare("DELETE FROM ventas WHERE pedido_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->conexion->prepare("DELETE FROM detalle_pedidos WHERE pedido_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->conexion->prepare("DELETE FROM pedidos WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $this->liberarMesaSiProcede((int) $pedido['mesa_id']);

            $this->conexion->commit();

            return true;

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return false;
        }
    }

    /**
     * Cobra un pedido servido: registra la venta y libera la mesa.
     *
     * @param array $pagos [['metodo_pago_id' => int, 'monto' => float], ...]
     *                   Permite pago partido: parte en efectivo, parte con otro método.
     */
    public function cobrar(int $id, int $usuarioId, array $pagos): bool
    {
        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "SELECT mesa_id, estado FROM pedidos WHERE id = :id FOR UPDATE"
            );
            $stmt->execute([':id' => $id]);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                $this->conexion->rollBack();

                return false;
            }

            $stmt = $this->conexion->prepare(
                "SELECT COALESCE(SUM(subtotal), 0) FROM detalle_pedidos WHERE pedido_id = :id"
            );
            $stmt->execute([':id' => $id]);
            $total = round((float) $stmt->fetchColumn(), 2);

            $reparto = $this->repartirPagos($pagos, $total);

            if ($reparto === null) {
                $this->conexion->rollBack();

                return false;
            }

            $stmt = $this->conexion->prepare(
                "INSERT INTO ventas (pedido_id, usuario_id, subtotal, descuento, total, estado)
                 VALUES (:pedido_id, :usuario_id, :subtotal, 0, :total, 'pagada')"
            );
            $stmt->execute([
                ':pedido_id'  => $id,
                ':usuario_id' => $usuarioId,
                ':subtotal'   => $total,
                ':total'      => $total
            ]);

            $ventaId = (int) $this->conexion->lastInsertId();

            $stmt = $this->conexion->prepare(
                "INSERT INTO pagos (venta_id, metodo_pago_id, monto)
                 VALUES (:venta_id, :metodo_id, :monto)"
            );

            foreach ($reparto as $pago) {
                $stmt->execute([
                    ':venta_id'  => $ventaId,
                    ':metodo_id' => $pago['metodo_pago_id'],
                    ':monto'     => $pago['monto']
                ]);
            }

            $stmt = $this->conexion->prepare(
                "UPDATE pedidos SET estado = 'entregado' WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);

            $this->liberarMesaSiProcede((int) $pedido['mesa_id']);

            $this->conexion->commit();

            return true;

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return false;
        }
    }

    /**
     * Valida los pagos recibidos y los ajusta al total.
     * Devuelve null si no cuadran.
     */
    private function repartirPagos(array $pagos, float $total): ?array
    {
        $limpio = [];
        $suma  = 0.0;

        foreach ($pagos as $pago) {
            $metodo = (int) ($pago['metodo_pago_id'] ?? 0);
            $monto  = round((float) ($pago['monto'] ?? 0), 2);

            if ($metodo <= 0 || $monto <= 0) {
                continue;
            }

            $limpio[] = ['metodo_pago_id' => $metodo, 'monto' => $monto];
            $suma += $monto;
        }

        if (!$limpio) {
            return null;
        }

        if (round($suma, 2) > $total + 0.01) {
            return null;
        }

        // Lo que falte se completa en el último método (descuento, propina, etc.)
        $ultimo = array_key_last($limpio);
        $limpio[$ultimo]['monto'] = round($limpio[$ultimo]['monto'] + ($total - $suma), 2);

        return $limpio;
    }

    /**
     * Pone la mesa en 'libre' si ya no tiene pedidos sin cobrar.
     *
     * Una mesa queda libre al COBRAR, no al servir. El mismo criterio que
     * usa Mesa::estadoVisual() para decidir qué se ve en pantalla, para que
     * la base y la pantalla nunca se contradigan.
     */
    private function liberarMesaSiProcede(int $mesaId): void
    {
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*)
             FROM pedidos p
             LEFT JOIN ventas v
                    ON v.pedido_id = p.id AND v.estado = 'pagada'
             WHERE p.mesa_id = :mesa
               AND v.id IS NULL
               AND p.estado <> 'anulado'"
        );
        $stmt->execute([':mesa' => $mesaId]);

        if ((int) $stmt->fetchColumn() === 0) {
            $stmt = $this->conexion->prepare(
                "UPDATE mesas SET estado = 'libre' WHERE id = :id AND estado <> 'reservada'"
            );
            $stmt->execute([':id' => $mesaId]);
        }
    }

    /**
     * Resuelve los precios reales de los productos indicados.
     */
    private function preciosDe(array $items): array
    {
        $precios = [];

        foreach ($items as $item) {
            $id       = (int) ($item['id'] ?? 0);
            $cantidad = (float) ($item['cantidad'] ?? 0);

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmt = $this->conexion->prepare(
                "SELECT id, precio FROM productos WHERE id = :id AND estado = 1 LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $producto = $stmt->fetch();

            if (!$producto) {
                continue;
            }

            $precios[] = [
                'id'       => (int) $producto['id'],
                'cantidad' => $cantidad,
                'precio'   => (float) $producto['precio']
            ];
        }

        return $precios;
    }

    /**
     * Suma de los pedidos del día agrupados por categoría de producto.
     */
    public function porCategoriaDia(): array
    {
        $sql = "SELECT c.nombre AS categoria, COALESCE(SUM(d.subtotal), 0) AS total
                FROM detalle_pedidos d
                INNER JOIN productos p ON p.id = d.producto_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN pedidos pe ON pe.id = d.pedido_id
                WHERE DATE(pe.fecha_creacion) = CURDATE()
                GROUP BY c.nombre
                ORDER BY total DESC";

        return $this->conexion->query($sql)->fetchAll();
    }

    /**
     * Crea un pedido con sus ítems en una transacción.
     *
     * @param int   $mesaId
     * @param int   $usuarioId
     * @param array $items [['id' => producto_id, 'cantidad' => float], ...]
     *
     * @return array ['success' => bool, 'id' => int, 'message' => string]
     */
    public function crear(int $mesaId, int $usuarioId, array $items): array
    {
        if (!$items) {
            return ['success' => false, 'message' => 'El pedido no tiene productos.'];
        }

        // Los precios se leen siempre de la base, nunca del formulario
        $precios = [];

        foreach ($items as $item) {
            $idProducto = (int) ($item['id'] ?? 0);
            // Las cantidades son unidades enteras: nada de medias claves
            $cantidad   = (int) ($item['cantidad'] ?? 0);

            if ($idProducto <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmt = $this->conexion->prepare(
                "SELECT id, nombre, precio FROM productos WHERE id = :id AND estado = 1 LIMIT 1"
            );
            $stmt->execute([':id' => $idProducto]);
            $producto = $stmt->fetch();

            if (!$producto) {
                return [
                    'success' => false,
                    'message' => 'Uno de los productos ya no está disponible.'
                ];
            }

            // Si el mismo producto viene repetido, se junta en una sola línea
            foreach ($precios as &$linea) {
                if ($linea['id'] === (int) $producto['id']) {
                    $linea['cantidad'] += $cantidad;
                    continue 2;
                }
            }
            unset($linea);

            $precios[] = [
                'id'       => (int) $producto['id'],
                'nombre'   => $producto['nombre'],
                'cantidad' => $cantidad,
                'precio'   => (float) $producto['precio']
            ];
        }

        if (!$precios) {
            return ['success' => false, 'message' => 'El pedido no tiene productos válidos.'];
        }

        $total = 0;

        foreach ($precios as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "INSERT INTO pedidos (mesa_id, usuario_id, estado, total)
                 VALUES (:mesa_id, :usuario_id, 'pendiente', :total)"
            );
            $stmt->execute([
                ':mesa_id'    => $mesaId,
                ':usuario_id' => $usuarioId,
                ':total'      => $total
            ]);

            $pedidoId = (int) $this->conexion->lastInsertId();

            $stmt = $this->conexion->prepare(
                "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (:pedido_id, :producto_id, :cantidad, :precio, :subtotal)"
            );

            foreach ($precios as $item) {
                $stmt->execute([
                    ':pedido_id'    => $pedidoId,
                    ':producto_id'  => $item['id'],
                    ':cantidad'     => $item['cantidad'],
                    ':precio'       => $item['precio'],
                    ':subtotal'     => $item['precio'] * $item['cantidad']
                ]);
            }

            // El stock baja al tomar el pedido: lo que está en cocina ya no
            // está en el almacén. Si no alcanza, se cae toda la operación.
            foreach ($precios as $item) {
                $error = $this->aplicarDeltaStock(
                    $item['id'],
                    $item['cantidad'],
                    'Pedido N° ' . $pedidoId,
                    $usuarioId
                );

                if ($error !== null) {
                    $this->conexion->rollBack();

                    return ['success' => false, 'message' => $error];
                }
            }

            // La mesa queda ocupada mientras haya el pedido
            $stmt = $this->conexion->prepare(
                "UPDATE mesas SET estado = 'ocupada' WHERE id = :id AND estado = 'libre'"
            );
            $stmt->execute([':id' => $mesaId]);

            $this->conexion->commit();

            return ['success' => true, 'id' => $pedidoId, 'message' => 'Pedido enviado a cocina.'];

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return ['success' => false, 'message' => 'No se pudo guardar el pedido: ' . $e->getMessage()];
        }
    }

    /**
     * Aplica un cambio de stock y deja el movimiento correspondiente.
     *
     * $delta va con signo: positivo descuenta (salida), negativo repone
     * (entrada). Se bloquea la fila del producto porque dos pedidos a la vez
     * no pueden leer el mismo stock y descontarlo los dos.
     *
     * @return string|null mensaje de error, o null si todo salió bien
     */
    private function aplicarDeltaStock(
        int $productoId,
        float $delta,
        string $motivo,
        int $usuarioId
    ): ?string {
        if ($delta == 0.0) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            "SELECT nombre, stock FROM productos WHERE id = :id FOR UPDATE"
        );
        $stmt->execute([':id' => $productoId]);
        $producto = $stmt->fetch();

        if (!$producto) {
            return 'Uno de los productos ya no está disponible.';
        }

        $anterior = (float) $producto['stock'];
        $nuevo    = $anterior - $delta;   // delta positivo resta, negativo suma

        if ($nuevo < 0) {
            return 'No hay stock suficiente de "' . $producto['nombre'] . '": quedan '
                . number_format($anterior, 0, '.', ',') . '.';
        }

        $stmt = $this->conexion->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
        $stmt->execute([':stock' => $nuevo, ':id' => $productoId]);

        $stmt = $this->conexion->prepare(
            "INSERT INTO movimientos_inventario
                (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
             VALUES (:producto_id, :usuario_id, :tipo, :cantidad, :anterior, :nuevo, :motivo)"
        );
        $stmt->execute([
            ':producto_id' => $productoId,
            ':usuario_id'  => $usuarioId > 0 ? $usuarioId : null,
            ':tipo'        => $delta > 0 ? 'salida' : 'entrada',
            ':cantidad'    => abs($delta),
            ':anterior'    => $anterior,
            ':nuevo'       => $nuevo,
            ':motivo'      => $motivo
        ]);

        return null;
    }

    /**
     * Cantidades de un pedido agrupadas por producto.
     */
    private function cantidadesPorProducto(int $pedidoId): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT producto_id, SUM(cantidad) AS cantidad
             FROM detalle_pedidos
             WHERE pedido_id = :id
             GROUP BY producto_id"
        );
        $stmt->execute([':id' => $pedidoId]);

        $cantidades = [];

        foreach ($stmt->fetchAll() as $fila) {
            $cantidades[(int) $fila['producto_id']] = (float) $fila['cantidad'];
        }

        return $cantidades;
    }

    /**
     * Cambia el estado de un pedido.
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $permitidos = ['pendiente', 'preparando', 'preparado', 'entregado'];

        if (!in_array($estado, $permitidos, true)) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            "UPDATE pedidos SET estado = :estado WHERE id = :id"
        );

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }
}
