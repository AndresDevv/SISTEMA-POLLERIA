<?php
/**
 * Vista: mesas y pedidos — replica el diseño de Figma (responsive tablet).
 *
 * Tres paneles conmutables:
 *   - Ver mesas   : cuadrícula de mesas
 *   - Ver pedidos : pedidos registrados
 *   - Ver ventas  : ventas del día
 */

require_once __DIR__ . '/../../models/Mesa.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Venta.php';
require_once __DIR__ . '/../../models/Producto.php';

$mesaModel    = new Mesa();
$pedidoModel  = new Pedido();
$ventaModel   = new Venta();
$productoModel = new Producto();

$mesas      = $mesaModel->listar();
$pedidosHoy = $pedidoModel->listar('hoy');
$ventas     = $ventaModel->delDia();
$productos  = $productoModel->listar();
$metodosPago = $ventaModel->metodosPago();

// Paginación de la lista de ventas del día
$pagVentas = paginar($ventas, 15);

/**
 * Pedidos a mostrar, sin repetir.
 *
 * Tres fuentes: los del día, los que siguen en cocina, y los que están sin
 * cobrar de cualquier fecha. La tercera es la importante: un pedido servido
 * ya no está activo, así que sin ella un cobro olvidado se quedaba invisible
 * y su mesa bloqueada para siempre.
 */
$pedidos = [];
$vistos  = [];

foreach (array_merge(
    $pedidosHoy,
    $pedidoModel->listar('activos'),
    $pedidoModel->sinCobrar()
) as $pedido) {
    $clave = (int) $pedido['id'];

    if (isset($vistos[$clave])) {
        continue;
    }

    $vistos[$clave] = true;
    $pedido['sin_cobrar'] = !$ventaModel->yaCobrado($clave);
    $pedidos[] = $pedido;
}

/**
 * Cobros que se quedaron pendientes de otro día.
 *
 * Son los que mantienen una mesa ocupada y los que más fácil se pierden de
 * vista, así que se avisa arriba y se marcan fila por fila.
 */
$hoy = date('Y-m-d');

$cobrosOlvidados = array_filter($pedidos, static function (array $p) use ($hoy): bool {
    return !empty($p['sin_cobrar'])
        && $p['estado'] === 'entregado'
        && date('Y-m-d', strtotime($p['fecha_creacion'])) !== $hoy;
});

/**
 * Cuántos pedidos hay sin aceptar en todo el salón.
 *
 * Es el número que va en la pestaña "Ver mesas" del usuario de cocina y el
 * que usa para avisar con la alarma cuando llega uno nuevo.
 */
$pedidosPendientesCocina = count(array_filter(
    $pedidos,
    static fn (array $p): bool => $p['estado'] === 'pendiente'
));

// Ids y momento de los pedidos sin aceptar al cargar la página. Sirven para
// detectar lo que entre después: un id nuevo es un pedido nuevo, y una fecha
// distinta es que le agregaron productos a uno que ya estaba en cocina.
$idsPendientesIniciales = array_values(array_map(
    static fn (array $p): array => [
        'id'   => (int) $p['id'],
        'alta' => (string) ($p['fecha_actualizacion'] ?? ''),
    ],
    array_filter($pedidos, static fn (array $p): bool => $p['estado'] === 'pendiente')
));

/** ¿Qué panel se muestra? */
$panelActual = $_GET['panel'] ?? 'mesas';

if (!in_array($panelActual, ['mesas', 'pedidos', 'cocina', 'ventas'], true)) {
    $panelActual = 'mesas';
}

// El panel de cocina es de quien puede cambiar el estado de un pedido
// (aceptar, marcar listo, servir). El mesero no lo ve: si lo pide por URL,
// vuelve al de mesas.
$veCocina = esPermitido('pedidos_estado');

/**
 * Usuario de cocina.
 *
 * Es el que avanza los pedidos pero no registra ni cobra: trabaja mesa por
 * mesa, así que no le sirven las listas globales. Solo ve "Ver mesas", y ahí
 * cada tarjeta muestra el estado de sus pedidos en vez de si está ocupada.
 */
$soloCocina = esPermitido('pedidos_estado')
    && !esPermitido('pedidos_crear')
    && !esPermitido('ventas');

if ($soloCocina && $panelActual !== 'mesas') {
    $panelActual = 'mesas';
}

// El panel de ventas solo existe para quien tenga ese permiso; si alguien
// lo pide por URL, vuelve al de mesas.
if ($panelActual === 'ventas' && !esPermitido('ventas')) {
    $panelActual = 'mesas';
}

// Pedidos con su detalle, para el panel de cocina
$pedidosCocina = ($veCocina && !$soloCocina) ? $pedidoModel->paraCocina() : [];

// Última novedad ya mostrada. Si se empieza en 0, el primer poll devolvería
// todas las novedades viejas y cocina oiría la alarma por cosas que ya vio.
$ultimaNovedadInicial = $soloCocina ? (int) $pedidoModel->ultimaNovedad() : 0;

/**
 * Genera una URL cambiando solo el panel.
 */
function urlPanel(string $panel): string
{
    return BASE_URL . '?page=mesas&panel=' . $panel;
}

/**
 * Mapea el estado de la base al de la interfaz de pedidos.
 */
function estadoPedidoUI(string $estado): array
{
    return match ($estado) {
        'preparando' => ['etiqueta' => 'En cocina', 'pildora' => 'lg-pill--rojo',    'icono' => 'fa fa-fire',    'accion' => 'preparado', 'texto' => 'Marcar listo'],
        'preparado'  => ['etiqueta' => 'Listo',     'pildora' => 'lg-pill--verde',   'icono' => 'fa fa-check',   'accion' => 'entregado', 'texto' => 'Servir'],
        'entregado'  => ['etiqueta' => 'Servido',   'pildora' => 'lg-pill--pizarra', 'icono' => 'fa fa-money',   'accion' => '',           'texto' => ''],
        default      => ['etiqueta' => 'Pendiente', 'pildora' => 'lg-pill--ambar',   'icono' => 'fa fa-clock-o','accion' => 'preparando','texto' => 'Aceptar']
    };
}
?>

<?php
encabezadoPagina(
    'fa fa-cutlery',
    'Mesas y Pedidos',
    'Selecciona una mesa para ver su estado o tomar un pedido'
);
?>

<!-- Selector de panel -->
<div class="lg-segmentos mb-3">

    <a href="<?= urlPanel('mesas') ?>" class="lg-segmento<?= $panelActual === 'mesas' ? ' is-activo' : '' ?>">
        <i class="fa fa-table"></i> Ver mesas
        <?php if ($soloCocina && $pedidosPendientesCocina): ?>
            <span class="lg-segmento-badge"><?= $pedidosPendientesCocina ?></span>
        <?php endif; ?>
    </a>

    <?php /* El usuario de cocina trabaja mesa por mesa: las listas globales
            no le aportan nada, así que no se le muestran. */ ?>
    <?php if (!$soloCocina): ?>

        <a href="<?= urlPanel('pedidos') ?>" class="lg-segmento<?= $panelActual === 'pedidos' ? ' is-activo' : '' ?>">
            <i class="fa fa-list-alt"></i> Ver pedidos
            <span class="lg-segmento-badge"><?= count($pedidos) ?></span>
        </a>

        <?php if ($veCocina): ?>
            <a href="<?= urlPanel('cocina') ?>" class="lg-segmento<?= $panelActual === 'cocina' ? ' is-activo' : '' ?>">
                <i class="fa fa-fire"></i> Cocina
                <?php if ($pedidosCocina): ?>
                    <span class="lg-segmento-badge"><?= count($pedidosCocina) ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (esPermitido('ventas')): ?>
        <a href="<?= urlPanel('ventas') ?>" class="lg-segmento<?= $panelActual === 'ventas' ? ' is-activo' : '' ?>">
            <i class="fa fa-bar-chart"></i> Ver ventas
        </a>
    <?php endif; ?>

</div>

<?php if ($panelActual === 'mesas'): ?>

    <?php if (!$mesas): ?>

        <div class="lg-card">
            <div class="lg-empty">
                <i class="fa fa-table"></i>
                <p class="mb-0">A&uacute;n no hay mesas registradas en la base de datos.</p>
            </div>
        </div>

    <?php else: ?>

        <!-- Leyenda de estados + agregar mesa -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

            <?php if (esPermitido('mesas_editar')): ?>
                <button type="button" class="lg-btn lg-btn--primary js-agregar-mesa">
                    <i class="fa fa-plus"></i> Agregar mesa
                </button>
            <?php endif; ?>

            <div class="lg-legend">
                <?php foreach ($soloCocina ? estadosMesaCocina() : estadosMesa() as $clave => $datos): ?>
                    <span class="lg-legend-item">
                        <span class="lg-legend-dot" style="background-color:<?= $datos['color'] ?>"></span>
                        <?= htmlspecialchars($datos['etiqueta']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- Cuadrícula de mesas -->
        <div class="lg-mesas">

            <?php foreach ($mesas as $mesa): ?>
                <?php
                    /* El usuario de cocina ve el estado de los pedidos de la
                       mesa; el resto sigue viendo si está libre u ocupada. */
                    $claveEstado = $soloCocina ? $mesa['estado_cocina'] : $mesa['estado_visual'];
                    $catalogo    = $soloCocina ? estadosMesaCocina() : estadosMesa();
                    $estado      = $catalogo[$claveEstado] ?? $catalogo['libre'];

                    // Quien puede ver las mesas no siempre puede tomar pedidos:
                    // cocina solo consulta, el mesero registra y manda a cocina.
                    $puedePedir = esPermitido('pedidos_crear');
                    $puedeEditarMesa = esPermitido('mesas_editar');
                    $puedeReservarMesa = esPermitido('mesas_reservar');
                    ?>
                    <div class="lg-mesa lg-mesa--<?= htmlspecialchars($claveEstado) ?>"
                         data-mesa-id="<?= (int) $mesa['id'] ?>">

                    <!-- Abrir pedido, o ver los pedidos de la mesa si es cocina -->
                    <button type="button"
                            class="lg-mesa-main"
                            data-mesa="Mesa <?= (int) $mesa['numero'] ?>"
                            data-mesa-id="<?= (int) $mesa['id'] ?>"
                            data-capacidad="<?= (int) $mesa['capacidad'] ?> personas"
                            <?= $soloCocina ? 'data-ver-pedidos="1"' : '' ?>>

                        <div class="lg-mesa-top">

                            <div>
                                <h3 class="lg-mesa-name">Mesa <?= (int) $mesa['numero'] ?></h3>
                                <div class="lg-mesa-cap">
                                    <i class="fa fa-users"></i>
                                    <?= (int) $mesa['capacidad'] ?> personas
                                </div>
                            </div>

                            <span class="lg-mesa-badge lg-mesa-badge--<?= htmlspecialchars($claveEstado) ?>">
                                <i class="<?= $estado['icono'] ?>"></i>
                                <?= htmlspecialchars($estado['etiqueta']) ?>
                            </span>

                        </div>

                        <?php if (!empty($mesa['detalle']) && !$soloCocina): ?>
                            <div class="lg-mesa-foot<?= $mesa['estado_visual'] === 'reservada' ? ' lg-mesa-foot--reserva' : '' ?>">
                                <i class="fa <?= $mesa['estado_visual'] === 'reservada' ? 'fa-user' : 'fa-clock-o' ?>"></i>
                                <span class="lg-mesa-reserva-nombre">
                                    <?= htmlspecialchars($mesa['detalle']) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($soloCocina): ?>
                            <div class="lg-mesa-cta">
                                <?php if ($mesa['estado_cocina'] === 'libre'): ?>
                                    <i class="fa fa-check"></i> Sin pedidos
                                <?php else: ?>
                                    <i class="fa fa-eye"></i> Ver pedidos
                                <?php endif; ?>
                            </div>
                        <?php elseif ($puedePedir): ?>
                            <div class="lg-mesa-cta">
                                <i class="fa fa-plus"></i> Agregar pedido
                            </div>
                        <?php endif; ?>

                    </button>

                    <!-- Acciones de la mesa. Reservar y quitar la reserva lo puede
                         hacer el mesero; agregar y eliminar, solo el admin. -->
                    <?php if ($puedeEditarMesa || $puedeReservarMesa): ?>
                    <div class="lg-mesa-acciones">

                        <?php if ($puedeReservarMesa): ?>
                            <?php if ($mesa['estado_visual'] === 'reservada'): ?>
                                <button type="button" class="js-liberar-mesa" data-id="<?= (int) $mesa['id'] ?>"
                                        title="Quitar reserva">
                                    <i class="fa fa-check"></i> Liberar
                                </button>
                            <?php else: ?>
                                <button type="button" class="js-reservar-mesa" data-id="<?= (int) $mesa['id'] ?>"
                                        data-mesa="Mesa <?= (int) $mesa['numero'] ?>"
                                        title="Reservar mesa">
                                    <i class="fa fa-calendar"></i> Reservar
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($puedeEditarMesa): ?>
                            <button type="button" class="js-eliminar-mesa" data-id="<?= (int) $mesa['id'] ?>"
                                    data-mesa="Mesa <?= (int) $mesa['numero'] ?>" title="Eliminar mesa">
                                <i class="fa fa-trash"></i>
                            </button>
                        <?php endif; ?>

                    </div>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="lg-info mt-3">
            <i class="fa fa-info-circle"></i>
            <span>
                <?= $soloCocina
                    ? 'Toca una mesa para ver qu&eacute; se pidi&oacute; y cambiar el estado del pedido.'
                    : 'Toca una mesa para agregar un pedido y enviarlo a la cocina.' ?>
            </span>
        </div>

    <?php endif; ?>

<?php elseif ($panelActual === 'pedidos'): ?>
<?php

    // 15 pedidos por pagina: son suficientes para trabajar y la lista no
    // crece sin fin.
    $pagPedidos = paginar($pedidos, 15);
    ?>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-list-alt"></i></span>
            <div>
                <h2 class="lg-card-title">Pedidos en curso</h2>
                <p class="lg-card-subtitle">
                    <?= count($pedidos) ?> pedido(s) en pantalla
                    <?php if ($cobrosOlvidados): ?>
                        &middot; <?= count($cobrosOlvidados) ?> sin cobrar
                    <?php endif; ?>
                </p>
            </div>

            <span class="lg-muted" style="font-size:0.75rem;"
                  title="El estado lo cambia cocina desde su pestaña">
                <i class="fa fa-eye"></i> Solo consulta
            </span>
        </div>

        <?php if (!$pedidos): ?>

            <div class="lg-empty">
                <i class="fa fa-clipboard"></i>
                <p class="mb-0">No hay pedidos pendientes.</p>
            </div>

        <?php else: ?>

            <?php if ($cobrosOlvidados): ?>
                <div class="lg-info mb-3" style="background-color:#FFF7ED;border-color:#FED7AA;color:#9A3412;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span>
                        Hay <?= count($cobrosOlvidados) ?> pedido(s) servido(s) sin cobrar de
                        otros d&iacute;as. Est&aacute;n mantendo su mesa ocupada hasta que se cobren.
                    </span>
                </div>
            <?php endif; ?>

            <?php
    /**
     * La columna Acciones solo se pinta si el rol tiene alguna acción que
     * hacer. Al cocina no puede cobrar ni borrar, así que le aparecería una
     * columna entera vacía.
     */
    $tieneAcciones = esPermitido('cobrar') || esPermitido('pedidos_editar');
    ?>

    <div class="table-responsive">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>C&oacute;digo</th>
                            <th>Mesa</th>
                            <th>Items</th>
                            <th>Hora</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <?php if ($tieneAcciones): ?>
                                <th style="text-align:right;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagPedidos['filas'] as $pedido): ?>
                            <?php
                            $ui      = estadoPedidoUI($pedido['estado']);
                            $cobrado = $ventaModel->yaCobrado((int) $pedido['id']);
                            $fecha   = date('Y-m-d', strtotime($pedido['fecha_creacion']));
                            // Servido y sin cobrar, y de un día anterior al de hoy
                            $olvidado = !$cobrado
                                && $pedido['estado'] === 'entregado'
                                && $fecha !== $hoy;
                            ?>

                            <tr<?= $olvidado ? ' style="background-color:#FFF7ED;"' : '' ?>>
                                <td style="font-weight:600;">
                                    P-<?= str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT) ?>
                                    <?php if ($olvidado): ?>
                                        <span class="lg-pill lg-pill--ambar" style="margin-left:6px;">
                                            Sin cobrar
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>Mesa <?= (int) $pedido['mesa_numero'] ?></td>
                                <td class="num"><?= (int) $pedido['items'] ?></td>
                                <td class="text-muted">
                                    <?= date('d/m H:i', strtotime($pedido['fecha_creacion'])) ?>
                                    <?php if ($fecha !== $hoy): ?>
                                        <br><small><?= date('d/m/Y', strtotime($pedido['fecha_creacion'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><?= soles((float) $pedido['total']) ?></td>
                                <td>
                                    <?php if ($cobrado): ?>
                                        <span class="lg-pill lg-pill--verde">Pagado</span>
                                    <?php else: ?>
                                        <span class="lg-pill <?= $ui['pildora'] ?>">
                                            <?= htmlspecialchars($ui['etiqueta']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($tieneAcciones): ?>
                                <td style="text-align:right;white-space:nowrap;">

                                    <?php if ($cobrado): ?>
                                        <span class="lg-muted">Cobrado</span>

                                    <?php else: ?>

                                        <?php /* Esta tabla es solo de consulta.
                                                 El estado se cambia desde la
                                                 pestaña Cocina, que es donde
                                                 también se ven los platos. */ ?>

                                        <?php if (esPermitido('cobrar')): ?>
                                            <button type="button"
                                                    class="lg-btn lg-btn--sm lg-btn--verde js-abrir-cobro"
                                                    data-id="<?= (int) $pedido['id'] ?>"
                                                    data-total="<?= htmlspecialchars((string) $pedido['total']) ?>"
                                                    data-mesa="Mesa <?= (int) $pedido['mesa_numero'] ?>">
                                                <i class="fa fa-money"></i> Cobrar
                                            </button>
                                        <?php endif; ?>

                                        <?php if (esPermitido('pedidos_editar')): ?>
                                            <button type="button"
                                                    class="lg-btn lg-btn--sm lg-btn--ghost js-eliminar-pedido"
                                                    data-id="<?= (int) $pedido['id'] ?>"
                                                    title="Eliminar pedido">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                </td>
                                <?php endif; ?>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php barraPaginacion($pagPedidos,
                'Pedidos ' . $pagPedidos['desde'] . '-' . $pagPedidos['hasta'] . ' de ' . $pagPedidos['total']); ?>


        <?php endif; ?>
    </div>

<?php elseif ($panelActual === 'cocina'): ?>

    <div class="lg-card mb-3">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-fire"></i></span>
            <div>
                <h2 class="lg-card-title">Pedidos para preparar</h2>
                <p class="lg-card-subtitle">
                    <?php if ($pedidosCocina): ?>
                        <?= count($pedidosCocina) ?> pedido(s) · en orden de llegada
                    <?php else: ?>
                        Todo al d&iacute;a
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!esPermitido('pedidos_estado')): ?>
                <span class="lg-muted" style="font-size:0.75rem;">
                    <i class="fa fa-eye"></i> Solo lectura
                </span>
            <?php endif; ?>
        </div>

        <?php if (!$pedidosCocina): ?>

            <div class="lg-empty">
                <i class="fa fa-check-circle-o"></i>
                <p class="mb-0">No hay pedidos pendientes.</p>
            </div>

        <?php else: ?>

            <div class="lg-grid-cocina">

                <?php foreach ($pedidosCocina as $pedido): ?>
                    <?php
                    $ui    = estadoPedidoUI($pedido['estado']);
                    $items = $pedido['detalle'];
                    ?>

                    <div class="lg-cocina-pedido lg-cocina-pedido--<?= htmlspecialchars($pedido['estado']) ?>">

                        <div class="lg-cocina-cabeza">
                            <div>
                                <strong style="font-size:1.05rem;">
                                    Mesa <?= (int) $pedido['mesa_numero'] ?>
                                </strong>
                                <span class="lg-muted" style="font-size:0.78rem;">
                                    P-<?= str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT) ?>
                                    &middot; <?= date('H:i', strtotime($pedido['fecha_creacion'])) ?>
                                </span>
                            </div>

                            <span class="lg-pill <?= $ui['pildora'] ?>">
                                <?= htmlspecialchars($ui['etiqueta']) ?>
                            </span>
                        </div>

                        <ul class="lg-cocina-items">
                            <?php foreach ($items as $item): ?>
                                <li>
                                    <span class="lg-cocina-cantidad">
                                        <?= rtrim(rtrim(number_format((float) $item['cantidad'], 2, '.', ''), '0'), '.') ?>×
                                    </span>
                                    <span class="lg-cocina-nombre"><?= htmlspecialchars($item['nombre']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($ui['accion'] !== '' && esPermitido('pedidos_estado')): ?>
                            <button type="button"
                                    class="lg-btn lg-btn--primary lg-btn--sm lg-cocina-avanzar"
                                    data-id="<?= (int) $pedido['id'] ?>"
                                    data-estado="<?= htmlspecialchars($ui['accion']) ?>">
                                <i class="<?= $ui['icono'] ?>"></i> <?= htmlspecialchars($ui['texto']) ?>
                            </button>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            </div>

            <div class="lg-info mt-3">
                <i class="fa fa-info-circle"></i>
                <span>
                    Las tarjetas se quedan en su lugar seg&uacute;n el orden de llegada
                    y no se mueven al cambiar el estado. Desaparecen cuando la mesa
                    se cobra.
                </span>
            </div>

        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="lg-grid-2">

        <div class="lg-card">
            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="fa fa-bar-chart"></i></span>
                <div>
                    <h2 class="lg-card-title">Ventas del d&iacute;a</h2>
                    <p class="lg-card-subtitle"><?= count($ventas) ?> venta(s) registrada(s)</p>
                </div>
            </div>

            <?php if (!$ventas): ?>

                <div class="lg-empty">
                    <i class="fa fa-bar-chart"></i>
                    <p class="mb-0">Todav&iacute;a no hay ventas registradas hoy.</p>
                </div>

            <?php else: ?>

                <div class="lg-total-box">
                    <span class="lg-row-label">Total del d&iacute;a</span>
                    <div class="lg-stat-value is-green" style="font-size:1.7rem;">
                        <?= soles($ventaModel->totalHoy()) ?>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="lg-table">
                        <thead>
                            <tr>
                                <th>C&oacute;digo</th>
                                <th>Mesa</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagVentas['filas'] as $venta): ?>
                                <tr>
                                    <td style="font-weight:600;">V-<?= str_pad((string) $venta['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td>Mesa <?= (int) $venta['mesa_numero'] ?></td>
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

                <?php barraPaginacion($pagVentas,
                    'Ventas ' . $pagVentas['desde'] . '-' . $pagVentas['hasta'] . ' de ' . $pagVentas['total']); ?>

            <?php endif; ?>
        </div>

        <div class="lg-card">
            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="fa fa-money"></i></span>
                <div>
                    <h2 class="lg-card-title">Ingresos por categor&iacute;a</h2>
                    <p class="lg-card-subtitle">Distribuci&oacute;n de las ventas de hoy</p>
                </div>
            </div>

            <?php $porCategoria = $ventaModel->porCategoria(); ?>

            <?php if (!$porCategoria): ?>

                <div class="lg-empty">
                    <i class="fa fa-tags"></i>
                    <p class="mb-0">Sin datos de ventas por categor&iacute;a.</p>
                </div>

            <?php else: ?>
                <div class="lg-rows">
                    <?php foreach ($porCategoria as $fila): ?>
                        <div class="lg-row">
                            <span class="lg-row-label"><?= htmlspecialchars($fila['categoria']) ?></span>
                            <span class="lg-row-value"><?= soles((float) $fila['total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

<?php endif; ?>

<!-- Catálogos y modales: solo se cargan si el rol los puede usar -->
<?php
$productosModal = [];

foreach ($productos as $producto) {
    $productosModal[] = [
        'nombre'    => $producto['nombre'],
        'categoria' => $producto['categoria'],
        'precio'    => (float) $producto['precio'],
        'stock'     => (int) $producto['stock'],
        'minimo'    => (int) $producto['stock_minimo'],
        'id'        => (int) $producto['id']
    ];
}

if (esPermitido('pedidos_crear')) {
    require APP_ROOT . '/views/partials/modal_pedido.php';
}

// El usuario de cocina abre el detalle de la mesa desde su tarjeta
if ($soloCocina) {
    require APP_ROOT . '/views/partials/modal_mesa_pedidos.php';
}

if (esPermitido('cobrar')) {
    require APP_ROOT . '/views/partials/modal_cobro.php';
}

if (esPermitido('mesas_editar')) {
    require APP_ROOT . '/views/partials/modal_mesa.php';
}

if (esPermitido('mesas_reservar')) {
    require APP_ROOT . '/views/partials/modal_reserva.php';
}
?>

<?php /* Aviso de pedido nuevo. Solo lo necesita quien está en cocina: es el
        que tiene que enterarse de que el mesero acaba de mandar algo. */ ?>
<?php if ($soloCocina): ?>
    <script>
    /**
     * Sondeo de cocina: solo la campana y el aviso de pedido nuevo.
     *
     * El refresco de los datos NO se hace aqui. Lo lleva el motor de tiempo
     * real de app.js, que revisa toda la base en todas las paginas y para
     * todos los roles: cobros, estados, stock, gastos. Aqui unicamente se
     * avisa de lo que entra, que es lo que el resto no puede hacer.
     *
     * Arranca con 'load' y no con $(function(){}) porque este script esta
     * en el cuerpo de la pagina y jQuery como app.js se cargan despues,
     * en el pie.
     */
    window.addEventListener('load', function () {

        /**
         * Cada cuanto se mira si entro algo.
         *
         * Seis segundos se nota rapido en la cocina sin castigar el servidor:
         * el refresco de los datos va cada 5 segundos por su cuenta, asi que
         * esto solo va atras, nunca adelante.
         */
        var CADA_CUANTO_MS = 6000;

        /**
         * Pedidos que ya estaban en cocina al abrir la pagina.
         *
         * Se guardan para no dar la bienvenida por pedido nuevo a las comandas
         * que ya estaban trabajando cuando el cocinero se sentó.
         */
        var yaVistos = <?= json_encode($idsPendientesIniciales) ?>;

        /**
         * Ultima novedad ya mostrada. El servidor solo devuelve las que
         * tengan id mayor, asi que no se avisa dos veces por lo mismo.
         */
        var ultimaNovedad = <?= (int) ($ultimaNovedadInicial ?? 0) ?>;

        /**
         * Cartel flotante con el detalle de lo que llego.
         *
         * Vive fuera del recuadro de contenido, asi que sobrevive al
         * refresco de los datos.
         */
        var mostrarAviso = function (lineas) {

            Alarma.tocar();

            var aviso = document.createElement('div');
            aviso.className = 'lg-flash lg-flash--alerta';
            aviso.setAttribute('role', 'alert');

            aviso.innerHTML = '<i class="fa fa-bell"></i><div>' + lineas.join('<br>') + '</div>';

            document.body.appendChild(aviso);

            window.setTimeout(function () {
                $(aviso).fadeOut(400, function () {
                    aviso.remove();
                });
            }, 5000);

        };

        window.setInterval(function () {

            // Si hay un modal abierto, se espera: el cocinero está ocupado
            // y recargarle debajo lo dejaría a medias.
            if (document.querySelector('.modal.show')) {
                return;
            }

            apiGet('api/pedido/resumen', { desde: ultimaNovedad })
            .done(function (r) {
                /**
                 * Pedidos que hay que nombrar en el aviso.
                 *
                 * Solo dos cosas las ameritan: que el pedido sea nuevo de
                 * verdad (no lo tenia en la lista) o que le hayan agregado
                 * productos (eso llega por las novedades, de arriba).
                 *
                 * Ya no se compara la fecha de cambio porque al mover el
                 * estado de un pedido la base le actualiza esa misma fecha:
                 * el propio cocinero se disparaba avisos a si mismo.
                 */
                var vistos = {};

                yaVistos.forEach(function (p) { vistos[p.id] = true; });

                var pedidosNuevos = [];
                var avisos = [];

                // Lo que se le sumo a una comanda que ya estaba en cocina
                (r.novedades || []).forEach(function (n) {

                    avisos.push(
                        '<strong>Pedido nuevo agregado</strong> - Mesa ' + n.mesa
                        + ' (P-' + String(n.pedido_id).padStart(4, '0') + '): ' + n.texto
                    );

                    ultimaNovedad = Math.max(ultimaNovedad, Number(n.id));

                    // Queda como visto, para no anunciarlo ademas como nuevo
                    yaVistos.push({ id: Number(n.pedido_id), alta: n.alta || '' });
                });

                // Y los pedidos que de verdad no tenia en la lista
                (r.pendientes || []).forEach(function (p) {

                    var id = Number(p.id);

                    if (vistos[id] === undefined) {
                        pedidosNuevos.push(p.mesa);
                        yaVistos.push({ id: id, alta: p.alta });
                    }
                });

                // Los pedidos nuevos, nombrando su mesa
                if (pedidosNuevos.length) {
                    pedidosNuevos.forEach(function (mesa) {
                        avisos.push(
                            '<strong>Pedido nuevo de la mesa</strong> (' + mesa + ').'
                        );
                    });
                }

                if (!avisos.length) {
                    return;
                }

                mostrarAviso(avisos);

                // El contador de la pestaña lo pone el propio refresco que
                // hace mostrarAviso, con los numeros que ya trae el servidor.
            });

        }, CADA_CUANTO_MS);

    });
    </script>
<?php endif; ?>

