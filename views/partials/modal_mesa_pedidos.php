<?php
/**
 * Partial: modal con los pedidos de UNA mesa, para el usuario de cocina.
 *
 * Antes la cocina tenía una pestaña con todos los pedidos del salón. Ahora
 * trabaja mesa por mesa: toca una tarjeta y ve aquí solo lo de esa mesa.
 *
 * El diseño NO es el de las tarjetas del panel general: aquí cabe el detalle
 * de una sola mesa, así que se presenta como una comanda de cocina (ticket),
 * con la franja de color al costado, los productos en dos columnas y los
 * pasos del pedido en fila.
 *
 * El contenido se arma en el navegador con api/pedido/listar?mesa_id=N, así
 * el modal nunca queda con datos viejos si algo cambió mientras estaba
 * abierto.
 */

?>

<div class="modal fade" id="modalMesaPedidos" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:14px;border:0;overflow:hidden;">

            <div class="modal-header align-items-center"
                style="border-bottom:1px solid #EFEFEF;background-color:#FAFAFA;">
                <div class="d-flex align-items-center gap-3">
                    <span class="lg-card-icon" style="width:46px;height:46px;">
                        <i class="fa fa-fire"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" style="font-weight:700;margin:0;">
                            Comanda — <span id="mesaPedidosTitulo">Mesa 1</span>
                        </h5>
                        <small class="lg-muted" id="mesaPedidosResumen">Cargando...</small>
                    </div>
                </div>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="max-height:min(72vh,680px);overflow-y:auto;padding:16px 20px;">
                <div id="mesaPedidosLista">
                    <div class="lg-empty">
                        <i class="fa fa-spinner fa-spin"></i>
                        <p class="mb-0">Cargando pedidos...</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="border-top:1px solid #EFEFEF;">
                <small class="lg-muted mr-auto" style="font-size:.78rem;">
                    <i class="fa fa-info-circle"></i>
                    Al sumar productos a esta mesa, se agregan a esta misma comanda.
                </small>
                <button type="button" class="lg-btn lg-btn--ghost" data-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
