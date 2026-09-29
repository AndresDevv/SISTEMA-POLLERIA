<?php
/**
 * Vista: finanzas.
 */

$categorias = [
    'Productos' => 0.0,
    'Bebidas'   => 0.0,
    'Postres'   => 0.0,
    'Servicios' => 0.0,
    'Otros'     => 0.0
];
?>

<?php
encabezadoPagina(
    'fa fa-folder-open',
    'Finanzas',
    'Ingresos, egresos y utilidad del negocio'
);
?>

<!-- Resumen -->
<div class="lg-grid-3 mb-3">

    <?php
    $tarjetas = [
        ['Ingresos del mes', 0.0, 'is-green', 'fa fa-arrow-up'],
        ['Egresos del mes',  0.0, 'is-red',   'fa fa-arrow-down'],
        ['Utilidad del mes', 0.0, 'is-green', 'fa fa-line-chart']
    ];
    ?>

    <?php foreach ($tarjetas as $t): ?>
        <div class="lg-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="lg-stat-label mb-1"><?= htmlspecialchars($t[0]) ?></p>
                    <div class="lg-stat-value <?= $t[2] ?>" style="font-size:1.7rem;">
                        <?= soles((float) $t[1]) ?>
                    </div>
                </div>
                <span class="lg-card-icon"><i class="<?= htmlspecialchars($t[3]) ?>"></i></span>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<div class="lg-grid-2">

    <!-- Ingresos por categoría -->
    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-bar-chart"></i></span>
            <div>
                <h2 class="lg-card-title">Ingresos por categor&iacute;a</h2>
                <p class="lg-card-subtitle">Distribuci&oacute;n de ventas del d&iacute;a</p>
            </div>
        </div>

        <div class="lg-rows">
            <?php foreach ($categorias as $categoria => $monto): ?>
                <div class="lg-row">
                    <span class="lg-row-label"><?= htmlspecialchars($categoria) ?></span>
                    <span class="lg-row-value"><?= soles((float) $monto) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="lg-total-box mt-3">
            <div class="lg-row">
                <span class="lg-row-label">Total</span>
                <span class="lg-row-value"><?= soles(0.0) ?></span>
            </div>
        </div>
    </div>

    <!-- Métodos de pago -->
    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-credit-card"></i></span>
            <div>
                <h2 class="lg-card-title">M&eacute;todos de pago</h2>
                <p class="lg-card-subtitle">Cobros del d&iacute;a</p>
            </div>
        </div>

        <div class="lg-rows">
            <?php foreach (['Efectivo' => 0.0, 'Yape' => 0.0, 'Plin' => 0.0, 'Tarjeta' => 0.0] as $medio => $monto): ?>
                <div class="lg-row">
                    <span class="lg-row-label"><?= htmlspecialchars($medio) ?></span>
                    <span class="lg-row-value"><?= soles((float) $monto) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
