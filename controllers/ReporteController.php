<?php

/**
 * Exporta las ventas y los gastos del período a CSV.
 *
 * Se invoca con index.php?page=reportes/exportar&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
 *
 * Son consultas separadas a propósito: unir detalle_pedidos con pagos en
 * la misma consulta multiplica las filas y descuadra el resumen de pagos.
 */

require_once __DIR__ . '/../config/database.php';

$db = conexionDB();

$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $desde = $hasta = date('Y-m-d');
}

/** Cabecera de ventas */
$stmt = $db->prepare(
    "SELECT v.id, v.fecha, v.pedido_id, v.subtotal, v.descuento, v.total, v.estado,
            m.numero AS mesa, u.nombre AS cajero
     FROM ventas v
     INNER JOIN pedidos p ON p.id = v.pedido_id
     INNER JOIN mesas m ON m.id = p.mesa_id
     LEFT JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.estado = 'pagada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
     ORDER BY v.fecha"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$ventas = $stmt->fetchAll();

// Ojo: productos y pagos se buscan por claves distintas.
// Los productos cuelgan del pedido, los pagos de la venta.
$idsPedido = array_column($ventas, 'pedido_id');
$idsVenta  = array_column($ventas, 'id');

$marcadoresPedido = $idsPedido ? implode(',', array_fill(0, count($idsPedido), '?')) : 'NULL';
$marcadoresVenta  = $idsVenta ? implode(',', array_fill(0, count($idsVenta), '?')) : 'NULL';

/** Productos por venta */
$stmt = $db->prepare(
    "SELECT d.pedido_id, GROUP_CONCAT(CONCAT(d.cantidad, ' x ', pr.nombre) SEPARATOR ' | ') AS productos
     FROM detalle_pedidos d
     INNER JOIN productos pr ON pr.id = d.producto_id
     WHERE d.pedido_id IN ($marcadoresPedido)
     GROUP BY d.pedido_id"
);
$stmt->execute($idsPedido);
$porPedido = [];

foreach ($stmt->fetchAll() as $fila) {
    $porPedido[$fila['pedido_id']] = $fila['productos'];
}

/** Pagos por venta, agrupados por método */
$stmt = $db->prepare(
    "SELECT pg.venta_id, m.nombre AS metodo, SUM(pg.monto) AS total
     FROM pagos pg
     INNER JOIN metodos_pago m ON m.id = pg.metodo_pago_id
     WHERE pg.venta_id IN ($marcadoresVenta)
     GROUP BY pg.venta_id, m.nombre
     ORDER BY m.nombre"
);
$stmt->execute($idsVenta);
$porVenta = [];

foreach ($stmt->fetchAll() as $fila) {
    $porVenta[$fila['venta_id']][] = $fila['metodo'] . ': ' . number_format((float) $fila['total'], 2);
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ventas_' . $desde . '_a_' . $hasta . '.csv"');

$salida = fopen('php://output', 'w');

// BOM para que Excel respete las tildes
fwrite($salida, "\xEF\xBB\xBF");

fputcsv($salida, [
    'Venta', 'Fecha', 'Hora', 'Mesa', 'Cajero', 'Subtotal',
    'Descuento', 'Total', 'Estado', 'Productos', 'Pagos'
]);

foreach ($ventas as $venta) {

    $pedidoId = (int) $venta['pedido_id'];
    $ventaId  = (int) $venta['id'];

    fputcsv($salida, [
        $ventaId,
        date('d/m/Y', strtotime($venta['fecha'])),
        date('H:i', strtotime($venta['fecha'])),
        'Mesa ' . $venta['mesa'],
        $venta['cajero'] ?: '-',
        number_format((float) $venta['subtotal'], 2),
        number_format((float) $venta['descuento'], 2),
        number_format((float) $venta['total'], 2),
        $venta['estado'],
        $porPedido[$pedidoId] ?? '',
        implode(' / ', $porVenta[$ventaId] ?? [])
    ]);
}

/** Resumen de gastos: egresos y gastos del mismo período */
$gastos = [];

foreach (['egresos' => 'Egreso', 'gastos' => 'Gasto'] as $tabla => $etiqueta) {

    try {
        $stmt = $db->prepare(
            "SELECT fecha,
                    COALESCE(" . ($tabla === 'gastos' ? 'descripcion' : 'concepto') . ", '-') AS concepto,
                    monto
             FROM $tabla
             WHERE DATE(fecha) BETWEEN :desde AND :hasta
             ORDER BY fecha"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);

        foreach ($stmt->fetchAll() as $fila) {
            $gastos[] = [$etiqueta, $fila['concepto'], (float) $fila['monto'], $fila['fecha']];
        }

    } catch (PDOException $e) {
        // Si la tabla no existe, el reporte sale solo con ventas
    }
}

if ($gastos) {

    fputcsv($salida, []);
    fputcsv($salida, ['GASTOS DEL PERIODO']);
    fputcsv($salida, ['Tipo', 'Concepto', 'Monto', 'Fecha']);

    foreach ($gastos as $gasto) {
        fputcsv($salida, [
            $gasto[0],
            $gasto[1],
            number_format($gasto[2], 2),
            date('d/m/Y', strtotime($gasto[3]))
        ]);
    }
}

/** Cierre: ventas, gastos y ganancia del período */
$totalVentas = array_sum(array_column($ventas, 'total'));
$totalGastos = array_sum(array_column($gastos, 2));

fputcsv($salida, []);
fputcsv($salida, ['RESUMEN']);
fputcsv($salida, ['Ventas', 'Gastos', 'Ganancia']);
fputcsv($salida, [
    number_format($totalVentas, 2),
    number_format($totalGastos, 2),
    number_format($totalVentas - $totalGastos, 2)
]);

fclose($salida);
exit;
