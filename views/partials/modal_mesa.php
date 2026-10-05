<?php
/**
 * Partial: agregar mesa al restaurante.
 */
?>

<div class="modal fade" id="modalMesa" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header" style="border-bottom:1px solid #EFEFEF;">
                <h5 class="modal-title" style="font-weight:700;">Agregar mesa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="lg-info mb-3" id="mesaAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="mesaAvisoTexto"></span>
                </div>

                <label class="lg-label" for="mesaCapacidad">Capacidad (personas)</label>
                <input type="number" id="mesaCapacidad" class="lg-input" min="1" max="20" value="4">

                <p class="lg-muted mt-3 mb-0" style="font-size:0.8rem;">
                    La mesa se agrega con el siguiente n&uacute;mero libre y
                    quedar&aacute; en estado Libre.
                </p>

            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cancelar</button>
                <button type="button" class="lg-btn lg-btn--primary" id="mesaGuardar">
                    <i class="fa fa-check"></i> Agregar
                </button>
            </div>

        </div>
    </div>
</div>