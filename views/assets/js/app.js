/* =========================================================
   LOS GOMEZ - Comportamiento de las vistas
   ========================================================= */

(function ($) {
    'use strict';

    $(function () {

        // -------------------------------------------------
        // Búsqueda rápida del catálogo (vista Pedidos)
        // -------------------------------------------------
        $('#filtroCatalogoPedidos').on('input', function () {

            var texto = $(this).val().toLowerCase().trim();

            $('.js-filtra-catalogo').each(function () {

                var $boton = $(this);
                var coincide = texto === '' ||
                    String($boton.data('nombre')).indexOf(texto) !== -1;

                $boton.toggle(coincide);
            });
        });

        // -------------------------------------------------
        // Segmentos (Ver mesas / Ver pedidos / Ver ventas)
        // -------------------------------------------------
        $('.lg-segmento').on('click', function () {
            $('.lg-segmento').removeClass('is-activo');
            $(this).addClass('is-activo');
        });

        // -------------------------------------------------
        // Cambio de estado de un pedido
        // -------------------------------------------------
        $('.js-estado-pedido').on('click', function () {

            var $boton = $(this);
            var original = $boton.html();

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

            $.post(APP_URL + '?page=api/pedido/estado', {
                id: $boton.data('id'),
                estado: $boton.data('estado')
            })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                var mensaje = 'No se pudo actualizar el pedido.';

                try {
                    mensaje = JSON.parse(xhr.responseText).message || mensaje;
                } catch (e) {}

                alert(mensaje);
                $boton.prop('disabled', false).html(original);
            });
        });

        // -------------------------------------------------
        // Modal de pedido
        // -------------------------------------------------
        var $modal = $('#modalPedido');

        if (!$modal.length) {
            return;
        }

        // AdminLTE crea contextos de apilamiento en el sidebar y el contenido,
        // por lo que el modal quedaría por detrás. Lo movemos al <body>.
        $modal.appendTo(document.body);

        var pedido = {
            mesaId: 0,
            mesa: '',
            capacidad: '',
            items: []
        };

        var soles = function (monto) {
            return 'S/ ' + Number(monto).toFixed(2);
        };

        var mostrarAviso = function (texto) {
            $('#pedidoAvisoTexto').text(texto);
            $('#pedidoAviso').stop(true, true).fadeIn(150);
        };

        var ocultarAviso = function () {
            $('#pedidoAviso').stop(true, true).fadeOut(150);
        };

        var pintarPedido = function () {

            var $lista = $('#pedidoLista');
            var total = 0;
            var unidades = 0;

            $lista.empty();

            if (!pedido.items.length) {
                $lista.append(
                    '<p class="lg-muted text-center py-4 mb-0">Todav&iacute;a no hay productos en el pedido.</p>'
                );
            }

            pedido.items.forEach(function (item, indice) {

                var subtotal = item.precio * item.cantidad;

                total += subtotal;
                unidades += item.cantidad;

                var $fila = $('<div class="modal-item"></div>');

                $fila.append(
                    '<div class="modal-item-nombre">' + item.nombre +
                    '<span class="modal-item-sub">' + soles(item.precio) + ' c/u</span></div>'
                );

                var $acciones = $('<div class="modal-item-acciones"></div>');
                var $qtd = $('<div class="modal-qty"></div>');

                $qtd.append(
                    $('<button type="button" title="Quitar uno"><i class="fa fa-minus"></i></button>')
                        .on('click', function () {
                            if (item.cantidad > 1) {
                                item.cantidad -= 1;
                            } else {
                                pedido.items.splice(indice, 1);
                            }
                            pintarPedido();
                        })
                );

                $qtd.append('<span>' + item.cantidad + '</span>');

                $qtd.append(
                    $('<button type="button" title="Agregar uno"><i class="fa fa-plus"></i></button>')
                        .on('click', function () {
                            item.cantidad += 1;
                            pintarPedido();
                        })
                );

                $acciones.append($qtd);
                $acciones.append('<div class="modal-item-total">' + soles(subtotal) + '</div>');

                $acciones.append(
                    $('<button type="button" class="modal-item-quitar" title="Quitar del pedido">' +
                      '<i class="fa fa-times"></i></button>')
                        .on('click', function () {
                            pedido.items.splice(indice, 1);
                            pintarPedido();
                        })
                );

                $fila.append($acciones);
                $lista.append($fila);
            });

            $('#pedidoTotal').text(soles(total));
            $('#pedidoItemsCount').text(unidades + (unidades === 1 ? ' item' : ' items'));
        };

        var abrirModal = function (datos) {

            pedido.mesaId    = datos.mesaId || 0;
            pedido.mesa      = datos.mesa || '';
            pedido.capacidad = datos.capacidad || '';

            $('#pedidoMesa').text(pedido.mesa);
            $('#pedidoMesaInfo').text(pedido.capacidad);

            ocultarAviso();
            pintarPedido();
            $modal.modal('show');
        };

        // Abre el modal desde las tarjetas de mesa
        $('.lg-mesa').on('click', function () {

            var $card = $(this);

            abrirModal({
                mesaId: $card.data('mesa-id'),
                mesa: $card.data('mesa'),
                capacidad: $card.data('capacidad')
            });
        });

        // Abre el modal desde los botones de mesa
        $('[data-abrir-pedido]').on('click', function () {

            var $boton = $(this);

            abrirModal({
                mesaId: $boton.data('mesa-id'),
                mesa: $boton.data('mesa'),
                capacidad: $boton.data('capacidad')
            });
        });

        // Agrega un producto al pedido
        $('.modal-producto').on('click', function () {

            var $boton = $(this);
            var nombre = $boton.data('producto');
            var id     = parseInt($boton.data('producto-id'), 10);
            var precio = parseFloat($boton.data('precio')) || 0;
            var existente = null;

            pedido.items.forEach(function (item) {
                if (item.nombre === nombre) {
                    existente = item;
                }
            });

            if (existente) {
                existente.cantidad += 1;
            } else {
                pedido.items.push({ id: id, nombre: nombre, precio: precio, cantidad: 1 });
            }

            ocultarAviso();
            pintarPedido();
        });

        // Filtra el catálogo del modal
        var filtrarCatalogo = function () {

            var texto = $('#modalBuscar').val().toLowerCase().trim();
            var categ = $('#modalCategoria').val();

            $('.modal-producto').each(function () {

                var $boton = $(this);
                var coincide = true;

                if (texto !== '' && String($boton.data('nombre')).indexOf(texto) === -1) {
                    coincide = false;
                }

                if (categ !== '' && $boton.data('categoria') !== categ) {
                    coincide = false;
                }

                $boton.toggle(coincide);
            });
        };

        $('#modalBuscar, #modalCategoria').on('input change', filtrarCatalogo);

        // Descarta el pedido en curso
        $('#pedidoLimpiar').on('click', function () {
            pedido.items = [];
            pintarPedido();
        });

        // Envía el pedido a la cocina
        $('#pedidoEnviarCocina').on('click', function () {

            if (!pedido.items.length) {
                mostrarAviso('Agrega al menos un producto antes de enviar el pedido.');
                return;
            }

            if (!pedido.mesaId) {
                mostrarAviso('No se pudo identificar la mesa.');
                return;
            }

            ocultarAviso();

            var $boton = $(this);
            var original = $boton.html();

            $boton.prop('disabled', true)
                  .html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

            $.post(APP_URL + '?page=api/pedido/crear', {
                mesa_id: pedido.mesaId,
                items: pedido.items.map(function (item) {
                    return { id: item.id, cantidad: item.cantidad };
                })
            })
            .done(function (r) {
                window.location.href = APP_URL + '?page=mesas&panel=pedidos';
            })
            .fail(function (xhr) {
                var mensaje = 'No se pudo enviar el pedido.';

                try {
                    mensaje = JSON.parse(xhr.responseText).message || mensaje;
                } catch (e) {}

                mostrarAviso(mensaje);
                $boton.prop('disabled', false).html(original);
            });
        });

        $modal.on('hidden.bs.modal', function () {
            pedido.items = [];
            pintarPedido();
        });

    });

})(jQuery);
