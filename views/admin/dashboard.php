<?php
/**
 * Vista: dashboard — replica el diseño de Figma con datos reales.
 */

require_once __DIR__ . '/../../models/Dashboard.php';

$m = Dashboard::metricas();

$mesasTotal   = (int) $m['mesas_total'];
$mesasOcupadas = (int) $m['mesas_ocupadas'];

/**
 * Indicadores del diseño de Figma.
 */
$indicadores = [
    [
        'icono'  => 'fa fa-shopping-cart',
        'titulo' => 'Ventas de hoy',
        'valor'  => soles((float) $m['ventas_hoy']),
        'tono'   => 'is-green'
    ],
    [
        'icono'  => 'fa fa-money',
        'titulo' => 'Egresos de hoy',
        'valor'  => soles((float) $m['egresos_hoy']),
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-line-chart',
        'titulo' => 'Ganancia estimada',
        'valor'  => soles((float) $m['ganancia']),
        'tono'   => 'is-green'
    ],
    [
        'icono'  => 'fa fa-clipboard',
        'titulo' => 'Pedidos realizados',
        'valor'  => (string) $m['pedidos_hoy'],
        'tono'   => 'is-dark'
    ],
    [
        'icono'  => 'fa fa-table',
        'titulo' => 'Mesas ocupadas',
        'valor'  => $mesasOcupadas . ' / ' . $mesasTotal,
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-cube',
        'titulo' => 'Productos con stock bajo',
        'valor'  => (string) $m['stock_bajo'],
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-users',
        'titulo' => 'Personal presente',
        'valor'  => (string) $m['personal'],
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-check-circle',
        'titulo' => 'Mesas del local',
        'valor'  => (string) $mesasTotal,
        'tono'   => 'is-green'
    ]
];
?>

<?php encabezadoPagina('fa fa-home', 'Dashboard', 'Resumen general de hoy'); ?>

<div class="lg-stats">

    <?php foreach ($indicadores as $ind): ?>

        <div class="lg-stat">
            <div class="lg-stat-icon">
                <i class="<?= htmlspecialchars($ind['icono']) ?>"></i>
            </div>

            <div class="lg-stat-label"><?= htmlspecialchars($ind['titulo']) ?></div>

            <div class="lg-stat-value <?= $ind['tono'] ?>">
                <?= htmlspecialchars($ind['valor']) ?>
            </div>
        </div>

    <?php endforeach; ?>

</div>
