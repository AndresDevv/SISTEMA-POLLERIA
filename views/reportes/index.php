<?php
/**
 * Vista: reportes.
 */

$reportes = [
    ['nombre' => 'Ventas por d&iacute;a',        'descripcion' => 'Detalle de ventas e ingresos del d&iacute;a',      'icono' => 'fa fa-bar-chart'],
    ['nombre' => 'Productos m&aacute;s vendidos', 'descripcion' => 'Ranking de productos por cantidad vendida',            'icono' => 'fa fa-star'],
    ['nombre' => 'Productos menos vendidos',     'descripcion' => 'Productos con menor rotaci&oacute;n',                 'icono' => 'fa fa-arrow-down'],
    ['nombre' => 'P&eacute;rdidas y mermas',      'descripcion' => 'Merma y desperdicio por producto',                     'icono' => 'fa fa-trash'],
    ['nombre' => 'Mesa m&aacute;s rentable',      'descripcion' => 'Ingresos por mesa en el per&iacute;odo',                 'icono' => 'fa fa-table'],
    ['nombre' => 'Turnos de personal',            'descripcion' => 'Horas laboradas y rendimiento por colaborador',        'icono' => 'fa fa-users'],
    ['nombre' => 'Compras a proveedores',         'descripcion' => 'Consolidado de compras por per&iacute;odo',              'icono' => 'fa fa-shopping-cart'],
    ['nombre' => 'M&eacute;todos de pago',         'descripcion' => 'Distribuci&oacute;n de cobros por medio de pago',         'icono' => 'fa fa-credit-card']
];
?>

<?php
encabezadoPagina(
    'fa fa-file-text',
    'Reportes',
    'Genera y descarga los reportes del sistema'
);
?>

<div class="lg-grid-3">

    <?php foreach ($reportes as $r): ?>
        <div class="lg-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="lg-card-icon"><i class="<?= htmlspecialchars($r['icono']) ?>"></i></span>
            </div>

            <h3 style="font-size:1.05rem;font-weight:700;margin:0 0 4px;">
                <?= $r['nombre'] ?>
            </h3>

            <p class="text-muted mb-3" style="font-size:0.86rem;">
                <?= $r['descripcion'] ?>
            </p>

            <div class="d-flex gap-2">
                <button type="button" class="lg-btn lg-btn--sm lg-btn--primary">
                    <i class="fa fa-eye"></i> Ver
                </button>
                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost">
                    <i class="fa fa-download"></i> Excel
                </button>
            </div>
        </div>
    <?php endforeach; ?>

</div>
