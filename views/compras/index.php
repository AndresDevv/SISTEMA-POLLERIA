<?php
/**
 * Vista: compras.
 *
 * Tres pestañas: registrar compra, historial y proveedores.
 */

require_once APP_ROOT . '/models/Compra.php';
require_once APP_ROOT . '/models/Gestion.php';
require_once APP_ROOT . '/models/Producto.php';

$compraModel   = new Compra();
$productoModel = new Producto();

$pestana = $_GET['tab'] ?? 'registrar';

if (!in_array($pestana, ['registrar', 'historial', 'proveedores'], true)) {
    $pestana = 'registrar';
}

$compras    = $compraModel->listar();
$proveedores = (new Gestion())->listar('proveedores');
$productos  = $productoModel->listar();

if ($pestana === 'proveedores'):

    $pestanaActual  = $pestana;
    $recurso        = 'proveedores';
    $icono          = 'fa fa-truck';
    $titulo         = 'Compras';
    $subtitulo      = 'Proveedores de la pollería';

    $recursosExtra = [
        ['clave' => 'registrar',   'recurso' => 'proveedores', 'pagina' => 'compras',
         'titulo' => 'Registrar compra', 'icono' => 'fa fa-plus-circle'],
        ['clave' => 'historial',   'recurso' => 'proveedores', 'pagina' => 'compras&tab=historial',
         'titulo' => 'Historial', 'icono' => 'fa fa-list'],
        ['clave' => 'proveedores', 'recurso' => 'proveedores', 'pagina' => 'compras&tab=proveedores',
         'titulo' => 'Proveedores', 'icono' => 'fa fa-truck']
    ];

    require APP_ROOT . '/views/admin/gestion.php';

    return;

endif;
?>

<?php
PestanasModulo([
    ['clave' => 'registrar',   'titulo' => 'Registrar compra', 'icono' => 'fa fa-plus-circle',
     'url' => url('compras')],
    ['clave' => 'historial',   'titulo' => 'Historial', 'icono' => 'fa fa-list',
     'url' => url('compras') . '&tab=historial', 'contador' => count($compras)],
    ['clave' => 'proveedores', 'titulo' => 'Proveedores', 'icono' => 'fa fa-truck',
     'url' => url('compras') . '&tab=proveedores']
], $pestana);

encabezadoPagina(
    'fa fa-shopping-cart',
    'Compras',
    'Registra las compras y administra los proveedores'
);
?>

<?php if ($pestana === 'registrar'): ?>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-cart-plus"></i></span>
            <div>
                <h2 class="lg-card-title">Registrar compra</h2>
                <p class="lg-card-subtitle">El stock de los productos se actualiza al guardar</p>
            </div>
        </div>

        <?php if (!$productos): ?>
            <div class="lg-empty">
                <i class="fa fa-cube"></i>
                <p class="mb-0">No hay productos registrados. Crea uno en Inventario primero.</p>
            </div>
        <?php else: ?>

            <div class="lg-filters mb-3">
                <div class="lg-field">
                    <label class="lg-label" for="compraProveedor">Proveedor</label>
                    <select id="compraProveedor" class="lg-select">
                        <option value="">Sin proveedor</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg-field">
                    <label class="lg-label" for="compraBuscar">Buscar producto</label>
                    <input type="search" id="compraBuscar" class="lg-input" placeholder="Nombre del producto...">
                </div>
            </div>

            <p class="lg-subtitle-block">Qué vas a comprar</p>

            <?php
            // Categorías del catálogo, con Insumos primero porque es lo que
            // más se compra. Sirven de filtro junto con el buscador.
            $categoriasCatalogo = [];

            foreach ($productos as $p) {
                $categoriasCatalogo[$p['categoria']] = ($categoriasCatalogo[$p['categoria']] ?? 0) + 1;
            }

            uksort($categoriasCatalogo, static function ($a, $b) {
                if ($a === 'Insumos') {
                    return -1;
                }

                if ($b === 'Insumos') {
                    return 1;
                }

                return strcasecmp((string) $a, (string) $b);
            });
            ?>

            <div class="d-flex flex-wrap gap-2 mb-3" id="compraFiltros">
                <button type="button" class="lg-chip js-filtro-categoria is-activo"
                        data-categoria="">Todas (<?= count($productos) ?>)</button>

                <?php foreach ($categoriasCatalogo as $categoria => $cuantos): ?>
                    <button type="button" class="lg-chip js-filtro-categoria"
                            data-categoria="<?= htmlspecialchars((string) $categoria) ?>">
                        <?= htmlspecialchars((string) $categoria) ?> (<?= (int) $cuantos ?>)
                    </button>
                <?php endforeach; ?>
            </div>

            <div id="compraCatalogo" class="modal-catalogo" style="max-height:none;">
                <?php foreach ($productos as $p): ?>
                    <?php
                    $nivel  = nivelStock((int) $p['stock']);
                    $clase  = claseStock((int) $p['stock']);
                    ?>
                    <button type="button"
                            class="modal-producto <?= $clase ?>"
                            data-categoria="<?= htmlspecialchars((string) ($p['categoria'] ?? '')) ?>"
                            data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
                            data-id="<?= (int) $p['id'] ?>"
                            data-precio="<?= (float) $p['precio'] ?>"
                            data-nombre-texto="<?= htmlspecialchars($p['nombre']) ?>">
                        <span class="modal-producto-nombre"><?= htmlspecialchars($p['nombre']) ?></span>
                        <span class="modal-producto-precio <?= $clase ?>">
                            Stock: <?= unidades($p['stock']) ?>
                            &middot; <?= htmlspecialchars(estadosStock()[$nivel]['etiqueta']) ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="lg-empty" id="compraSinResultados" style="display:none;">
                <i class="fa fa-search"></i>
                <p class="mb-0">Ning&uacute;n producto coincide con el filtro.</p>
            </div>

            <p class="lg-subtitle-block">Detalle de la compra</p>

            <div class="table-responsive">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="width:130px;">Cantidad</th>
                            <th style="width:140px;">Precio unit.</th>
                            <th style="text-align:right;">Subtotal</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="compraItems">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3" id="compraVacio">
                                Todav&iacute;a no agregaste productos.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="lg-total-box mt-3">
                <div class="lg-row">
                    <span class="lg-row-label">Total de la compra</span>
                    <span class="lg-row-value" id="compraTotal">S/ 0.00</span>
                </div>
            </div>

            <div class="lg-info mt-3" id="compraAviso" style="display:none;">
                <i class="fa fa-exclamation-circle"></i>
                <span id="compraAvisoTexto"></span>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="lg-btn lg-btn--primary lg-btn--sm" id="compraGuardar">
                    <i class="fa fa-check"></i> Guardar compra
                </button>
            </div>

        <?php endif; ?>
    </div>

<?php elseif ($pestana === 'historial'): ?>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-list"></i></span>
            <div>
                <h2 class="lg-card-title">Historial de compras</h2>
                <p class="lg-card-subtitle"><?= count($compras) ?> compra(s) registrada(s)</p>
            </div>
        </div>

        <?php if (!$compras): ?>
            <div class="lg-empty">
                <i class="fa fa-cart-plus"></i>
                <p class="mb-0">A&uacute;n no hay compras registradas.</p>
            </div>
        <?php else: ?>
            <div class="lg-info mb-3">
                <i class="fa fa-info-circle"></i>
                <span>Toca una compra para ver qu&eacute; productos trajo. Si te equivocaste, puedes anularla.</span>
            </div>

            <div class="table-responsive">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>C&oacute;digo</th>
                            <th>Proveedor</th>
                            <th>Registró</th>
                            <th>Fecha</th>
                            <th>Items</th>
                            <th style="text-align:right;">Total</th>
                            <th style="text-align:right;">Gasto del d&iacute;a</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $c): ?>
                            <?php $anulada = $c['estado'] === 'anulada'; ?>
                            <tr<?= $anulada ? ' style="opacity:0.55;"' : '' ?>>
                                <td style="font-weight:600;">C-<?= str_pad((string) $c['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($c['proveedor'] ?? 'Sin proveedor') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($c['usuario'] ?? '-') ?></td>
                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($c['fecha'])) ?></td>
                                <td class="num">
                                    <?= unidades($c['items']) ?>
                                    <?php if ((int) $c['productos'] > 1): ?>
                                        <span class="lg-muted">· <?= (int) $c['productos'] ?> prod.</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num" style="text-align:right;font-weight:600;"><?= soles((float) $c['total']) ?></td>
                                <td class="num" style="text-align:right;color:<?= $anulada ? '#94A3B8' : '#EF4444' ?>;">
                                    <?= $anulada ? '&mdash;' : soles((float) $c['total']) ?>
                                </td>
                                <td>
                                    <span class="lg-pill <?= $anulada ? 'lg-pill--pizarra' : 'lg-pill--verde' ?>">
                                        <?= $anulada ? 'Anulada' : 'Registrada' ?>
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-ver-compra"
                                            data-id="<?= (int) $c['id'] ?>"
                                            title="Ver detalle">
                                        <i class="fa fa-eye"></i>
                                    </button>

                                    <?php if (!$anulada): ?>
                                        <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-anular-compra"
                                                data-id="<?= (int) $c['id'] ?>"
                                                data-codigo="C-<?= str_pad((string) $c['id'], 4, '0', STR_PAD_LEFT) ?>"
                                                title="Anular compra">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require APP_ROOT . '/views/partials/modal_compra.php'; ?>