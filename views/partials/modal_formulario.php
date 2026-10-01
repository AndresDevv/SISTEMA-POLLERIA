<?php
/**
 * Partial: formulario genérico en modal para el CRUD.
 * Los campos se generan desde la definición del recurso.
 */
?>

<div class="modal fade" id="modalFormulario" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header" style="border-bottom:1px solid #EFEFEF;">
                <h5 class="modal-title" style="font-weight:700;" id="formTitulo">Nuevo registro</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="lg-info mb-3" id="formAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="formAvisoTexto"></span>
                </div>

                <div id="formCampos"></div>

            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cancelar</button>
                <button type="button" class="lg-btn lg-btn--primary" id="formGuardar">
                    <i class="fa fa-check"></i> Guardar
                </button>
            </div>

        </div>
    </div>
</div>
