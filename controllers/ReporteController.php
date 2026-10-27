<?php

/**
 * Exporta el reporte del período a CSV.
 *
 * Se invoca con index.php?page=reportes/exportar&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
 *
 * El archivo trae las mismas secciones que la pantalla de Reportes, en este
 * orden: resumen, ventas por día, métodos de pago, productos más vendidos,
 * mesas más rentables, ventas por mesero, detalle de ventas y compras.
 *
 * Los importes van sin separador de miles (1234.50 y no 1,234.50) para que
 * Excel los lea como números y se puedan sumar; si llevaran coma, la columna
 * se abriría como texto.
 */

require_once __DIR__ . '/../config/database.php';

$db = conexionDB();

$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $desde = $hasta = date('Y-m-d');
}

if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

/**
 * Escribe una fila en el CSV.
 */
$escribir = static function (array $valores) use (&$salida): void {
    fputcsv($salida, $valores);
};

/**
 * Importe con dos decimales y sin separador de miles.
 */
$importe = static function ($valor): string {
    return number_format((float) $valor, 2, '.', '');
};

/**
 * Cantidad sin decimales de relleno: 3 en vez de 3.00.
 */
$cantidad = static function ($valor): string {
    $texto = number_format((float) $valor, 2, '.', '');

    return rtrim(rtrim($texto, '0'), '.');
};

// --- Datos ---------------------------------------------------------------

/** Detalle de ventas cobradas del período */
$stmt = $db->prepare(
    "SELECT v.id, v.fecha, v.pedido_id, v.subtotal, v.descuento, v.total, v.estado,
            m.numero AS mesa,
            c.nombre AS cajero,
            a.nombre AS mesero
    FROM ventas v
    INNER JOIN pedidos p ON p.id = v.pedido_id
    INNER JOIN mesas m ON m.id = p.mesa_id
    LEFT JOIN usuarios c ON c.id = v.usuario_id
    LEFT JOIN usuarios a ON a.id = p.usuario_id
    WHERE v.estado = 'pagada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
    ORDER BY v.fecha"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$ventas = $stmt->fetchAll();

// Los productos cuelgan del pedido; los pagos, de la venta.
$idsPedido = array_column($ventas, 'pedido_id');
$idsVenta  = array_column($ventas, 'id');

$marcadoresPedido = $idsPedido ? implode(',', array_fill(0, count($idsPedido), '?')) : 'NULL';
$marcadoresVenta  = $idsVenta ? implode(',', array_fill(0, count($idsVenta), '?')) : 'NULL';

/** Productos de cada pedido */
$porPedido = [];

if ($idsPedido) {
    $stmt = $db->prepare(
        "SELECT d.pedido_id,
                GROUP_CONCAT(CONCAT(ROUND(d.cantidad), ' x ', pr.nombre) SEPARATOR ' | ') AS productos
        FROM detalle_pedidos d
        INNER JOIN productos pr ON pr.id = d.producto_id
        WHERE d.pedido_id IN ($marcadoresPedido)
        GROUP BY d.pedido_id"
    );
    $stmt->execute($idsPedido);

    foreach ($stmt->fetchAll() as $fila) {
        $porPedido[$fila['pedido_id']] = $fila['productos'];
    }
}

/** Pagos de cada venta, agrupados por método */
$porVenta = [];

if ($idsVenta) {
    $stmt = $db->prepare(
        "SELECT pg.venta_id, m.nombre AS metodo, SUM(pg.monto) AS total
        FROM pagos pg
        INNER JOIN metodos_pago m ON m.id = pg.metodo_pago_id
        WHERE pg.venta_id IN ($marcadoresVenta)
        GROUP BY pg.venta_id, m.nombre
        ORDER BY m.nombre"
    );
    $stmt->execute($idsVenta);

    foreach ($stmt->fetchAll() as $fila) {
        $porVenta[$fila['venta_id']][] = $fila['metodo'] . ': ' . $importe($fila['total']);
    }
}

/** Ventas por día */
$stmt = $db->prepare(
    "SELECT DATE(v.fecha) AS dia, COUNT(*) AS ventas, COALESCE(SUM(v.total), 0) AS total
    FROM ventas v
    WHERE v.estado = 'pagada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
    GROUP BY DATE(v.fecha)
    ORDER BY dia DESC"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$porDia = $stmt->fetchAll();

/** Compras por día (tabla egresos) */
$gastosPorDia = [];

try {
    $stmt = $db->prepare(
        "SELECT DATE(fecha) AS dia, COALESCE(SUM(monto), 0) AS total
        FROM egresos
        WHERE DATE(fecha) BETWEEN :desde AND :hasta
        GROUP BY DATE(fecha)"
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);

    foreach ($stmt->fetchAll() as $fila) {
        $gastosPorDia[$fila['dia']] = (float) $fila['total'];
    }

} catch (PDOException $e) {
    // Si la tabla no existe, el reporte sale solo con ventas
}

/** Métodos de pago */
$stmt = $db->prepare(
    "SELECT m.nombre AS metodo, COALESCE(SUM(pg.monto), 0) AS total
    FROM pagos pg
    INNER JOIN ventas v ON v.id = pg.venta_id
    INNER JOIN metodos_pago m ON m.id = pg.metodo_pago_id
    WHERE v.estado = 'pagada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
    GROUP BY m.nombre
    ORDER BY total DESC"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$porMetodo = $stmt->fetchAll();

/** Productos más vendidos */
$stmt = $db->prepare(
    "SELECT pr.nombre, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS total
    FROM detalle_pedidos d
    INNER JOIN productos pr ON pr.id = d.producto_id
    INNER JOIN pedidos p ON p.id = d.pedido_id
    WHERE DATE(p.fecha_creacion) BETWEEN :desde AND :hasta
    GROUP BY pr.id, pr.nombre
    ORDER BY unidades DESC
    LIMIT 10"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$topProductos = $stmt->fetchAll();

/** Mesas más rentables */
$stmt = $db->prepare(
    "SELECT m.numero, COALESCE(SUM(v.total), 0) AS total, COUNT(v.id) AS ventas
    FROM ventas v
    INNER JOIN pedidos p ON p.id = v.pedido_id
    INNER JOIN mesas m ON m.id = p.mesa_id
    WHERE v.estado = 'pagada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
    GROUP BY m.id, m.numero
    ORDER BY total DESC
    LIMIT 10"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$porMesa = $stmt->fetchAll();

/** Ventas por mesero (se le atribuye al que registró el pedido) */
$ventasPorMesero = [];

try {
    $stmt = $db->prepare(
        "SELECT u.id, u.nombre, u.usuario,
                COUNT(v.id) AS ventas,
                COALESCE(SUM(v.total), 0) AS total
        FROM usuarios u
        INNER JOIN roles r ON r.id = u.rol_id
        LEFT JOIN pedidos p ON p.usuario_id = u.id
        LEFT JOIN ventas v ON v.pedido_id = p.id
            AND v.estado = 'pagada'
            AND DATE(v.fecha) BETWEEN :desde AND :hasta
        WHERE r.nombre = 'Mesero'
        GROUP BY u.id, u.nombre, u.usuario
        ORDER BY total DESC, u.nombre ASC"
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $ventasPorMesero = $stmt->fetchAll();

} catch (PDOException $e) {
    // Sin roles configurados, la sección no se escribe
}

/** Compras del período, al detalle */
$gastos = [];

try {
    $stmt = $db->prepare(
        "SELECT fecha,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(cantidad, 0), ' ', COALESCE(unidad, ''))), '0 '), '') AS medida,
                concepto,
                monto
        FROM egresos
        WHERE DATE(fecha) BETWEEN :desde AND :hasta
        ORDER BY fecha"
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $gastos = $stmt->fetchAll();

} catch (PDOException $e) {
    // Si la tabla no existe, el reporte sale solo con ventas
}

// --- Salida --------------------------------------------------------------

$totalVentas  = array_sum(array_map(static fn(array $v): float => (float) $v['total'], $ventas));
$totalGastos  = array_sum(array_map(static fn(array $g): float => (float) $g['monto'], $gastos));
$totalTickets = count($ventas);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_' . $desde . '_a_' . $hasta . '.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$salida = fopen('php://output', 'w');

// BOM para que Excel respete las tildes
fwrite($salida, "\xEF\xBB\xBF");

// Encabezado del archivo
$escribir(['REPORTE']);
$escribir(['Desde', date('d/m/Y', strtotime($desde))]);
$escribir(['Hasta', date('d/m/Y', strtotime($hasta))]);
$escribir(['Generado', date('d/m/Y H:i')]);
$escribir([]);

// Resumen
$escribir(['RESUMEN']);
$escribir(['Concepto', 'Valor']);
$escribir(['Ventas del periodo', $importe($totalVentas)]);
$escribir(['Compras del periodo', $importe($totalGastos)]);
$escribir(['Ganancia del periodo', $importe($totalVentas - $totalGastos)]);
$escribir(['Tickets emitidos', $totalTickets]);
$escribir([]);

// Ventas por día
$escribir(['VENTAS POR DIA']);
$escribir(['Dia', 'Tickets', 'Ventas', 'Compras', 'Ganancia']);

$dias = array_unique(array_merge(
    array_column($porDia, 'dia'),
    array_keys($gastosPorDia)
));
rsort($dias);

foreach ($dias as $dia) {
    $diaVentas = 0.0;
    $diaTickets = 0;

    foreach ($porDia as $fila) {
        if ($fila['dia'] === $dia) {
            $diaVentas  = (float) $fila['total'];
            $diaTickets = (int) $fila['ventas'];
            break;
        }
    }

    $diaGastos = $gastosPorDia[$dia] ?? 0.0;

    $escribir([
        date('d/m/Y', strtotime($dia)),
        $diaTickets,
        $importe($diaVentas),
        $importe($diaGastos),
        $importe($diaVentas - $diaGastos)
    ]);
}

$escribir(['TOTAL', $totalTickets, $importe($totalVentas), $importe($totalGastos), $importe($totalVentas - $totalGastos)]);
$escribir([]);

// Métodos de pago
$escribir(['METODOS DE PAGO']);
$escribir(['Metodo', 'Total']);

if ($porMetodo) {
    foreach ($porMetodo as $fila) {
        $escribir([$fila['metodo'], $importe($fila['total'])]);
    }
} else {
    $escribir(['Sin cobros en el periodo', $importe(0)]);
}

$escribir([]);

// Productos más vendidos
$escribir(['PRODUCTOS MAS VENDIDOS']);
$escribir(['Producto', 'Unidades', 'Total']);

if ($topProductos) {
    foreach ($topProductos as $fila) {
        $escribir([
            $fila['nombre'],
            $cantidad($fila['unidades']),
            $importe($fila['total'])
        ]);
    }
} else {
    $escribir(['Sin ventas en el periodo', 0, $importe(0)]);
}

$escribir([]);

// Mesas más rentables
$escribir(['MESAS MAS RENTABLES']);
$escribir(['Mesa', 'Ventas', 'Total']);

if ($porMesa) {
    foreach ($porMesa as $fila) {
        $escribir([
            'Mesa ' . $fila['numero'],
            (int) $fila['ventas'],
            $importe($fila['total'])
        ]);
    }
} else {
    $escribir(['Sin ventas en el periodo', 0, $importe(0)]);
}

$escribir([]);

// Ventas por mesero
$escribir(['VENTAS POR MESERO']);
$escribir(['Mesero', 'Usuario', 'Pedidos cobrados', 'Total vendido', 'Promedio por pedido']);

$totalMeserosVentas  = 0.0;
$totalMeserosTickets = 0;

if ($ventasPorMesero) {
    foreach ($ventasPorMesero as $fila) {
        $filaVentas = (int) $fila['ventas'];
        $filaTotal  = (float) $fila['total'];

        $totalMeserosVentas  += $filaTotal;
        $totalMeserosTickets += $filaVentas;

        $escribir([
            $fila['nombre'],
            $fila['usuario'],
            $filaVentas,
            $importe($filaTotal),
            $importe($filaVentas > 0 ? $filaTotal / $filaVentas : 0)
        ]);
    }

    $escribir([
        'TOTAL',
        '',
        $totalMeserosTickets,
        $importe($totalMeserosVentas),
        $importe($totalMeserosTickets > 0 ? $totalMeserosVentas / $totalMeserosTickets : 0)
    ]);
} else {
    $escribir(['Sin meseros registrados', '', 0, $importe(0), $importe(0)]);
}

$escribir([]);

// Detalle de ventas
$escribir(['DETALLE DE VENTAS']);
$escribir([
    'Venta', 'Fecha', 'Hora', 'Mesa', 'Atendio', 'Cobro',
    'Subtotal', 'Descuento', 'Total', 'Estado', 'Productos', 'Pagos'
]);

foreach ($ventas as $venta) {

    $pedidoId = (int) $venta['pedido_id'];
    $ventaId  = (int) $venta['id'];

    $escribir([
        $ventaId,
        date('d/m/Y', strtotime($venta['fecha'])),
        date('H:i', strtotime($venta['fecha'])),
        'Mesa ' . $venta['mesa'],
        $venta['mesero'] ?: '-',
        $venta['cajero'] ?: '-',
        $importe($venta['subtotal']),
        $importe($venta['descuento']),
        $importe($venta['total']),
        $venta['estado'],
        $porPedido[$pedidoId] ?? '',
        implode(' / ', $porVenta[$ventaId] ?? [])
    ]);
}

$escribir(['TOTAL', '', '', '', '', '', '', '', $importe($totalVentas), '', '', '']);
$escribir([]);

// Compras
$escribir(['COMPRAS DEL PERIODO']);
$escribir(['Fecha', 'Compra', 'Monto']);

if ($gastos) {
    foreach ($gastos as $fila) {
        $compra = (string) $fila['concepto'];

        if ($fila['medida'] !== '') {
            $compra .= ' x' . $fila['medida'];
        }

        $escribir([
            date('d/m/Y', strtotime($fila['fecha'])),
            $compra,
            $importe($fila['monto'])
        ]);
    }

    $escribir(['TOTAL', '', $importe($totalGastos)]);
} else {
    $escribir(['Sin compras en el periodo', '', $importe(0)]);
}

fclose($salida);
exit;
