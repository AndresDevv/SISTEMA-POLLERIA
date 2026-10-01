<?php
/**
 * Partial: modal de cobro de un pedido.
 * Permite pagar con uno o varios métodos (pago partido).
 *
 * Variables esperadas: $metodosPago (array de la tabla metodos_pago)
 */

$metodosPago = $metodosPago ?? [];
?>

<div class="modal fade" id="modalCobro" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header align-items-center" style="border-bottom:1px solid #EFEFEF;">
                <div class="d-flex align-items-center gap-3">
                    <span class="lg-card-icon" style="width:44px;height:44px;">
                        <i class="fa fa-money"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" style="font-weight:700;margin:0;">Cobrar pedido</h5>
                        <small class="lg-muted" id="cobroMesa">&mdash;</small>
                    </div>
                </div>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="lg-total-box text-center">
                    <span class="lg-row-label">Total a cobrar</span>
                    <div class="lg-stat-value is-red" style="font-size:1.9rem;" id="cobroTotal">S/ 0.00</div>
                </div>

                <!-- Aviso -->
                <div class="lg-info mb-3" id="cobroAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="cobroAvisoTexto"></span>
                </div>

                <p class="lg-subtitle-block" style="margin-top:18px;">Cómo paga el cliente</p>

                <table class="lg-table lg-table--compact">
                    <thead>
                        <tr>
                            <th style="width:38%;">Método</th>
                            <th>Monto</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cobroFilas"></tbody>
                </table>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <select id="cobroMetodoNuevo" class="lg-select" style="max-width:200px;">
                        <?php foreach ($metodosPago as $metodo): ?>
                            <option value="<?= (int) $metodo['id'] ?>"><?= htmlspecialchars($metodo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" class="lg-btn lg-btn--ghost" id="cobroAgregarLinea">
                        <i class="fa fa-plus"></i> Agregar método
                    </button>
                </div>

                <p class="lg-muted mt-3 mb-0" style="font-size:0.8rem;">
                    Puedes dividir el pago entre varios métodos. El sistema completa
                    la diferencia en el último para cuadrar con el total.
                </p>

            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cancelar</button>
                <button type="button" class="lg-btn lg-btn--verde" id="cobroConfirmar">
                    <i class="fa fa-check"></i> Confirmar cobro
                </button>
            </div>

        </div>
    </div>
</div>
