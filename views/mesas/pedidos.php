<?php
/**
 * Vista: pedidos.
 *
 * Pantalla táctil para el mesero: elige mesa y manda el pedido a cocina.
 * La columna derecha es el tablero del cocinero con los pedidos reales.
 */

require_once __DIR__ . '/../../models/Mesa.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Producto.php';

$mesaModel     = new Mesa();
$pedidoModel   = new Pedido();
$productoModel = new Producto();

$mesas     = $mesaModel->listar();
$pedidos   = $pedidoModel->listar('activos');
$productos = $productoModel->listar();

/**
 * Mapea el estado de la base al de la interfaz de pedidos.
 */
function estadoPedidoUI(string $estado): array
{
    return match ($estado) {
        'preparando' => ['etiqueta' => 'En cocina', 'pildora' => 'lg-pill--rojo',    'icono' => 'fa fa-fire',    'accion' => 'preparado', 'texto' => 'Marcar listo'],
        'preparado'  => ['etiqueta' => 'Listo',     'pildora' => 'lg-pill--verde',   'icono' => 'fa fa-check',   'accion' => 'entregado', 'texto' => 'Servir'],
        'entregado'  => ['etiqueta' => 'Servido',   'pildora' => 'lg-pill--pizarra', 'icono' => 'fa fa-bell',   'accion' => '',           'texto' => ''],
        default      => ['etiqueta' => 'Pendiente', 'pildora' => 'lg-pill--ambar',   'icono' => 'fa fa-clock-o','accion' => 'preparando','texto' => 'Aceptar']
    };
}
?>

<?php
encabezadoPagina(
    'fa fa-list-alt',
    'Pedidos',
    'Toma pedidos por mesa y envíalos a la cocina'
);
?>

<div class="lg-grid-2">

    <!-- ================= Columna mesero ================= -->
    <div>

        <!-- Selector de mesa -->
        <div class="lg-card mb-3">
            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="fa fa-table"></i></span>
                <div>
                    <h2 class="lg-card-title">Mesa del pedido</h2>
                    <p class="lg-card-subtitle">Toca la mesa para empezar o continuar</p>
                </div>
            </div>

            <?php if (!$mesas): ?>

                <div class="lg-empty">
                    <i class="fa fa-table"></i>
                    <p class="mb-0">No hay mesas registradas.</p>
                </div>

            <?php else: ?>

                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($mesas as $mesa): ?>
                        <button type="button"
                                class="lg-btn lg-btn--sm lg-mesa-badge--<?= htmlspecialchars($mesa['estado_visual']) ?>"
                                style="min-height:44px;"
                                data-abrir-pedido
                                data-mesa-id="<?= (int) $mesa['id'] ?>"
                                data-mesa="Mesa <?= (int) $mesa['numero'] ?>"
                                data-capacidad="<?= (int) $mesa['capacidad'] ?> personas">
                            Mesa <?= (int) $mesa['numero'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

        <!-- Catálogo -->
        <div class="lg-card">
            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="fa fa-list"></i></span>
                <div>
                    <h2 class="lg-card-title">Catálogo</h2>
                    <p class="lg-card-subtitle">Toca un producto para agregarlo al pedido</p>
                </div>
            </div>

            <?php if (!$productos): ?>

                <div class="lg-empty">
                    <i class="fa fa-cube"></i>
                    <p class="mb-0">No hay productos registrados en la base de datos.</p>
                </div>

            <?php else: ?>

                <div class="lg-filters mb-3">
                    <div class="lg-field">
                        <input type="search" id="filtroCatalogoPedidos" class="lg-input" placeholder="Buscar producto...">
                    </div>
                </div>

                <?php foreach ($productos as $producto): ?>
                    <?php $agotado = (int) $producto['stock'] <= 0; ?>

                    <button type="button"
                            class="lg-btn lg-btn--ghost text-left w-100 mb-2 js-filtra-catalogo"
                            style="min-height:52px;flex-direction:column;align-items:flex-start;justify-content:center;gap:2px;<?= $agotado ? 'opacity:0.5;' : '' ?>"
                            data-nombre="<?= htmlspecialchars(strtolower($producto['nombre'])) ?>"
                            <?= $agotado ? 'disabled' : '' ?>>

                        <span style="font-weight:600;">
                            <?= htmlspecialchars($producto['nombre']) ?>
                            <?php if ($agotado): ?>
                                <span class="lg-pill lg-pill--rojo" style="font-size:0.65rem;">Agotado</span>
                            <?php endif; ?>
                        </span>

                        <span class="text-muted" style="font-size:0.78rem;">
                            <?= soles((float) $producto['precio']) ?>
                            &middot; <?= htmlspecialchars($producto['categoria']) ?>
                        </span>

                    </button>

                <?php endforeach; ?>

            <?php endif; ?>
        </div>

    </div>

    <!-- ================= Columna cocina ================= -->
    <div>

        <div class="lg-card">
            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="fa fa-fire"></i></span>
                <div>
                    <h2 class="lg-card-title">Pedidos en curso</h2>
                    <p class="lg-card-subtitle"><?= count($pedidos) ?> pedido(s) enviado(s) a cocina</p>
                </div>
            </div>

            <?php if (!$pedidos): ?>

                <div class="lg-empty">
                    <i class="fa fa-fire"></i>
                    <p class="mb-0">No hay pedidos en cocina por ahora.</p>
                </div>

            <?php else: ?>

                <?php foreach ($pedidos as $pedido): ?>
                    <?php
                    $ui = estadoPedidoUI($pedido['estado']);
                    $detalle = $pedidoModel->conDetalle((int) $pedido['id']);
                    ?>

                    <div class="lg-card lg-card--flat mb-3">

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">

                            <div>
                                <strong>P-<?= str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                                <span class="text-muted">&middot; Mesa <?= (int) $pedido['mesa_numero'] ?></span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="lg-pill <?= $ui['pildora'] ?>">
                                    <?= htmlspecialchars($ui['etiqueta']) ?>
                                </span>
                                <small class="text-muted">
                                    <?= date('H:i', strtotime($pedido['fecha_creacion'])) ?>
                                </small>
                            </div>

                        </div>

                        <table class="lg-table lg-table--compact">
                            <tbody>
                                <?php foreach ($detalle['items'] as $item): ?>
                                    <tr>
                                        <td class="num" style="width:55px;font-weight:600;">
                                            <?= (float) $item['cantidad'] ?>x
                                        </td>
                                        <td><?= htmlspecialchars($item['nombre']) ?></td>
                                        <td class="num text-muted" style="text-align:right;">
                                            <?= soles((float) $item['subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                            <strong><?= soles((float) $pedido['total']) ?></strong>

                            <?php if ($ui['accion'] !== ''): ?>
                                <button type="button"
                                        class="lg-btn lg-btn--sm lg-btn--primary js-estado-pedido"
                                        data-id="<?= (int) $pedido['id'] ?>"
                                        data-estado="<?= htmlspecialchars($ui['accion']) ?>">
                                    <i class="<?= $ui['icono'] ?>"></i> <?= htmlspecialchars($ui['texto']) ?>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>
        </div>

    </div>

</div>

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

require APP_ROOT . '/views/partials/modal_pedido.php';
?>
