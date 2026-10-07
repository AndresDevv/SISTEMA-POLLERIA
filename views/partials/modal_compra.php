<?php
/**
 * Partial: detalle de una compra.
 *
 * El contenido lo carga el navegador con api/compra/detalle, así que el
 * modal se imprime una sola vez con la estructura vacía.
 */
?>

<div class="modal fade" id="modalCompra" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header" style="border-bottom:1px solid #EFEFEF;">
                <h5 class="modal-title" style="font-weight:700;">
                    <i class="fa fa-receipt"></i> <span id="compraDetalleTitulo">Compra</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" id="compraDetalleCuerpo">
                <div class="text-center py-4">
                    <i class="fa fa-spinner fa-spin fa-2x" style="opacity:0.4;"></i>
                </div>
            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cerrar</button>
                <button type="button" class="lg-btn lg-btn--rojo" id="compraAnular" style="display:none;">
                    <i class="fa fa-ban"></i> Anular compra
                </button>
            </div>

        </div>
    </div>
</div>