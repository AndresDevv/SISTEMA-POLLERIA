<?php
/**
 * Vista: ventas — replica el diseño de Figma con datos reales.
 */

require_once __DIR__ . '/../../models/Venta.php';
require_once __DIR__ . '/../../models/Pedido.php';

$ventaModel   = new Venta();
$pedidoModel  = new Pedido();

$ventasHoy    = $ventaModel->delDia();
$pedidosHoy   = $pedidoModel->listar('hoy');
$porCategoria = $ventaModel->porCategoria();
$totalHoy     = $ventaModel->totalHoy();
$ganancia     = $totalHoy;

/**
 * Agrupa el total de los pedidos del día por categoría de producto.
 */
$porCategoriaPedidos = $pedidoModel->porCategoriaDia();

$clientes = count($ventasHoy);
$resumen  = $porCategoriaPedidos;
?>

<?php encabezadoPagina(
    'fa fa-bar-chart',
    'Ventas',
    'Consulta las ventas del día, revisa el historial y gestiona la caja.'
); ?>

<div class="lg-grid-3">

    <!-- ================= Ventas del día ================= -->
    <div class="lg-card">

        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-line-chart"></i></span>
            <div>
                <h2 class="lg-card-title">Ventas del día</h2>
            </div>
        </div>

        <div class="text-center">
            <div class="lg-stat-value is-green"><?= soles($totalHoy) ?></div>
        </div>

        <hr style="border-color:#EFEFEF;">

        <div class="d-flex justify-content-around text-center mb-4">
            <div>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fa fa-clipboard text-muted"></i>
                    <span style="font-size:1.25rem;font-weight:600;"><?= count($pedidosHoy) ?></span>
                </div>
                <small class="text-muted">Pedidos</small>
            </div>

            <div>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fa fa-users text-muted"></i>
                    <span style="font-size:1.25rem;font-weight:600;"><?= $clientes ?></span>
                </div>
                <small class="text-muted">Clientes</small>
            </div>
        </div>

        <p class="lg-subtitle-block">Resumen de ventas del d&iacute;a</p>

        <div class="lg-rows">
            <?php if ($resumen): ?>
                <?php foreach ($resumen as $fila): ?>
                    <div class="lg-row">
                        <span class="lg-row-label"><?= htmlspecialchars($fila['categoria']) ?></span>
                        <span class="lg-row-value"><?= soles((float) $fila['total']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="lg-row">
                    <span class="lg-row-label lg-muted">Sin ventas registradas hoy</span>
                    <span class="lg-row-value lg-muted"><?= soles(0) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="lg-total-box mt-3">
            <div class="lg-row">
                <span class="lg-row-label">Total del d&iacute;a</span>
                <span class="lg-row-value"><?= soles($totalHoy) ?></span>
            </div>
        </div>

    </div>

    <!-- ================= Historial ================= -->
    <div class="lg-card">

        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-clipboard"></i></span>
            <div>
                <h2 class="lg-card-title">Historial</h2>
                <p class="lg-card-subtitle">&Uacute;ltimas ventas del d&iacute;a</p>
            </div>
        </div>

        <?php if (!$ventasHoy): ?>

            <div class="lg-empty">
                <i class="fa fa-receipt"></i>
                <p class="mb-0">No hay ventas registradas hoy.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="lg-table lg-table--compact">
                    <thead>
                        <tr>
                            <th>N&ordm;</th>
                            <th>Hora</th>
                            <th>Mesa</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventasHoy as $i => $venta): ?>
                            <tr>
                                <td style="font-weight:600;">
                                    <?= str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td class="text-muted"><?= date('H:i', strtotime($venta['fecha'])) ?></td>
                                <td class="text-muted">Mesa <?= (int) $venta['mesa_numero'] ?></td>
                                <td class="num"><?= soles((float) $venta['total']) ?></td>
                                <td>
                                    <span class="lg-pill <?= $venta['estado'] === 'pagada' ? 'lg-pill--verde' : 'lg-pill--rojo' ?>">
                                        <?= htmlspecialchars(ucfirst($venta['estado'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

    <!-- ================= Caja ================= -->
    <div class="lg-card">

        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-money"></i></span>
            <div>
                <h2 class="lg-card-title">Caja</h2>
                <p class="lg-card-subtitle">Control de ingresos del d&iacute;a</p>
            </div>
        </div>

        <div class="lg-total-box">
            <span class="lg-row-label">Total en caja</span>
            <div class="lg-stat-value is-red" style="font-size:1.6rem;"><?= soles($totalHoy) ?></div>
        </div>

        <div class="lg-rows">
            <?php if ($porCategoria): ?>
                <?php foreach ($porCategoria as $fila): ?>
                    <div class="lg-row">
                        <span class="lg-row-label"><?= htmlspecialchars($fila['categoria']) ?></span>
                        <span class="lg-row-value"><?= soles((float) $fila['total']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="lg-row">
                    <span class="lg-row-label lg-muted">Sin ingresos por categor&iacute;a</span>
                    <span class="lg-row-value lg-muted"><?= soles(0) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <p class="lg-subtitle-block">Movimientos de caja</p>

        <div class="lg-movement">
            <span class="lg-dot lg-dot--in"><i class="fa fa-arrow-up"></i></span>
            <span class="lg-movement-label">Ingresos del d&iacute;a</span>
            <span class="lg-movement-value"><?= soles($totalHoy) ?></span>
        </div>

        <div class="lg-movement">
            <span class="lg-dot lg-dot--out"><i class="fa fa-arrow-down"></i></span>
            <span class="lg-movement-label">Gastos del d&iacute;a</span>
            <span class="lg-movement-value"><?= soles(0) ?></span>
        </div>

    </div>

</div>
