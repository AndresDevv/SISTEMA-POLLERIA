<?php

require_once __DIR__ . '/../config/database.php';

class Venta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Ventas del día con su mesa y estado de cobro.
     */
    public function delDia(): array
    {
        $sql = "SELECT
                    v.id,
                    v.pedido_id,
                    v.subtotal,
                    v.descuento,
                    v.total,
                    v.estado,
                    v.fecha,
                    m.numero AS mesa_numero,
                    p.estado AS pedido_estado
                FROM ventas v
                INNER JOIN pedidos p ON p.id = v.pedido_id
                INNER JOIN mesas m ON m.id = p.mesa_id
                WHERE DATE(v.fecha) = CURDATE()
                ORDER BY v.fecha DESC";

        return $this->conexion->query($sql)->fetchAll();
    }

    /**
     * Total cobrado hoy.
     */
    public function totalHoy(): float
    {
        $stmt = $this->conexion->query(
            "SELECT COALESCE(SUM(total), 0)
             FROM ventas
             WHERE DATE(fecha) = CURDATE()
               AND estado = 'pagada'"
        );

        return (float) $stmt->fetchColumn();
    }

    /**
     * Suma de las ventas por categoría de producto.
     */
    public function porCategoria(): array
    {
        $sql = "SELECT c.nombre AS categoria, COALESCE(SUM(d.subtotal), 0) AS total
                FROM ventas v
                INNER JOIN detalle_pedidos d ON d.pedido_id = v.pedido_id
                INNER JOIN productos p ON p.id = d.producto_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE DATE(v.fecha) = CURDATE() AND v.estado = 'pagada'
                GROUP BY c.nombre
                ORDER BY total DESC";

        return $this->conexion->query($sql)->fetchAll();
    }

    /**
     * Registra el cobro de un pedido entregado.
     */
    public function registrar(int $pedidoId, int $usuarioId, float $descuento = 0.0): bool
    {
        $stmt = $this->conexion->prepare(
            "SELECT COALESCE(SUM(subtotal), 0) AS subtotal
             FROM detalle_pedidos
             WHERE pedido_id = :pedido"
        );
        $stmt->execute([':pedido' => $pedidoId]);
        $subtotal = (float) $stmt->fetchColumn();

        $total = max(0.0, $subtotal - $descuento);

        $stmt = $this->conexion->prepare(
            "INSERT INTO ventas (pedido_id, usuario_id, subtotal, descuento, total, estado)
             VALUES (:pedido_id, :usuario_id, :subtotal, :descuento, :total, 'pagada')"
        );

        return $stmt->execute([
            ':pedido_id'  => $pedidoId,
            ':usuario_id' => $usuarioId,
            ':subtotal'   => $subtotal,
            ':descuento'  => $descuento,
            ':total'      => $total
        ]);
    }
}
