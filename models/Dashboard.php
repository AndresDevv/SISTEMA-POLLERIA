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
            'mesas_ocupadas' => self::mesasOcupadas($db),
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
     * Egresos del día, que son las compras de insumos registradas.
     * Las vistas también lo usan para mostrarlo en Caja.
     */
    public static function egresosPublicos(string $fecha = ''): float
    {
        return self::egresosDe(conexionDB(), self::validar($fecha));
    }

    /**
     * Egresos de un día: la suma de las compras registradas.
     *
     * Las compras se guardan en la tabla egresos, asi que aqui ya no se suma
     * la tabla gastos: esa duplicaba al mismo dato y hacia que el total del
     * dia saliera counting dos veces lo mismo.
     */
    private static function egresosDe(PDO $db, string $fecha): float
    {
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM egresos WHERE DATE(fecha) = :f"
        );
        $stmt->execute([':f' => $fecha]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Mesas ocupadas ahora mismo.
     *
     * Cuenta las mesas con pedidos SIN COBRAR, que es la misma regla que usa
     * la pantalla Mesas y Pedidos y la que usa el cobro para liberar la mesa.
     *
     * Antes contaba cualquier mesa que hubiera tenido un pedido hoy, y eso
     * incluía las que ya estaban pagadas: el tablero decía "9 de 16 ocupadas"
     * con todas las mesas libres en la pantalla de mesas.
     *
     * Lo que marca el final de una mesa es el COBRO, no el estado del pedido:
     * un plato que ya se sirvio sigue sin pagarse y la mesa sigue ocupada.
     */
    private static function mesasOcupadas(PDO $db): int
    {
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.mesa_id)
             FROM pedidos p
             LEFT JOIN ventas v
                    ON v.pedido_id = p.id AND v.estado = 'pagada'
             WHERE v.id IS NULL
               AND p.estado <> 'anulado'
               AND p.mesa_id IS NOT NULL"
        );
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
