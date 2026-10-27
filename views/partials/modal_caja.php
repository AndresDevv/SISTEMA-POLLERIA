<?php
/**
 * Partial: modal de cobro de la Caja.
 *
 * Muestra la cuenta completa de una mesa (todos sus pedidos sin pagar) y
 * permite cobrarla. En efectivo se puede indicar con cuánto paga el cliente
 * y el sistema calcula el vuelto, pensado para billetes grandes.
 *
 * Variables esperadas: $metodosPago (array de la tabla metodos_pago)
 */

$metodosPago = $metodosPago ?? [];
?>

<div class="modal fade" id="modalCaja" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header align-items-center" style="border-bottom:1px solid #EFEFEF;">
                <div class="d-flex align-items-center gap-3">
                    <span class="lg-card-icon" style="width:44px;height:44px;">
                        <i class="fa fa-money"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" style="font-weight:700;margin:0;">Cobrar mesa</h5>
                        <small class="lg-muted" id="cajaMesa">&mdash;</small>
                    </div>
                </div>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <!-- Aviso -->
                <div class="lg-info mb-3" id="cajaAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="cajaAvisoTexto"></span>
                </div>

                <!-- La cuenta: qué se consumió -->
                <p class="lg-subtitle-block">Detalle de la cuenta</p>

                <div class="lg-caja-cuenta" id="cajaCuenta">
                    <p class="lg-muted mb-0">Cargando la cuenta...</p>
                </div>

                <div class="lg-total-box text-center mt-3">
                    <span class="lg-row-label">Total a cobrar</span>
                    <div class="lg-stat-value is-red" style="font-size:1.9rem;" id="cajaTotal">S/ 0.00</div>
                </div>

                <!-- Cómo paga -->
                <p class="lg-subtitle-block" style="margin-top:18px;">Cómo paga el cliente</p>

                <table class="lg-table lg-table--compact">
                    <thead>
                        <tr>
                            <th style="width:42%;">Método</th>
                            <th>Monto</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cajaFilas"></tbody>
                </table>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <select id="cajaMetodoNuevo" class="lg-select" style="max-width:200px;">
                        <?php foreach ($metodosPago as $metodo): ?>
                            <option value="<?= (int) $metodo['id'] ?>"><?= htmlspecialchars($metodo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" class="lg-btn lg-btn--ghost" id="cajaAgregarLinea">
                        <i class="fa fa-plus"></i> Agregar método
                    </button>
                </div>

                <!-- Efectivo: con cuánto paga y cuánto es el vuelto -->
                <div class="lg-caja-efectivo mt-3" id="cajaEfectivo">
                    <p class="lg-subtitle-block mb-2">Pago en efectivo</p>

                    <label class="lg-label" for="cajaRecibido">¿Con cuánto paga el cliente?</label>
                    <input type="number"
                        class="lg-input"
                        id="cajaRecibido"
                        step="0.01"
                        min="0"
                        placeholder="Ej: 100"
                        inputmode="decimal">

                    <div class="lg-caja-billetes" id="cajaBilletes">
                        <button type="button" class="lg-chip js-billete" data-monto="20">S/ 20</button>
                        <button type="button" class="lg-chip js-billete" data-monto="50">S/ 50</button>
                        <button type="button" class="lg-chip js-billete" data-monto="100">S/ 100</button>
                        <button type="button" class="lg-chip js-billete" data-monto="200">S/ 200</button>
                    </div>

                    <div class="lg-caja-vuelto" id="cajaVueltoCaja" style="display:none;">
                        <span class="lg-row-label">Vuelto</span>
                        <div class="lg-stat-value is-green" style="font-size:1.6rem;" id="cajaVuelto">S/ 0.00</div>
                        <small class="lg-muted" id="cajaVueltoTexto"></small>
                    </div>
                </div>

            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cancelar</button>
                <button type="button" class="lg-btn lg-btn--verde" id="cajaConfirmar">
                    <i class="fa fa-check"></i> Confirmar cobro
                </button>
            </div>

        </div>
    </div>
</div>
