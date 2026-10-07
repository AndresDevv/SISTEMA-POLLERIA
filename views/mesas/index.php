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

/**
 * Pedidos a mostrar, sin repetir.
 *
 * Antes se concatenaban la lista de activos con la de pedidos del día, y un
 * pedido pendiente de hoy aparecía en ambas: se veía duplicado en pantalla.
 */
$pedidos = [];
$vistos  = [];

foreach (array_merge($pedidosHoy, $pedidoModel->listar('activos')) as $pedido) {
    $clave = (int) $pedido['id'];

    if (isset($vistos[$clave])) {
        continue;
    }

    $vistos[$clave] = true;
    $pedidos[] = $pedido;
}

/** ¿Qué panel se muestra? */
$panelActual = $_GET['panel'] ?? 'mesas';

if (!in_array($panelActual, ['mesas', 'pedidos', 'cocina', 'ventas'], true)) {
    $panelActual = 'mesas';
}

// El panel de ventas solo existe para quien tenga ese permiso; si alguien
// lo pide por URL, vuelve al de mesas.
if ($panelActual === 'ventas' && !esPermitido('ventas')) {
    $panelActual = 'mesas';
}

// Pedidos con su detalle, para el panel de cocina
$pedidosCocina = $pedidoModel->paraCocina();

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
    </a>

    <a href="<?= urlPanel('pedidos') ?>" class="lg-segmento<?= $panelActual === 'pedidos' ? ' is-activo' : '' ?>">
        <i class="fa fa-list-alt"></i> Ver pedidos
        <span class="lg-segmento-badge"><?= count($pedidos) ?></span>
    </a>

    <a href="<?= urlPanel('cocina') ?>" class="lg-segmento<?= $panelActual === 'cocina' ? ' is-activo' : '' ?>">
        <i class="fa fa-fire"></i> Cocina
        <?php if ($pedidosCocina): ?>
            <span class="lg-segmento-badge"><?= count($pedidosCocina) ?></span>
        <?php endif; ?>
    </a>

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
                <?php foreach (estadosMesa() as $clave => $datos): ?>
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
                <?php $estado = estadosMesa()[$mesa['estado_visual']] ?? estadosMesa()['libre']; ?>

                <?php
                    // Quien puede ver las mesas no siempre puede tomar pedidos:
                    // cocina solo consulta, el mesero registra y manda a cocina.
                    $puedePedir = esPermitido('pedidos_crear');
                    $puedeEditarMesa = esPermitido('mesas_editar');
                    $puedeReservarMesa = esPermitido('mesas_reservar');
                    ?>
                    <div class="lg-mesa lg-mesa--<?= htmlspecialchars($mesa['estado_visual']) ?>"
                         data-mesa-id="<?= (int) $mesa['id'] ?>">

                    <!-- Abrir pedido -->
                    <button type="button"
                            class="lg-mesa-main"
                            data-mesa="Mesa <?= (int) $mesa['numero'] ?>"
                            data-mesa-id="<?= (int) $mesa['id'] ?>"
                            data-capacidad="<?= (int) $mesa['capacidad'] ?> personas">

                        <div class="lg-mesa-top">

                            <div>
                                <h3 class="lg-mesa-name">Mesa <?= (int) $mesa['numero'] ?></h3>
                                <div class="lg-mesa-cap">
                                    <i class="fa fa-users"></i>
                                    <?= (int) $mesa['capacidad'] ?> personas
                                </div>
                            </div>

                            <span class="lg-mesa-badge lg-mesa-badge--<?= htmlspecialchars($mesa['estado_visual']) ?>">
                                <i class="<?= $estado['icono'] ?>"></i>
                                <?= htmlspecialchars($estado['etiqueta']) ?>
                            </span>

                        </div>

                        <?php if (!empty($mesa['detalle'])): ?>
                            <div class="lg-mesa-foot<?= $mesa['estado_visual'] === 'reservada' ? ' lg-mesa-foot--reserva' : '' ?>">
                                <i class="fa <?= $mesa['estado_visual'] === 'reservada' ? 'fa-user' : 'fa-clock-o' ?>"></i>
                                <span class="lg-mesa-reserva-nombre">
                                    <?= htmlspecialchars($mesa['detalle']) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($puedePedir): ?>
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
            <span>Toca una mesa para agregar un pedido y enviarlo a la cocina.</span>
        </div>

    <?php endif; ?>

<?php elseif ($panelActual === 'pedidos'): ?>

    <?php
    /**
     * Huella de los pedidos al cargar la página, para que el mesero note los
     * cambios de estado que vaya haciendo cocina. Se usa md5 de los mismos
     * datos que devuelve api/pedido/resumen.
     */
    $huellaPedidos = md5(json_encode([
        array_map(static fn (array $p): array => [
            'id'     => (int) $p['id'],
            'estado' => $p['estado'],
            'total'  => (float) $p['total']
        ], $pedidos),
        (int) $ventaModel->totalPagadas()
    ]));
    ?>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-list-alt"></i></span>
            <div>
                <h2 class="lg-card-title">Pedidos en curso</h2>
                <p class="lg-card-subtitle"><?= count($pedidos) ?> pedido(s) pendiente(s)</p>
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
                        <?php foreach ($pedidos as $pedido): ?>
                            <?php
                            $ui      = estadoPedidoUI($pedido['estado']);
                            $cobrado = $ventaModel->yaCobrado((int) $pedido['id']);
                            ?>

                            <tr>
                                <td style="font-weight:600;">P-<?= str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>Mesa <?= (int) $pedido['mesa_numero'] ?></td>
                                <td class="num"><?= (int) $pedido['items'] ?></td>
                                <td class="text-muted"><?= date('H:i', strtotime($pedido['fecha_creacion'])) ?></td>
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

            <?php if (!esPermitido('pedidos_estado')): ?>
                <script>
                    /**
                     * El mesero ve aquí lo que cocina va marcando. Como no
                     * tiene un botón que refresque, se comprueba cada 30 s y
                     * solo se recarga si algo cambió y no hay ningún modal
                     * abierto (para no interrumpir un cobro en curso).
                     */
                    (function () {
                        var huella = <?= json_encode($huellaPedidos) ?>;

                        setInterval(function () {

                            if ($('.modal.show').length) {
                                return;
                            }

                            apiGet('api/pedido/resumen')
                            .done(function (r) {

                                if (r.huella !== huella) {
                                    window.location.reload();
                                }
                            });
                        }, 30000);
                    })();
                </script>
            <?php endif; ?>

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
                            <?php foreach ($ventas as $venta): ?>
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
