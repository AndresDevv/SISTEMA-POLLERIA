<?php

require_once __DIR__ . '/Mesa.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Pedido.php';
require_once __DIR__ . '/Venta.php';

/**
 * Indicadores del tablero principal.
 *
 * Todos aceptan la fecha a consultar (YYYY-MM-DD). Por defecto es hoy.
 * No se guarda ningún resumen: las cifras salen de ventas, pagos, egresos y
 * pedidos, así que el historial de cualquier día está siempre disponible.
 */
class Dashboard
{
    public static function metricas(string $fecha = ''): array
    {
        $fecha = self::validar($fecha);

        $db       = conexionDB();
        $venta    = new Venta();
        $egresos  = self::egresosDe($db, $fecha);
        $ingresos = $venta->totalDe($fecha);

        return [
            'fecha'          => $fecha,
            'ventas_hoy'     => $ingresos,
            'egresos_hoy'    => $egresos,
            'ganancia'       => $ingresos - $egresos,
            'pedidos_hoy'    => self::conteo($db, 'pedidos', 'DATE(fecha_creacion) = :f', $fecha),
            'mesas_total'    => self::conteo($db, 'mesas', '1 = 1'),
            'mesas_ocupadas' => self::mesasOcupadas($db, $fecha),
            'stock_bajo'     => (new Producto())->stockBajo(),
            'personal'       => self::conteo($db, 'empleados', 'estado = 1')
        ];
    }

    private static function validar(string $fecha): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d');
    }

    private static function conteo(PDO $db, string $tabla, string $donde, ?string $fecha = null): int
    {
        if ($fecha !== null && str_contains($donde, ':f')) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM $tabla WHERE " . str_replace(':f', ':f', $donde));
            $stmt->execute([':f' => $fecha]);

            return (int) $stmt->fetchColumn();
        }

        return (int) $db->query("SELECT COUNT(*) FROM $tabla WHERE $donde")->fetchColumn();
    }

    /**
     * Egresos del día, sumando egresos y gastos.
     * Las vistas también lo usan para mostrarlo en caja.
     */
    public static function egresosPublicos(string $fecha = ''): float
    {
        return self::egresosDe(conexionDB(), self::validar($fecha));
    }

    /**
     * Egresos de un día, sumando egresos y gastos.
     */
    private static function egresosDe(PDO $db, string $fecha): float
    {
        $total = 0.0;

        foreach (['egresos', 'gastos'] as $tabla) {

            try {
                $stmt = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM $tabla WHERE DATE(fecha) = :f");
                $stmt->execute([':f' => $fecha]);
                $total += (float) $stmt->fetchColumn();

            } catch (PDOException $e) {
                // la tabla puede no existir; se ignora
            }
        }

        return $total;
    }

    /**
     * Mesas que tuvieron pedidos activos ese día.
     */
    private static function mesasOcupadas(PDO $db, string $fecha): int
    {
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT mesa_id)
             FROM pedidos
             WHERE DATE(fecha_creacion) = :f"
        );
        $stmt->execute([':f' => $fecha]);

        return (int) $stmt->fetchColumn();
    }
}
