<?php
/**
 * Partial: reservar una mesa.
 *
 * Pide a nombre de quién queda y a qué hora, porque el salón necesita
 * saber a nombre de quién está reservada.
 */
?>

<div class="modal fade" id="modalReserva" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header" style="border-bottom:1px solid #EFEFEF;">
                <h5 class="modal-title" style="font-weight:700;">
                    <i class="fa fa-calendar" style="color:#F59E0B;"></i>
                    Reservar <span id="reservaMesaTitulo">mesa</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="lg-info mb-3" id="reservaAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="reservaAvisoTexto"></span>
                </div>

                <label class="lg-label" for="reservaNombre">A nombre de qui&eacute;n</label>
                <input type="text" id="reservaNombre" class="lg-input mb-3" maxlength="100"
                       placeholder="Ej: Mar&iacute;a Gonz&aacute;les">

                <label class="lg-label" for="reservaHora">Hora estimada</label>
                <input type="time" id="reservaHora" class="lg-input" value="<?= date('H:i') ?>">

                <p class="lg-muted mt-3 mb-0" style="font-size:0.8rem;">
                    La mesa quedar&aacute; en amarillo y se mostrar&aacute; el nombre
                    del reserva en la tarjeta.
                </p>

            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cancelar</button>
                <button type="button" class="lg-btn lg-btn--primary" id="reservaGuardar">
                    <i class="fa fa-check"></i> Reservar
                </button>
            </div>

        </div>
    </div>
</div>