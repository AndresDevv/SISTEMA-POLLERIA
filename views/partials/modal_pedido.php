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

            <!-- Cuerpo -->
            <div class="modal-body" style="max-height:65vh;overflow-y:auto;">

                <!-- Aviso de validación -->
                <div class="lg-info mb-3" id="pedidoAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="pedidoAvisoTexto">Agrega al menos un producto antes de enviar el pedido.</span>
                </div>

                <!-- Pedidos que ya tiene la mesa -->
                <div class="mb-3" id="pedidoExistentesWrap" style="display:none;">
                    <p class="lg-subtitle-block" style="margin-top:0;">
                        Pedidos ya registrados en esta mesa
                    </p>
                    <div id="pedidoExistentes"></div>
                </div>

                <p class="lg-subtitle-block" style="margin-top:0;">
                    Agregar productos al pedido
                </p>

                <div class="row">

                    <!-- Selector de productos -->
                    <div class="col-lg-7">

                        <div class="lg-filters mb-3">
                            <div class="lg-field">
                                <input type="search" id="modalBuscar"
                                       class="lg-input" placeholder="Buscar producto...">
                            </div>
                            <div class="lg-field">
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
                                    ?>

                                    <button type="button"
                                            class="modal-producto<?= $agotado ? ' is-agotado' : '' ?>"
                                            data-nombre="<?= htmlspecialchars(strtolower($nombreModal)) ?>"
                                            data-categoria="<?= htmlspecialchars($categoriaModal) ?>"
                                            data-precio="<?= $precioModal ?>"
                                            data-producto-id="<?= (int) ($p['id'] ?? 0) ?>"
                                            data-producto="<?= htmlspecialchars($nombreModal) ?>"
                                            <?= $agotado ? 'disabled' : '' ?>>

                                        <span class="modal-producto-nombre">
                                            <?= htmlspecialchars($nombreModal) ?>
                                        </span>

                                        <span class="modal-producto-precio">
                                            <?= soles($precioModal) ?>
                                            <?php if ($agotado): ?>
                                                &middot; Agotado
                                            <?php endif; ?>
                                        </span>
                                    </button>

                                <?php endforeach; ?>
                            </div>

                        <?php endif; ?>
                    </div>

                    <!-- Pedido en curso -->
                    <div class="col-lg-5" style="min-width:0;">

                        <div class="modal-pedido">
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
