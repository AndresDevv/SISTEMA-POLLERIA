<?php
/**
 * Partial: ajuste de stock de un producto.
 */

$metodos = [
    'entrada' => 'Entrada (compra, producción)',
    'salida'  => 'Salida (merma, consumo)',
    'ajuste'  => 'Ajuste (dejar el stock real)'
];
?>

<div class="modal fade" id="modalStock" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content" style="border-radius:12px;border:0;overflow:hidden;">

            <div class="modal-header" style="border-bottom:1px solid #EFEFEF;">
                <h5 class="modal-title" style="font-weight:700;">Ajustar stock</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="lg-info mb-3" id="stockAviso" style="display:none;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span id="stockAvisoTexto"></span>
                </div>

                <p class="lg-muted mb-3" style="font-size:0.88rem;">
                    <strong id="stockProducto">Producto</strong><br>
                    Stock actual: <span id="stockActual">0</span>
                </p>

                <label class="lg-label" for="stockTipo">Movimiento</label>
                <select id="stockTipo" class="lg-select mb-3">
                    <?php foreach ($metodos as $valor => $texto): ?>
                        <option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($texto) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="lg-label" for="stockCantidad" id="stockEtiqueta">Cantidad a ingresar</label>
                <input type="number" id="stockCantidad" class="lg-input mb-3" step="1" min="0" value="0">

                <label class="lg-label" for="stockMotivo">Motivo</label>
                <input type="text" id="stockMotivo" class="lg-input" placeholder="Opcional">

            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;gap:10px;">
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">Cancelar</button>
                <button type="button" class="lg-btn lg-btn--primary" id="stockGuardar">
                    <i class="fa fa-check"></i> Guardar
                </button>
            </div>

        </div>
    </div>
</div>