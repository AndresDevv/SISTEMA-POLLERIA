<?php

/**
 * Exporta las ventas del período a CSV.
 *
 * Se invoca con index.php?page=reportes/exportar&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
 *
 * Son tres consultas separadas a propósito: unir detalle_pedidos con pagos en
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

if (!$ventas) {
    // Encabezados y nada más
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_' . $desde . '_a_' . $hasta . '.csv"');
    $salida = fopen('php://output', 'w');
    fwrite($salida, "\xEF\xBB\xBF");
    fputcsv($salida, ['Venta', 'Fecha', 'Hora', 'Mesa', 'Cajero', 'Subtotal', 'Descuento', 'Total', 'Estado', 'Productos', 'Pagos']);
    fclose($salida);
    exit;
}

$ids = array_column($ventas, 'id');
$marcadores = implode(',', array_fill(0, count($ids), '?'));

/** Productos por venta */
$stmt = $db->prepare(
    "SELECT d.pedido_id, GROUP_CONCAT(CONCAT(d.cantidad, ' x ', pr.nombre) SEPARATOR ' | ') AS productos
     FROM detalle_pedidos d
     INNER JOIN productos pr ON pr.id = d.producto_id
     WHERE d.pedido_id IN ($marcadores)
     GROUP BY d.pedido_id"
);
$stmt->execute($ids);
$porPedido = [];

foreach ($stmt->fetchAll() as $fila) {
    $porPedido[$fila['pedido_id']] = $fila['productos'];
}

/** Pagos por venta, agrupados por método */
$stmt = $db->prepare(
    "SELECT pg.venta_id, m.nombre AS metodo, SUM(pg.monto) AS total
     FROM pagos pg
     INNER JOIN metodos_pago m ON m.id = pg.metodo_pago_id
     WHERE pg.venta_id IN ($marcadores)
     GROUP BY pg.venta_id, m.nombre
     ORDER BY m.nombre"
);
$stmt->execute($ids);
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

fclose($salida);
exit;
