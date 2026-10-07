<?php
/**
 * Vista: inventario (productos, categorías y movimientos).
 */

require_once APP_ROOT . '/models/Gestion.php';
require_once APP_ROOT . '/models/Inventario.php';

$inventarioModel = new Inventario();

$recurso = $_GET['tab'] ?? 'productos';

if (!in_array($recurso, ['productos', 'categorias', 'movimientos'], true)) {
    $recurso = 'productos';
}

$paginaActual = $recurso === 'movimientos' ? 'inventario/movimientos' : 'inventario';
$icono = 'fa fa-cube';
$titulo = 'Inventario';
$subtitulo = 'Productos, stock y movimientos del almacén';

$movimientos = $inventarioModel->movimientos();

/**
 * Pestañas del módulo, compartidas por el CRUD genérico y la vista de
 * movimientos para que no se dupliquen.
 */
$pestanasInventario = static function (string $actual) use ($movimientos): void {
    ?>
    <div class="lg-segmentos mb-3">
        <a href="<?= url('inventario') ?>" class="lg-segmento<?= $actual === 'productos' ? ' is-activo' : '' ?>">
            <i class="fa fa-cube"></i> Productos
        </a>
        <a href="<?= url('inventario') ?>&tab=categorias" class="lg-segmento<?= $actual === 'categorias' ? ' is-activo' : '' ?>">
            <i class="fa fa-tags"></i> Categorías
        </a>
        <a href="<?= url('inventario/movimientos') ?>" class="lg-segmento<?= $actual === 'movimientos' ? ' is-activo' : '' ?>">
            <i class="fa fa-exchange-alt"></i> Movimientos
            <span class="lg-segmento-badge"><?= count($movimientos) ?></span>
        </a>
    </div>
    <?php
};

if ($recurso !== 'movimientos'):

    $pestanaActual  = $recurso;
    $recursoCrud    = $recurso;
    $icono          = 'fa fa-cube';
    $titulo         = 'Inventario';
    $subtitulo      = 'Productos y stock del almacén';

    $recursosExtra = [
        ['clave' => 'productos',   'recurso' => $recursoCrud, 'pagina' => 'inventario',
         'titulo' => 'Productos',   'icono' => 'fa fa-cube'],
        ['clave' => 'categorias',  'recurso' => 'categorias', 'pagina' => 'inventario&tab=categorias',
         'titulo' => 'Categorías',  'icono' => 'fa fa-tags'],
        ['clave' => 'movimientos', 'recurso' => $recursoCrud, 'pagina' => 'inventario/movimientos',
         'titulo' => 'Movimientos', 'icono' => 'fa fa-exchange-alt']
    ];

    $recurso = $recursoCrud;

    require APP_ROOT . '/views/admin/gestion.php';

    return;

endif;
?>

<?php $pestanasInventario($recurso); ?>

<?php encabezadoPagina($icono, $titulo, $subtitulo); ?>

<?php if ($recurso === 'movimientos'): ?>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-exchange-alt"></i></span>
            <div>
                <h2 class="lg-card-title">Movimientos de inventario</h2>
                <p class="lg-card-subtitle">Entradas, salidas y ajustes de stock</p>
            </div>
        </div>

        <?php if (!$movimientos): ?>
            <div class="lg-empty">
                <i class="fa fa-exchange-alt"></i>
                <p class="mb-0">A&uacute;n no hay movimientos registrados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th style="text-align:right;">Cantidad</th>
                            <th style="text-align:right;">Stock anterior</th>
                            <th style="text-align:right;">Stock nuevo</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <?php
                            $esEntrada = $m['tipo'] === 'entrada';
                            $esAjuste  = $m['tipo'] === 'ajuste';
                            ?>
                            <tr>
                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($m['producto']) ?></td>
                                <td>
                                    <span class="lg-pill <?= $esEntrada ? 'lg-pill--verde' : ($esAjuste ? 'lg-pill--ambar' : 'lg-pill--rojo') ?>">
                                        <?= htmlspecialchars(ucfirst($m['tipo'])) ?>
                                    </span>
                                </td>
                                <td class="num" style="text-align:right;color:<?= $esEntrada ? '#2E9E4B' : ($esAjuste ? '#111' : '#EF4444') ?>;">
                                    <?= $esSalida = ($m['tipo'] === 'salida') ? '-' : '+' ?><?= unidades($m['cantidad']) ?>
                                </td>
                                <td class="num text-muted" style="text-align:right;"><?= unidades($m['stock_anterior']) ?></td>
                                <td class="num" style="text-align:right;font-weight:600;"><?= unidades($m['stock_nuevo']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($m['motivo'] ?? '-') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($m['usuario'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>