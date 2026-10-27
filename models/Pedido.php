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
                    p.fecha_actualizacion,
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
     * Pedidos que siguen sin cobrar, de cualquier día.
     */
    public function sinCobrar(): array
    {
        // Un pedido servido queda en 'entregado': deja de estar activo y, si
        // es de otro día, tampoco sale en el listado del día. Sin esta lista
        // un pedido abandonado sería incobrable desde la pantalla y su mesa
        // se quedaría ocupada para siempre.
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
                INNER JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
                WHERE v.id IS NULL
                  AND p.estado <> 'anulado'
                ORDER BY p.fecha_creacion";

        $stmt = $this->conexion->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Cuentas por cobrar, agrupadas por mesa.
     *
     * Es lo que ve el cajero: cada mesa con pedidos sin pagar, cuántos
     * pedidos acumula y cuánto debe en total. Se ordena por la mesa con la
     * cuenta más antigua, que es la que más rato lleva esperando el cobro.
     *
     * @return array [['mesa_id','mesa_numero','pedidos','total','desde',
     *                'ultimo'], ...]
     */
    public function cuentasPorMesa(): array
    {
        $sql = "SELECT
                    m.id                          AS mesa_id,
                    m.numero                      AS mesa_numero,
                    COUNT(p.id)                   AS pedidos,
                    COALESCE(SUM(p.total), 0)     AS total,
                    MIN(p.fecha_creacion)         AS desde,
                    MAX(p.fecha_creacion)         AS ultimo
                FROM pedidos p
                INNER JOIN mesas m ON m.id = p.mesa_id
                LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
                WHERE v.id IS NULL
                  AND p.estado <> 'anulado'
                GROUP BY m.id, m.numero
                ORDER BY MIN(p.fecha_creacion)";

        return $this->conexion->query($sql)->fetchAll();
    }

    /**
     * Pedidos sin cobrar de una mesa, con el detalle de sus platos.
     *
     * Con esto el cajero revisa la cuenta antes de cobrar. Va junto al
     * total de cada pedido para poder mostrar el desglose.
     */
    public function cuentaDeMesa(int $mesaId): array
    {
        $sql = "SELECT
                    p.id,
                    p.estado,
                    p.total,
                    p.fecha_creacion,
                    u.nombre AS usuario_nombre
                FROM pedidos p
                INNER JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
                WHERE p.mesa_id = :mesa
                  AND v.id IS NULL
                  AND p.estado <> 'anulado'
                ORDER BY p.fecha_creacion";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':mesa' => $mesaId]);
        $pedidos = $stmt->fetchAll();

        if (!$pedidos) {
            return [];
        }

        $detalle = $this->conexion->prepare(
            "SELECT d.cantidad, d.precio_unitario, d.subtotal, pr.nombre
             FROM detalle_pedidos d
             INNER JOIN productos pr ON pr.id = d.producto_id
             WHERE d.pedido_id = :pedido
             ORDER BY d.id"
        );

        foreach ($pedidos as &$pedido) {
            $detalle->execute([':pedido' => $pedido['id']]);
            $pedido['items'] = $detalle->fetchAll();
        }

        return $pedidos;
    }

    /**
     * Total que debe una mesa (suma de sus pedidos sin cobrar).
     */
    public function totalDeMesa(int $mesaId): float
    {
        $stmt = $this->conexion->prepare(
            "SELECT COALESCE(SUM(p.total), 0)
             FROM pedidos p
             LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
             WHERE p.mesa_id = :mesa
               AND v.id IS NULL
               AND p.estado <> 'anulado'"
        );
        $stmt->execute([':mesa' => $mesaId]);

        return round((float) $stmt->fetchColumn(), 2);
    }

    /**
     * Detalle de un pedido con sus productos.
     */
    public function conDetalle(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT p.*, m.numero AS mesa_numero, u.nombre AS usuario_nombre
             FROM pedidos p
             INNER JOIN mesas m ON m.id = p.mesa_id
             LEFT JOIN usuarios u ON u.id = p.usuario_id
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
     * Id de la última novedad registrada.
     *
     * Es el punto de partida del aviso de cocina: si se empieza en 0, el
     * primer poll devolvería todas las novedades viejas y sonaría la alarma
     * por cosas que ya vio.
     */
    public function ultimaNovedad(): int
    {
        try {
            return (int) $this->conexion
                ->query("SELECT COALESCE(MAX(id), 0) FROM pedido_novedades")
                ->fetchColumn();

        } catch (PDOException $e) {
            // Si la tabla aún no existe (SQL sin cargar), se sigue sin
            // novedades en vez de romper la pantalla.
            return 0;
        }
    }

    /**
     * Novedades de pedidos sin cobrar posteriores a un id.
     *
     * @return array [['id' =>, 'pedido_id' =>, 'mesa' =>, 'texto' =>], ...]
     */
    public function novedades(int $desde, int $limite = 30): array
    {
        try {
            $stmt = $this->conexion->prepare(
                "SELECT n.id, n.pedido_id, n.texto, m.numero AS mesa
                 FROM pedido_novedades n
                 INNER JOIN pedidos p ON p.id = n.pedido_id
                 INNER JOIN mesas m   ON m.id = p.mesa_id
                 LEFT JOIN ventas v  ON v.pedido_id = p.id AND v.estado = 'pagada'
                 WHERE n.id > :desde AND v.id IS NULL
                 ORDER BY n.id
                 LIMIT " . (int) $limite
            );
            $stmt->execute([':desde' => $desde]);

            return array_map(static fn (array $n): array => [
                'id'        => (int) $n['id'],
                'pedido_id' => (int) $n['pedido_id'],
                'mesa'      => (int) $n['mesa'],
                'texto'     => $n['texto'],
            ], $stmt->fetchAll());

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Pedidos de una mesa, del más reciente al más antiguo.
     *
     * Por defecto solo los del día: al abrir el modal de una mesa solo
     * interesa lo que se está pidiendo ahora, no el historial de días
     * anteriores, que además tapaba el catálogo.
     */
    public function porMesa(int $mesaId, bool $soloHoy = true): array
    {
        $sql = "SELECT p.id, p.estado, p.total, p.fecha_creacion, p.usuario_id, COALESCE(u.nombre, '') AS usuario_nombre
                FROM pedidos p
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.mesa_id = :mesa";

        if ($soloHoy) {
            $sql .= " AND DATE(p.fecha_creacion) = CURDATE()";
        }

        $sql .= " ORDER BY p.fecha_creacion DESC, p.id DESC";

        $stmt = $this->conexion->prepare($sql);
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
     * Cobra de una sola vez todos los pedidos sin pagar de una mesa.
     *
     * Es como cobra el cajero: la mesa tiene una sola cuenta, aunque sean
     * varios pedidos, y se cierra toda junta. Cada pedido genera su propia
     * venta (el detalle de lo que se vendió no se puede perder), pero el
     * cliente paga un único total.
     *
     * @param array $pagos          [['metodo_pago_id' =>, 'monto' =>], ...]
     * @param float $montoRecibido  con cuánto paga en efectivo, si aplica
     *
     * @return array ['success' =>, 'message' =>, 'total' =>, 'vuelto' =>]
     */
    public function cobrarMesa(int $mesaId, int $usuarioId, array $pagos, float $montoRecibido = 0.0): array
    {
        try {
            $this->conexion->beginTransaction();

            $pedidos = $this->conexion->prepare(
                "SELECT p.id
                 FROM pedidos p
                 LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
                 WHERE p.mesa_id = :mesa
                   AND v.id IS NULL
                   AND p.estado <> 'anulado'
                 ORDER BY p.fecha_creacion
                 FOR UPDATE"
            );
            $pedidos->execute([':mesa' => $mesaId]);
            $ids = array_map(static fn (array $f): int => (int) $f['id'], $pedidos->fetchAll());

            if (!$ids) {
                $this->conexion->rollBack();

                return ['success' => false, 'message' => 'Esa mesa no tiene pedidos por cobrar.'];
            }

            // Total de la cuenta completa
            $total = $this->totalDeMesa($mesaId);

            // El vuelto se calcula dentro de repartirPagos, que espera el
            // "con cuánto paga" en el mismo arreglo de pagos.
            $pagos['monto_recibido'] = $montoRecibido;

            $reparto = $this->repartirPagos($pagos, $total);

            if ($reparto === null) {
                $this->conexion->rollBack();

                return [
                    'success' => false,
                    'message' => 'El pago no cubre el total de la cuenta.'
                ];
            }

            // Se devuelve el vuelto calculado para poder mostrarlo en pantalla
            $vuelto = 0.0;

            foreach ($reparto as $linea) {
                if ($linea['vuelto'] !== null) {
                    $vuelto = (float) $linea['vuelto'];
                }
            }

            $insertaVenta = $this->conexion->prepare(
                "INSERT INTO ventas (pedido_id, usuario_id, subtotal, descuento, total, estado)
                 VALUES (:pedido_id, :usuario_id, :subtotal, 0, :total, 'pagada')"
            );

            $insertaPago = $this->conexion->prepare(
                "INSERT INTO pagos (venta_id, metodo_pago_id, monto, monto_recibido, vuelto)
                 VALUES (:venta_id, :metodo_id, :monto, :recibido, :vuelto)"
            );

            /**
             * El reparto de métodos se aplica pedido por pedido.
             *
             * El cliente paga un solo total, pero cada venta necesita su
             * propia fila de pago para que los reportes por método cuadren.
             * Se va consumiendo lo que indicó el cajero en el mismo orden.
             */
            $pendiente = $reparto;
            $indice    = 0;

            $leePedido = $this->conexion->prepare("SELECT total FROM pedidos WHERE id = :id");

            foreach ($ids as $pedidoId) {
                $leePedido->execute([':id' => $pedidoId]);
                $montoPedido = (float) $leePedido->fetchColumn();

                $insertaVenta->execute([
                    ':pedido_id'  => $pedidoId,
                    ':usuario_id' => $usuarioId,
                    ':subtotal'   => $montoPedido,
                    ':total'      => $montoPedido
                ]);

                $ventaId  = (int) $this->conexion->lastInsertId();
                $restante = $montoPedido;

                while ($restante > 0.01 && $indice < count($pendiente)) {

                    $disponible = (float) $pendiente[$indice]['monto'];
                    $aplicar    = min($disponible, $restante);

                    $insertaPago->execute([
                        ':venta_id'  => $ventaId,
                        ':metodo_id' => $pendiente[$indice]['metodo_pago_id'],
                        ':monto'     => round($aplicar, 2),
                        ':recibido'  => null,
                        ':vuelto'    => null
                    ]);

                    $pendiente[$indice]['monto'] = round($disponible - $aplicar, 2);
                    $restante = round($restante - $aplicar, 2);

                    if ($pendiente[$indice]['monto'] <= 0.01) {
                        $indice++;
                    }
                }
            }

            $this->conexion->prepare(
                "UPDATE pedidos SET estado = 'entregado' WHERE mesa_id = :mesa
                   AND id IN (" . implode(',', array_map('intval', $ids)) . ")"
            )->execute([':mesa' => $mesaId]);

            // La mesa queda libre cuando ya no le queda nada por cobrar
            $this->liberarMesaSiProcede($mesaId);

            /**
             * El vuelto, y con cuánto pagó el cliente, se guardan en la
             * primera fila de pago de la venta más antigua: es la que
             * representa el cierre de la cuenta de la mesa.
             */
            if ($montoRecibido > 0 || $vuelto > 0) {

                $leeVenta = $this->conexion->prepare(
                    "SELECT id FROM ventas WHERE pedido_id = :pedido ORDER BY id LIMIT 1"
                );
                $leeVenta->execute([':pedido' => $ids[0]]);
                $primera = (int) $leeVenta->fetchColumn();

                $this->conexion->prepare(
                    "UPDATE pagos SET monto_recibido = :recibido, vuelto = :vuelto
                     WHERE venta_id = :venta
                     ORDER BY id LIMIT 1"
                )->execute([
                    ':recibido' => $montoRecibido > 0 ? $montoRecibido : null,
                    ':vuelto'   => $vuelto,
                    ':venta'    => $primera
                ]);
            }

            $this->conexion->commit();

            return [
                'success' => true,
                'message' => 'Cuenta cobrada y mesa liberada.',
                'total'   => $total,
                'vuelto'  => $vuelto
            ];

        } catch (PDOException $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return ['success' => false, 'message' => 'No se pudo registrar el cobro.'];
        }
    }

    /**
     * Valida los pagos recibidos y los ajusta al total.
     * Devuelve null si no cuadran.
     *
     * En efectivo se puede indicar con cuánto paga el cliente
     * (monto_recibido): si esa cantidad cubre la parte en efectivo, la
     * diferencia es el vuelto que hay que devolver. Es el caso típico de
     * pagar un pedido de S/ 28 con un billete de S/ 50 o de S/ 100.
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

            $limpio[] = [
                'metodo_pago_id' => $metodo,
                'monto'          => $monto,
                'monto_recibido' => null,
                'vuelto'         => null
            ];
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

        /**
         * Vuelto del efectivo.
         *
         * Se calcula por fuera del reparto: el monto real que entra a caja
         * es siempre lo que costó el pedido, y lo que se devuelve al cliente
         * queda guardado aparte para poder revisarlo después.
         */
        $recibido = round((float) ($pagos['monto_recibido'] ?? 0), 2);

        if ($recibido > 0) {
            $efectivo = 0.0;

            foreach ($limpio as $linea) {
                $efectivo += $linea['monto'];
            }

            if ($recibido < $efectivo - 0.01) {
                return null;
            }

            $limpio[$ultimo]['monto_recibido'] = $recibido;
            $limpio[$ultimo]['vuelto']         = round($recibido - $efectivo, 2);
        }

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
     * Si la mesa ya tiene un pedido ABIERTO de hoy, los productos se suman a
     * ese pedido en vez de crear uno nuevo: es lo normal cuando el mismo
     * cliente pide otra cosa a los pocos minutos.
     *
     * @param int   $mesaId
     * @param int   $usuarioId
     * @param array $items [['id' => producto_id, 'cantidad' => float], ...]
     *
     * @return array ['success' => bool, 'id' => int, 'message' => string,
     *               'sumado' => bool]
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

            /**
             * Pedido abierto de esta mesa, si lo hay.
             *
             * "Abierto" = todavía en cocina (ni servido ni cobrado). Solo se
             * mira el de hoy: una mesa que quedó abierta de ayer no debe
             * absorber el pedido de hoy.
             */
            $stmt = $this->conexion->prepare(
                "SELECT p.id, p.estado
                 FROM pedidos p
                 LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
                 WHERE p.mesa_id = :mesa
                   AND v.id IS NULL
                   AND p.estado IN ('pendiente','preparando','preparado')
                   AND DATE(p.fecha_creacion) = CURDATE()
                 ORDER BY p.fecha_creacion DESC, p.id DESC
                 LIMIT 1"
            );
            $stmt->execute([':mesa' => $mesaId]);
            $abierto = $stmt->fetch();

            $sumado = false;

            if ($abierto) {
                $pedidoId = (int) $abierto['id'];

                // Si ya estaba aceptado, vuelve a "pendiente": hay cosas
                // nuevas que cocina todavía no vio, y la mesa tiene que
                // ponerse en amarillo otra vez para que no se pierdan.
                $stmt = $this->conexion->prepare(
                    "UPDATE pedidos
                     SET estado = 'pendiente', fecha_actualizacion = NOW()
                     WHERE id = :id"
                );
                $stmt->execute([':id' => $pedidoId]);

                $stmt = $this->conexion->prepare(
                    "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                     VALUES (:pedido_id, :producto_id, :cantidad, :precio, :subtotal)
                     ON DUPLICATE KEY UPDATE
                        cantidad = cantidad + VALUES(cantidad),
                        subtotal = subtotal + VALUES(subtotal)"
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

                $sumado = true;

                /**
                 * Se deja rastro de qué se sumó.
                 *
                 * Como no se crea un pedido nuevo, cocina no vería nada nuevo
                 * y podría no notar que le agregaron productos a esa comanda.
                 * Esta nota es lo que le permite avisarle exactamente qué
                 * apareció.
                 */
                $stmt = $this->conexion->prepare(
                    "INSERT INTO pedido_novedades (pedido_id, producto_id, texto, cantidad, usuario_id)
                     VALUES (:pedido_id, :producto_id, :texto, :cantidad, :usuario_id)"
                );

                foreach ($precios as $item) {
                    $stmt->execute([
                        ':pedido_id'   => $pedidoId,
                        ':producto_id' => $item['id'],
                        ':texto'       => $item['cantidad'] . 'x ' . $item['nombre'],
                        ':cantidad'    => $item['cantidad'],
                        ':usuario_id'  => $usuarioId,
                    ]);
                }

            } else {
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
            }

            // El total se recalcula siempre desde las líneas del pedido, para
            // que al sumar productos no se descuadre.
            $this->recalcularTotal($pedidoId);

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

            $resumenAgregado = [];

            foreach ($precios as $item) {
                $resumenAgregado[] = $item['cantidad'] . 'x ' . $item['nombre'];
            }

            return [
                'success'  => true,
                'id'       => $pedidoId,
                'sumado'   => $sumado,
                'agregado' => $sumado ? $resumenAgregado : [],
                'message'  => $sumado
                    ? 'Agregado al pedido P-' . str_pad((string) $pedidoId, 4, '0', STR_PAD_LEFT)
                      . ': ' . implode(', ', $resumenAgregado)
                    : 'Pedido enviado a cocina.',
            ];

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return ['success' => false, 'message' => 'No se pudo guardar el pedido: ' . $e->getMessage()];
        }
    }

    /**
     * Recalcula el total de un pedido a partir de sus líneas.
     *
     * Se usa después de sumar productos, porque el total guardado no se
     * entera solo y un total viejo cobraría de menos.
     */
    private function recalcularTotal(int $pedidoId): void
    {
        $stmt = $this->conexion->prepare(
            "UPDATE pedidos
             SET total = (SELECT COALESCE(SUM(subtotal), 0) FROM detalle_pedidos WHERE pedido_id = :id)
             WHERE id = :id"
        );
        $stmt->execute([':id' => $pedidoId]);
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
