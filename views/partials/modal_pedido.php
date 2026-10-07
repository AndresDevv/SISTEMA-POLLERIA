<?php
/**
 * Partial: modal para agregar productos al pedido de una mesa.
 *
 * Se incluye desde las vistas de Mesas y Pedidos.
 * Los productos se leen de un array $productosModal para no acoplar el partial al modelo.
 */

$productosModal = $productosModal ?? [];
$categoriasModal = array_values(array_unique(array_column($productosModal, 'categoria')));
?>

<div class="modal fade" id="modalPedido" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <!-- Cabecera -->
            <div class="modal-header align-items-center" style="border-bottom:1px solid #EFEFEF;">
                <div class="d-flex align-items-center gap-3">
                    <span class="lg-card-icon" style="width:44px;height:44px;">
                        <i class="fa fa-clipboard"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" style="font-weight:700;margin:0;">
                            Pedido — <span id="pedidoMesa">Mesa 1</span>
                        </h5>
                        <small class="lg-muted" id="pedidoMesaInfo">2-4 personas</small>
                    </div>
                </div>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Cuerpo: dos columnas con scroll propio, para que el catálogo
                 quede siempre a la vista aunque la mesa tenga muchos
                 pedidos ya registrados. -->
            <div class="modal-body lg-modal-cuerpo">

                <!-- Aviso de validación -->
                <div class="lg-info mb-3" id="pedidoAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="pedidoAvisoTexto">Agrega al menos un producto antes de enviar el pedido.</span>
                </div>

                <div class="lg-modal-panes">

                    <!-- Selector de productos -->
                    <div class="lg-modal-pane lg-modal-pane--catalogo">

                        <div class="lg-modal-pane-head">
                            <p class="lg-subtitle-block" style="margin:0;">
                                Agregar productos al pedido
                            </p>

                            <div class="lg-filters mb-0">
                                <div class="lg-field">
                                    <input type="search" id="modalBuscar"
                                           class="lg-input" placeholder="Buscar producto...">
                                </div>
                                <div class="lg-field" style="max-width:180px;">
                                    <select id="modalCategoria" class="lg-select">
                                        <option value="">Todas</option>
                                        <?php foreach ($categoriasModal as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>">
                                                <?= htmlspecialchars($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="lg-modal-pane-scroll">
                            <?php if (!$productosModal): ?>

                                <div class="lg-empty">
                                    <i class="fa fa-cube"></i>
                                    <p class="mb-0">No hay productos registrados en la base de datos.</p>
                                </div>

                            <?php else: ?>

                                <div id="modalCatalogo" class="modal-catalogo">
                                    <?php foreach ($productosModal as $p): ?>
                                        <?php
                                        $nombreModal    = $p['nombre'] ?? 'Producto';
                                        $categoriaModal = $p['categoria'] ?? '';
                                        $precioModal    = (float) ($p['precio'] ?? 0);
                                        $stockModal     = (int) ($p['stock'] ?? 0);
                                        $minimoModal    = (int) ($p['minimo'] ?? 0);
                                        $agotado        = $stockModal <= 0;
                                        $nivelModal     = nivelStock($stockModal);
                                        $claseModal     = claseStock($stockModal);
                                        ?>

                                        <button type="button"
                                                class="modal-producto <?= $claseModal ?><?= $agotado ? ' is-agotado' : '' ?>"
                                                data-nombre="<?= htmlspecialchars(strtolower($nombreModal)) ?>"
                                                data-categoria="<?= htmlspecialchars($categoriaModal) ?>"
                                                data-precio="<?= $precioModal ?>"
                                                data-producto-id="<?= (int) ($p['id'] ?? 0) ?>"
                                                data-producto="<?= htmlspecialchars($nombreModal) ?>"
                                                data-stock="<?= (int) $stockModal ?>"
                                                <?= $agotado ? 'disabled' : '' ?>>

                                            <span class="modal-producto-nombre">
                                                <?= htmlspecialchars($nombreModal) ?>
                                            </span>

                                            <span class="modal-producto-precio <?= $claseModal ?>">
                                                <?= soles($precioModal) ?>
                                                &middot; <?= $agotado
                                                    ? 'Agotado'
                                                    : htmlspecialchars(estadosStock()[$nivelModal]['etiqueta']) ?>
                                            </span>
                                        </button>

                                    <?php endforeach; ?>
                                </div>

                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- Pedido en curso + pedidos ya registrados -->
                    <div class="lg-modal-pane lg-modal-pane--pedido">

                        <!--
                            Los pedidos que ya tiene la mesa van plegados en una
                            línea. Antes ocupaban todo el alto y empujaban el
                            catálogo fuera de la pantalla.
                        -->
                        <div class="lg-plegable" id="pedidoExistentesWrap" style="display:none;">
                            <button type="button" class="lg-plegable-cab"
                                    id="pedidoExistentesToggle"
                                    aria-expanded="false">
                                <span>
                                    <i class="fa fa-list-alt"></i>
                                    <strong>Pedidos ya registrados</strong>
                                    <span class="lg-muted" id="pedidoExistentesResumen"></span>
                                </span>
                                <i class="fa fa-chevron-down lg-plegable-flecha"></i>
                            </button>

                            <div class="lg-plegable-cuerpo" id="pedidoExistentes"></div>
                        </div>

                        <div class="modal-pedido lg-modal-pedido">
                            <div class="modal-pedido-head">
                                <h6 style="font-weight:700;margin:0;">Pedido en curso</h6>
                                <span class="lg-pill lg-pill--pizarra" id="pedidoItemsCount">0 items</span>
                            </div>

                            <div id="pedidoLista" class="modal-pedido-lista">
                                <p class="lg-muted text-center py-4 mb-0" id="pedidoVacio">
                                    Todav&iacute;a no hay productos en el pedido.
                                </p>
                            </div>

                            <div class="lg-total-box mt-3">
                                <div class="lg-row">
                                    <span class="lg-row-label">Total</span>
                                    <span class="lg-row-value" id="pedidoTotal">S/ 0.00</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Pie -->
            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">

                <button type="button" class="lg-btn lg-btn--ghost mr-auto" id="pedidoLimpiar">
                    <i class="fa fa-trash"></i> Descartar
                </button>

                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">
                    Cancelar
                </button>

                <button type="button" class="lg-btn lg-btn--primary" id="pedidoEnviarCocina">
                    <i class="fa fa-paper-plane"></i> Enviar a cocina
                </button>

            </div>

        </div>
    </div>
</div>
