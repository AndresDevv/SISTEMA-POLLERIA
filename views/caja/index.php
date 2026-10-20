<?php
/**
 * Vista: Caja — el mostrador donde el cajero cobra las mesas.
 *
 * Muestra, mesa por mesa, cuánto se debe y desde cuándo. Al tocar una mesa
 * se abre el detalle de la cuenta y desde ahí se cobra. El mesero ya no
 * cobra: esta pantalla es del rol Cajero (y del administrador).
 *
 * Variables esperadas: ninguna. La vista arma sus propios modelos.
 */

require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Venta.php';

$pedidoModel = new Pedido();
$ventaModel  = new Venta();

$cuentas      = $pedidoModel->cuentasPorMesa();
$metodosPago  = $ventaModel->metodosPago();

// Búsqueda por número de mesa. Se filtra aquí y no en el navegador para
// que el listado y el total del mostrador siempre coincidan.
$buscar = trim((string) ($_GET['buscar'] ?? ''));

if ($buscar !== '') {
    $cuentas = array_values(array_filter(
        $cuentas,
        static fn (array $c): bool => str_contains((string) $c['mesa_numero'], $buscar)
    ));
}

$totalPendiente = 0.0;

foreach ($cuentas as $cuenta) {
    $totalPendiente += (float) $cuenta['total'];
}

// Cuánto se cobró hoy, para tener la referencia a mano en el mostrador.
$cobradoHoy = $ventaModel->totalHoy();

// Paginación: nunca se sabe cuántas mesas pueden quedar con cuenta abierta.
$pagina = paginar($cuentas, 12);
?>

<?php encabezadoPagina(
    'fa fa-money',
    'Caja',
    'Revisa lo que debe cada mesa y cobra la cuenta completa'
); ?>

<!-- Resumen del mostrador -->
<div class="lg-grid-3 lg-caja-resumen mb-3">

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-table"></i></span>
            <div>
                <h2 class="lg-card-title">Mesas por cobrar</h2>
                <p class="lg-card-subtitle">Cuentas abiertas en este momento</p>
            </div>
        </div>

        <div class="lg-total-box">
            <span class="lg-row-label">Cuentas abiertas</span>
            <div class="lg-stat-value" style="font-size:1.7rem;"><?= count($cuentas) ?></div>
        </div>
    </div>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-hourglass-half"></i></span>
            <div>
                <h2 class="lg-card-title">Por cobrar</h2>
                <p class="lg-card-subtitle">Total que deben las mesas</p>
            </div>
        </div>

        <div class="lg-total-box">
            <span class="lg-row-label">Total pendiente</span>
            <div class="lg-stat-value is-red" style="font-size:1.7rem;"><?= soles($totalPendiente) ?></div>
        </div>
    </div>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-check-circle"></i></span>
            <div>
                <h2 class="lg-card-title">Cobrado hoy</h2>
                <p class="lg-card-subtitle">Lo que ya entró a caja</p>
            </div>
        </div>

        <div class="lg-total-box">
            <span class="lg-row-label">Total del día</span>
            <div class="lg-stat-value is-green" style="font-size:1.7rem;"><?= soles($cobradoHoy) ?></div>
        </div>
    </div>

</div>

<div class="lg-card">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-money"></i></span>
        <div>
            <h2 class="lg-card-title">Mesas con cuenta abierta</h2>
            <p class="lg-card-subtitle">
                <?= $pagina['total'] ?> mesa(s) por cobrar.
                Toca una mesa para ver la cuenta y cobrarla.
            </p>
        </div>
    </div>

    <?php if (!$cuentas): ?>

        <div class="lg-empty">
            <i class="fa fa-smile-o"></i>
            <p class="mb-0">No hay mesas con cuentas por cobrar. Todo al día.</p>
        </div>

    <?php else: ?>

        <form class="input-group mb-3 lg-buscar"
              data-buscar-url="<?= BASE_URL ?>?page=caja">
            <input type="search"
                   class="lg-input"
                   name="buscar"
                   placeholder="Buscar mesa por número..."
                   value="<?= htmlspecialchars($buscar) ?>"
                   autocomplete="off"
                   aria-label="Buscar">
            <span class="input-group-btn">
                <button type="submit" class="lg-btn lg-btn--primary" title="Buscar">
                    <i class="fa fa-search"></i>
                </button>
            </span>
        </form>

        <div class="table-responsive">
            <table class="lg-table lg-table--caja">
                <thead>
                    <tr>
                        <th>Mesa</th>
                        <th>Pedidos</th>
                        <th>Desde</th>
                        <th>Espera</th>
                        <th>Total a cobrar</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagina['filas'] as $cuenta): ?>
                        <?php
                        // Minutos que lleva abierta la cuenta más antigua de
                        // la mesa: es la que más rato espera el cobro.
                        $minutos = (int) ((time() - strtotime($cuenta['desde'])) / 60);
                        ?>
                        <tr>
                            <td data-label="Mesa" style="font-weight:600;">
                                Mesa <?= (int) $cuenta['mesa_numero'] ?>
                            </td>
                            <td data-label="Pedidos" class="num"><?= (int) $cuenta['pedidos'] ?></td>
                            <td data-label="Desde" class="text-muted">
                                <?= date('d/m H:i', strtotime($cuenta['desde'])) ?>
                            </td>
                            <td data-label="Espera">
                                <span class="lg-pill <?= $minutos >= 60 ? 'lg-pill--ambar' : 'lg-pill--pizarra' ?>">
                                    <?= $minutos ?> min
                                </span>
                            </td>
                            <td data-label="Total a cobrar" class="num" style="font-weight:700;">
                                <?= soles((float) $cuenta['total']) ?>
                            </td>
                            <td data-label="Acciones" class="lg-td-acciones" style="text-align:right;">
                                <button type="button"
                                        class="lg-btn lg-btn--sm lg-btn--verde js-abrir-caja"
                                        data-mesa-id="<?= (int) $cuenta['mesa_id'] ?>"
                                        data-mesa="Mesa <?= (int) $cuenta['mesa_numero'] ?>">
                                    <i class="fa fa-money"></i> Cobrar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php barraPaginacion($pagina,
            'Mesas ' . $pagina['desde'] . '-' . $pagina['hasta'] . ' de ' . $pagina['total']); ?>

    <?php endif; ?>
</div>

<div class="lg-info mt-3">
    <i class="fa fa-info-circle"></i>
    <span>
        Una mesa se cobra completa: al confirmar el pago quedan pagados todos
        sus pedidos y la mesa vuelve a quedar libre.
    </span>
</div>

<?php
// Modal de cobro exclusivo de Caja: muestra la cuenta y calcula el vuelto.
require APP_ROOT . '/views/partials/modal_caja.php';
?>
