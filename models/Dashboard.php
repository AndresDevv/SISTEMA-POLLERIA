<?php

require_once __DIR__ . '/Mesa.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Pedido.php';
require_once __DIR__ . '/Venta.php';

/**
 * Indicadores del tablero principal.
 */
class Dashboard
{
    public static function metricas(): array
    {
        $db      = conexionDB();
        $venta   = new Venta();
        $egresos = self::egresosHoy($db);
        $ingresos = $venta->totalHoy();

        return [
            'ventas_hoy'     => $ingresos,
            'egresos_hoy'    => $egresos,
            'ganancia'       => $ingresos - $egresos,
            'pedidos_hoy'    => self::conteo($db, 'pedidos', 'DATE(fecha_creacion) = CURDATE()'),
            'mesas_total'    => self::conteo($db, 'mesas', '1 = 1'),
            'mesas_ocupadas' => self::mesasOcupadas($db),
            'stock_bajo'     => (new Producto())->stockBajo(),
            'personal'       => self::conteo($db, 'empleados', 'estado = 1')
        ];
    }

    private static function conteo(PDO $db, string $tabla, string $donde): int
    {
        $stmt = $db->query("SELECT COUNT(*) FROM $tabla WHERE $donde");

        return (int) $stmt->fetchColumn();
    }

    private static function egresosHoy(PDO $db): float
    {
        try {
            $stmt = $db->query(
                "SELECT COALESCE(SUM(monto), 0) FROM egresos WHERE DATE(fecha) = CURDATE()"
            );

            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    private static function mesasOcupadas(PDO $db): int
    {
        $stmt = $db->query(
            "SELECT COUNT(DISTINCT mesa_id)
             FROM pedidos
             WHERE estado IN ('pendiente','preparando','preparado')"
        );

        return (int) $stmt->fetchColumn();
    }
}
