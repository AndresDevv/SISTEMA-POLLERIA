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
     * Valida una fecha YYYY-MM-DD.
     */
    private function validarFecha(string $fecha): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d');
    }

    /**
     * Total cobrado hoy.
     */
    public function totalHoy(): float
    {
        return $this->totalDe(date('Y-m-d'));
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
     * Total cobrado en una fecha.
     */
    public function totalDe(string $fecha = ''): float
    {
        $fecha = $this->validarFecha($fecha);

        $stmt = $this->conexion->prepare(
            "SELECT COALESCE(SUM(total), 0)
             FROM ventas
             WHERE DATE(fecha) = :f
               AND estado = 'pagada'"
        );
        $stmt->execute([':f' => $fecha]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Montos cobrados agrupados por método de pago en una fecha.
     */
    public function porMetodoPago(string $fecha = ''): array
    {
        $fecha = $this->validarFecha($fecha);

        $sql = "SELECT m.nombre AS metodo, COALESCE(SUM(pg.monto), 0) AS total
                FROM metodos_pago m
                LEFT JOIN pagos pg ON pg.metodo_pago_id = m.id
                    AND DATE(pg.fecha) = :fecha
                WHERE m.estado = 1
                GROUP BY m.id, m.nombre
                ORDER BY m.id";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':fecha' => $fecha]);
        $filas = $stmt->fetchAll();

        $porNombre = [];

        foreach ($filas as $fila) {
            $porNombre[strtolower($fila['metodo'])] = [
                'metodo' => $fila['metodo'],
                'total'  => (float) $fila['total']
            ];
        }

        // Orden fijo para que coincida con el diseño
        $orden = ['efectivo', 'yape', 'plin', 'tarjeta'];
        $resultado = [];

        foreach ($orden as $clave) {
            $resultado[] = $porNombre[$clave] ?? ['metodo' => ucfirst($clave), 'total' => 0.0];
        }

        return $resultado;
    }

    /**
     * Métodos de pago activos, para el selector del cobro.
     */
    public function metodosPago(): array
    {
        return $this->conexion->query(
            "SELECT id, nombre FROM metodos_pago WHERE estado = 1 ORDER BY id"
        )->fetchAll();
    }

    /**
     * Indica si un pedido ya fue cobrado.
     */
    public function yaCobrado(int $pedidoId): bool
    {
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) FROM ventas WHERE pedido_id = :pedido AND estado = 'pagada'"
        );
        $stmt->execute([':pedido' => $pedidoId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
