<?php
/**
 * Vista: reportes.
 * Muestra datos reales y permite exportarlos a CSV.
 */

require_once APP_ROOT . '/models/Venta.php';
require_once APP_ROOT . '/models/Pedido.php';
require_once APP_ROOT . '/models/Producto.php';
require_once __DIR__ . '/../../config/database.php';

$ventaModel   = new Venta();
$pedidoModel  = new Pedido();
$productoModel = new Producto();
$db = conexionDB();

$hoy = date('Y-m-d');
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-6 days'));
$hasta = $_GET['hasta'] ?? $hoy;

if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

/** Ventas del período */
$stmt = $db->prepare(
    "SELECT DATE(v.fecha) AS dia, COUNT(*) AS ventas, COALESCE(SUM(v.total), 0) AS total
     FROM ventas v
     WHERE v.estado = 'pagada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
     GROUP BY DATE(v.fecha)
     ORDER BY dia DESC"
);
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$porDia = $stmt->fetchAll();

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

/** Ventas por método de pago */
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

/** Platos más rentedables */
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

$totalPeriodo = array_sum(array_column($porDia, 'total'));
?>

<?php
encabezadoPagina(
    'fa fa-file-text',
    'Reportes',
    'Del ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta))
);
?>

<!-- Filtros -->
<form class="lg-card mb-3" method="GET" action="<?= BASE_URL ?>">
    <input type="hidden" name="page" value="reportes">
    <div class="lg-filters">
        <div class="lg-field">
            <label class="lg-label" for="repDesde">Desde</label>
            <input type="date" id="repDesde" name="desde" class="lg-input" value="<?= htmlspecialchars($desde) ?>">
        </div>

        <div class="lg-field">
            <label class="lg-label" for="repHasta">Hasta</label>
            <input type="date" id="repHasta" name="hasta" class="lg-input" value="<?= htmlspecialchars($hasta) ?>">
        </div>

        <div class="lg-field" style="flex:0 0 auto;">
            <button type="submit" class="lg-btn lg-btn--primary">
                <i class="fa fa-filter"></i> Aplicar
            </button>
        </div>

        <div class="lg-field" style="flex:0 0 auto;">
            <a class="lg-btn lg-btn--ghost" href="<?= BASE_URL ?>?page=reportes/exportar&amp;desde=<?= htmlspecialchars($desde) ?>&amp;hasta=<?= htmlspecialchars($hasta) ?>">
                <i class="fa fa-download"></i> Exportar CSV
            </a>
        </div>
    </div>
</form>

<!-- Resumen -->
<div class="lg-grid-3 mb-3">
    <div class="lg-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="lg-stat-label mb-1">Vendido en el per&iacute;odo</p>
                <div class="lg-stat-value is-green" style="font-size:1.6rem;"><?= soles((float) $totalPeriodo) ?></div>
            </div>
            <span class="lg-card-icon"><i class="fa fa-line-chart"></i></span>
        </div>
    </div>

    <div class="lg-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="lg-stat-label mb-1">D&iacute;as con ventas</p>
                <div class="lg-stat-value is-dark" style="font-size:1.6rem;"><?= count($porDia) ?></div>
            </div>
            <span class="lg-card-icon"><i class="fa fa-calendar"></i></span>
        </div>
    </div>

    <div class="lg-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="lg-stat-label mb-1">Productos con stock bajo</p>
                <div class="lg-stat-value is-red" style="font-size:1.6rem;"><?= $productoModel->stockBajo() ?></div>
            </div>
            <span class="lg-card-icon"><i class="fa fa-exclamation-triangle"></i></span>
        </div>
    </div>
</div>

<div class="lg-grid-2">

    <!-- Ventas por día -->
    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-bar-chart"></i></span>
            <h2 class="lg-card-title">Ventas por d&iacute;a</h2>
        </div>

        <?php if (!$porDia): ?>
            <div class="lg-empty"><i class="fa fa-bar-chart"></i><p class="mb-0">Sin ventas en el per&iacute;odo.</p></div>
        <?php else: ?>
            <table class="lg-table">
                <thead>
                    <tr><th>D&iacute;a</th><th>Ventas</th><th style="text-align:right;">Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($porDia as $fila): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($fila['dia'])) ?></td>
                            <td class="num"><?= (int) $fila['ventas'] ?></td>
                            <td class="num" style="text-align:right;font-weight:600;"><?= soles((float) $fila['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Métodos de pago -->
    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-credit-card"></i></span>
            <h2 class="lg-card-title">M&eacute;todos de pago</h2>
        </div>

        <?php if (!$porMetodo): ?>
            <div class="lg-empty"><i class="fa fa-credit-card"></i><p class="mb-0">Sin cobros en el per&iacute;odo.</p></div>
        <?php else: ?>
            <div class="lg-rows">
                <?php foreach ($porMetodo as $fila): ?>
                    <div class="lg-row">
                        <span class="lg-row-label"><?= htmlspecialchars($fila['metodo']) ?></span>
                        <span class="lg-row-value"><?= soles((float) $fila['total']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top productos -->
    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-star"></i></span>
            <h2 class="lg-card-title">Productos m&aacute;s vendidos</h2>
        </div>

        <?php if (!$topProductos): ?>
            <div class="lg-empty"><i class="fa fa-star"></i><p class="mb-0">Sin ventas en el per&iacute;odo.</p></div>
        <?php else: ?>
            <table class="lg-table">
                <thead>
                    <tr><th>Producto</th><th>Unidades</th><th style="text-align:right;">Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($topProductos as $fila): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['nombre']) ?></td>
                            <td class="num"><?= number_format((float) $fila['unidades'], 0) ?></td>
                            <td class="num" style="text-align:right;font-weight:600;"><?= soles((float) $fila['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Mesas más rentables -->
    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-table"></i></span>
            <h2 class="lg-card-title">Mesas m&aacute;s rentables</h2>
        </div>

        <?php if (!$porMesa): ?>
            <div class="lg-empty"><i class="fa fa-table"></i><p class="mb-0">Sin ventas en el per&iacute;odo.</p></div>
        <?php else: ?>
            <table class="lg-table">
                <thead>
                    <tr><th>Mesa</th><th>Ventas</th><th style="text-align:right;">Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($porMesa as $fila): ?>
                        <tr>
                            <td>Mesa <?= (int) $fila['numero'] ?></td>
                            <td class="num"><?= (int) $fila['ventas'] ?></td>
                            <td class="num" style="text-align:right;font-weight:600;"><?= soles((float) $fila['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
