<?php

require_once __DIR__ . '/../config/database.php';

class Compra
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Compras con su proveedor, cantidad de ítems y total.
     */
    public function listar(int $limite = 100): array
    {
        // "items" es el total de unidades, no de filas: comprar 3 del mismo
        // producto es una línea con cantidad 3, y eso son 3 ítems.
        // "productos" cuenta cuántas líneas distintas tiene la compra.
        $sql = "SELECT
                    c.id,
                    c.total,
                    c.estado,
                    c.fecha,
                    p.nombre AS proveedor,
                    u.nombre AS usuario,
                    (SELECT COALESCE(SUM(d.cantidad), 0)
                     FROM detalle_compras d WHERE d.compra_id = c.id) AS items,
                    (SELECT COUNT(*) FROM detalle_compras d WHERE d.compra_id = c.id) AS productos
                FROM compras c
                LEFT JOIN proveedores p ON p.id = c.proveedor_id
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                ORDER BY c.fecha DESC
                LIMIT $limite";

        return $this->conexion->query($sql)->fetchAll();
    }

    public function conDetalle(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT c.*, p.nombre AS proveedor, u.nombre AS usuario
             FROM compras c
             LEFT JOIN proveedores p ON p.id = c.proveedor_id
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.id = :id"
        );
        $stmt->execute([':id' => $id]);

        $compra = $stmt->fetch();

        if (!$compra) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            "SELECT d.cantidad, d.precio_unitario, d.subtotal, pr.nombre
             FROM detalle_compras d
             INNER JOIN productos pr ON pr.id = d.producto_id
             WHERE d.compra_id = :id"
        );
        $stmt->execute([':id' => $id]);

        $compra['items'] = $stmt->fetchAll();

        return $compra;
    }

    /**
     * Registra una compra: guarda el encabezado, el detalle,
     * suma el stock de cada producto y deja el movimiento de inventario.
     *
     * También deja el gasto correspondiente en la tabla gastos, para que la
     * compra cuente en el "Gastos del día" del dashboard y de la caja.
     *
     * @param int   $proveedorId
     * @param array $items [['id' => producto_id, 'cantidad' => float, 'precio' => float], ...]
     */
    public function registrar(int $proveedorId, array $items): array
    {
        $limpio = [];
        $total  = 0.0;

        foreach ($items as $item) {
            $id       = (int) ($item['id'] ?? 0);
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio   = round((float) ($item['precio'] ?? 0), 2);

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmt = $this->conexion->prepare("SELECT id, nombre, precio FROM productos WHERE id = :id AND estado = 1");
            $stmt->execute([':id' => $id]);
            $producto = $stmt->fetch();

            if (!$producto) {
                return ['success' => false, 'message' => 'Uno de los productos ya no está disponible.'];
            }

            // El precio se escribe a mano, pero si se dejó en cero la compra
            // saldría con total 0 y el stock entraría sin justification.
            if ($precio <= 0) {
                return [
                    'success' => false,
                    'message' => 'Escribe el precio de compra de "' . $producto['nombre'] . '".'
                ];
            }

            $limpio[] = [
                'id'       => $id,
                'cantidad' => $cantidad,
                'precio'   => $precio,
                'subtotal' => $cantidad * $precio
            ];

            $total += $cantidad * $precio;
        }

        if (!$limpio) {
            return ['success' => false, 'message' => 'Agrega al menos un producto a la compra.'];
        }

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "INSERT INTO compras (proveedor_id, usuario_id, total, estado)
                 VALUES (:proveedor_id, :usuario_id, :total, 'registrada')"
            );
            $stmt->execute([
                ':proveedor_id' => $proveedorId > 0 ? $proveedorId : null,
                ':usuario_id'   => $usuarioId > 0 ? $usuarioId : null,
                ':total'        => $total
            ]);

            $compraId = (int) $this->conexion->lastInsertId();

            $stmtDetalle = $this->conexion->prepare(
                "INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (:compra_id, :producto_id, :cantidad, :precio, :subtotal)"
            );

            $stmtStock = $this->conexion->prepare("SELECT stock FROM productos WHERE id = :id FOR UPDATE");
            $stmtUpdate = $this->conexion->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
            $stmtMovimiento = $this->conexion->prepare(
                "INSERT INTO movimientos_inventario
                    (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                 VALUES (:producto_id, :usuario_id, 'entrada', :cantidad, :anterior, :nuevo, :motivo)"
            );

            foreach ($limpio as $item) {

                $stmtDetalle->execute([
                    ':compra_id'  => $compraId,
                    ':producto_id' => $item['id'],
                    ':cantidad'   => $item['cantidad'],
                    ':precio'     => $item['precio'],
                    ':subtotal'   => $item['subtotal']
                ]);

                // Suma stock y deja rastro del movimiento
                $stmtStock->execute([':id' => $item['id']]);
                $anterior = (float) $stmtStock->fetchColumn();
                $nuevo    = $anterior + $item['cantidad'];

                $stmtUpdate->execute([':stock' => $nuevo, ':id' => $item['id']]);

                $stmtMovimiento->execute([
                    ':producto_id' => $item['id'],
                    ':usuario_id'  => $usuarioId > 0 ? $usuarioId : null,
                    ':cantidad'    => $item['cantidad'],
                    ':anterior'    => $anterior,
                    ':nuevo'       => $nuevo,
                    ':motivo'      => 'Compra N° ' . $compraId
                ]);
            }

            // La compra es un gasto del día: se guarda junto a los demás para
            // que el dashboard y el movimiento de caja la sumen sola.
            $this->registrarGasto($compraId, $proveedorId, count($limpio), $total, $usuarioId);

            $this->conexion->commit();

            return [
                'success' => true,
                'id'      => $compraId,
                'message' => 'Compra registrada, stock actualizado y gasto del día contabilizado.'
            ];

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return ['success' => false, 'message' => 'No se pudo registrar la compra: ' . $e->getMessage()];
        }
    }

    /**
     * Anula una compra: revierte el stock, deja el movimiento de salida y
     * borra el gasto que generó, para que no siga sumando en la caja.
     */
    public function anular(int $compraId): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id, estado FROM compras WHERE id = :id FOR UPDATE"
        );
        $stmt->execute([':id' => $compraId]);
        $compra = $stmt->fetch();

        if (!$compra) {
            return ['success' => false, 'message' => 'La compra no existe.'];
        }

        if ($compra['estado'] === 'anulada') {
            return ['success' => false, 'message' => 'Esa compra ya estaba anulada.'];
        }

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "SELECT producto_id, cantidad FROM detalle_compras WHERE compra_id = :id"
            );
            $stmt->execute([':id' => $compraId]);
            $items = $stmt->fetchAll();

            $stmtStock = $this->conexion->prepare("SELECT stock FROM productos WHERE id = :id FOR UPDATE");
            $stmtUpdate = $this->conexion->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
            $stmtMovimiento = $this->conexion->prepare(
                "INSERT INTO movimientos_inventario
                    (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                 VALUES (:producto_id, :usuario_id, 'salida', :cantidad, :anterior, :nuevo, :motivo)"
            );

            foreach ($items as $item) {

                $stmtStock->execute([':id' => $item['producto_id']]);
                $anterior = (float) $stmtStock->fetchColumn();
                $nuevo    = max(0.0, $anterior - (float) $item['cantidad']);

                $stmtUpdate->execute([':stock' => $nuevo, ':id' => $item['producto_id']]);

                $stmtMovimiento->execute([
                    ':producto_id' => $item['producto_id'],
                    ':usuario_id'  => $usuarioId > 0 ? $usuarioId : null,
                    ':cantidad'    => abs($anterior - $nuevo),
                    ':anterior'    => $anterior,
                    ':nuevo'       => $nuevo,
                    ':motivo'      => 'Anulación compra N° ' . $compraId
                ]);
            }

            $stmt = $this->conexion->prepare("UPDATE compras SET estado = 'anulada' WHERE id = :id");
            $stmt->execute([':id' => $compraId]);

            // El gasto deja de contar en el día
            $stmt = $this->conexion->prepare("DELETE FROM gastos WHERE compra_id = :id");
            $stmt->execute([':id' => $compraId]);

            $this->conexion->commit();

            return [
                'success' => true,
                'message' => 'Compra anulada: se revirtió el stock y se quitó el gasto del día.'
            ];

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return ['success' => false, 'message' => 'No se pudo anular la compra: ' . $e->getMessage()];
        }
    }

    /**
     * Asienta el gasto de una compra en la tabla gastos.
     *
     * Se guarda compra_id para poder revertirla al anular.
     */
    private function registrarGasto(
        int $compraId,
        int $proveedorId,
        int $cantidadItems,
        float $total,
        int $usuarioId
    ): void {
        $stmt = $this->conexion->prepare("SELECT nombre FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => $proveedorId]);
        $proveedor = $stmt->fetchColumn();

        $descripcion = sprintf(
            'Compra C-%04d - %d producto(s)%s',
            $compraId,
            $cantidadItems,
            $proveedor ? ' a ' . $proveedor : ''
        );

        $stmt = $this->conexion->prepare(
            "INSERT INTO gastos (usuario_id, compra_id, categoria, descripcion, monto)
             VALUES (:usuario_id, :compra_id, 'Compra', :descripcion, :monto)"
        );
        $stmt->execute([
            ':usuario_id'  => $usuarioId > 0 ? $usuarioId : null,
            ':compra_id'   => $compraId,
            ':descripcion' => mb_substr($descripcion, 0, 255),
            ':monto'       => $total
        ]);
    }
}