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
            $cantidad   = (float) ($item['cantidad'] ?? 0);

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

            $precios[] = [
                'id'       => (int) $producto['id'],
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
