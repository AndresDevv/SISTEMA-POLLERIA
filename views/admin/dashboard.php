<?php
/**
 * Vista: dashboard — replica el diseño de Figma con datos reales.
 */

require_once __DIR__ . '/../../models/Dashboard.php';
require_once __DIR__ . '/../../models/Venta.php';

$db = conexionDB();

/**
 * Fecha que se está mirando.
 *
 * Las 12 tarjetas son datos diarios: por defecto muestran HOY, pero se puede
 * elegir cualquier día pasado. Los datos no se pierden: viven en ventas,
 * pagos, egresos y pedidos, así que el historial se reconstruye desde la base.
 */
$fecha     = $_GET['fecha'] ?? date('Y-m-d');
$esHoy     = $fecha === date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
    $esHoy = true;
}

$m = Dashboard::metricas($fecha);
$metodosPago = (new Venta())->porMetodoPago($fecha);

/** Días con actividad, para el selector */
$stmt = $db->query(
    "SELECT DISTINCT DATE(fecha) AS dia FROM (
        SELECT fecha FROM ventas
        UNION SELECT fecha FROM egresos
        UNION SELECT fecha FROM ingresos
        UNION SELECT fecha_creacion AS fecha FROM pedidos
     ) d ORDER BY dia DESC LIMIT 30"
);
$fechasConDatos = $stmt->fetchAll(PDO::FETCH_COLUMN);

$mesasTotal    = (int) $m['mesas_total'];
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
        'titulo' => 'Personal Presente',
        'valor'  => (string) $m['personal'] . ' / ' . (int) $m['personal'],
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-check-circle',
        'titulo' => 'Mesas del local',
        'valor'  => (string) $mesasTotal,
        'tono'   => 'is-green'
    ],
    [
        'icono'  => 'fa fa-credit-card',
        'titulo' => 'Efectivo',
        'valor'  => soles((float) ($metodosPago[0]['total'] ?? 0)),
        'tono'   => 'is-green'
    ],
    [
        'icono'  => 'fa fa-mobile',
        'titulo' => 'Yape',
        'valor'  => soles((float) ($metodosPago[1]['total'] ?? 0)),
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-mobile',
        'titulo' => 'Plin',
        'valor'  => soles((float) ($metodosPago[2]['total'] ?? 0)),
        'tono'   => 'is-red'
    ],
    [
        'icono'  => 'fa fa-credit-card',
        'titulo' => 'Tarjeta',
        'valor'  => soles((float) ($metodosPago[3]['total'] ?? 0)),
        'tono'   => 'is-green'
    ]
];
?>

<?php encabezadoPagina(
    'fa fa-home',
    'Dashboard',
    $esHoy ? 'Resumen general de hoy' : 'Resumen del ' . date('d/m/Y', strtotime($fecha))
); ?>

<!-- Selector de fecha: los indicadores son diarios pero el historial se conserva -->
<form method="GET" action="<?= BASE_URL ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="hidden" name="page" value="dashboard">

    <label class="lg-label mb-0" for="dashFecha">Fecha</label>

    <input type="date" id="dashFecha" name="fecha" class="lg-input"
           style="width:auto;min-height:42px;" value="<?= htmlspecialchars($fecha) ?>"
           max="<?= date('Y-m-d') ?>">

    <?php if (!$esHoy): ?>
        <a href="<?= BASE_URL ?>?page=dashboard" class="lg-btn lg-btn--ghost lg-btn--sm">Hoy</a>
    <?php endif; ?>

    <button type="submit" class="lg-btn lg-btn--primary lg-btn--sm">
        <i class="fa fa-filter"></i> Ver
    </button>

    <?php if (!empty($fechasConDatos)): ?>
        <span class="lg-muted ms-2" style="font-size:0.8rem;">
            Días con actividad:
            <?php foreach (array_slice($fechasConDatos, 0, 5) as $dia): ?>
                <a href="?page=dashboard&amp;fecha=<?= htmlspecialchars($dia) ?>"
                   style="text-decoration:underline;<?= $dia === $fecha ? 'font-weight:700;' : '' ?>">
                    <?= date('d/m', strtotime($dia)) ?>
                </a>
            <?php endforeach; ?>
        </span>
    <?php endif; ?>
</form>

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
