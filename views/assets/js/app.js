/* =========================================================
   LOS GOMEZ - Comportamiento de las vistas
   ========================================================= */

(function ($) {
    'use strict';

    $(function () {

        // -------------------------------------------------
        // CRUD genérico de los módulos administrativos
        // -------------------------------------------------
        var $formModal = $('#modalFormulario');

        if ($formModal.length) {

            var formulario = { recurso: '', id: 0, campos: {} };
            var opcionesRelacion = {};

            // Carga las opciones de los campos tipo relación
            var cargarRelaciones = function (campos, done) {

                var pendientes = [];

                Object.keys(campos).forEach(function (campo) {

                    var regla = campos[campo];

                    if (regla.tipo === 'relacion' && !opcionesRelacion[regla.tabla]) {
                        pendientes.push(regla.tabla);
                    }
                });

                if (!pendientes.length) {
                    return done();
                }

                var restantes = pendientes.length;

                pendientes.forEach(function (tabla) {
                    $.get(APP_URL + '?page=api/gestion/opciones&tabla=' + tabla)
                        .done(function (r) {
                            opcionesRelacion[tabla] = r.filas || [];
                            if (--restantes === 0) { done(); }
                        })
                        .fail(function () {
                            opcionesRelacion[tabla] = [];
                            if (--restantes === 0) { done(); }
                        });
                });
            };

            var pintarFormulario = function (valores) {

                var $contenedor = $('#formCampos');

                $contenedor.empty();

                Object.keys(formulario.campos).forEach(function (campo) {

                    var regla = formulario.campos[campo];
                    var valor = valores ? valores[campo] : (regla.default !== undefined ? regla.default : '');

                    var $grupo = $('<div class="mb-3"></div>');
                    var $label = $('<label class="lg-label"></label>').text(regla.etiqueta);
                    var $controlo;

                    if (regla.tipo === 'enum') {
                        $controlo = $('<select class="lg-select"></select>');

                        (regla.opciones || []).forEach(function (opcion) {
                            $controlo.append(
                                $('<option></option>').attr('value', opcion).text(opcion.charAt(0).toUpperCase() + opcion.slice(1))
                            );
                        });

                    } else if (regla.tipo === 'relacion') {

                        $controlo = $('<select class="lg-select"></select>');
                        $controlo.append('<option value="">Seleccione...</option>');

                        (opcionesRelacion[regla.tabla] || []).forEach(function (fila) {
                            $controlo.append(
                                $('<option></option>')
                                    .attr('value', fila.id)
                                    .text(fila.nombre)
                                    .prop('selected', String(fila.id) === String(valor))
                            );
                        });

                    } else if (regla.tipo === 'check') {

                        $controlo = $('<select class="lg-select"></select>');
                        $controlo.append('<option value="1"' + (String(valor) === '1' ? ' selected' : '') + '>Activo</option>');
                        $controlo.append('<option value="0"' + (String(valor) === '0' ? ' selected' : '') + '>Inactivo</option>');

                    } else if (regla.tipo === 'password') {

                        $controlo = $('<input type="password" class="lg-input" autocomplete="new-password">');

                    } else if (regla.tipo === 'fecha') {

                        $controlo = $('<input type="date" class="lg-input">');
                        $controlo.val(valor ? String(valor).slice(0, 10) : '');

                    } else if (regla.tipo === 'decimal') {

                        $controlo = $('<input type="number" step="0.01" min="0" class="lg-input">').val(valor);

                    } else {

                        $controlo = $('<input type="text" class="lg-input">').val(valor);
                    }

                    $controlo.attr('data-campo', campo);

                    if (regla.requerido && regla.tipo !== 'check') {
                        $controlo.attr('required', 'required');
                    }

                    $grupo.append($label).append($controlo);
                    $contenedor.append($grupo);
                });
            };

            var abrirFormulario = function (recurso, campos, fila) {

                formulario.recurso = recurso;
                formulario.campos = campos;
                formulario.id = fila ? (fila.id || 0) : 0;

                $('#formTitulo').text((fila ? 'Editar' : 'Nuevo') + ' registro');
                $('#formAviso').hide();

                cargarRelaciones(campos, function () {
                    pintarFormulario(fila);
                    $formModal.modal('show');
                });
            };

            $('.js-nuevo').on('click', function () {
                abrirFormulario($(this).data('recurso'), $(this).data('campos'), null);
            });

            $('.js-editar').on('click', function () {
                var $boton = $(this);
                abrirFormulario($boton.data('recurso'), $boton.data('campos'), $boton.data('fila'));
            });

            $('#formGuardar').on('click', function () {

                var datos = { recurso: formulario.recurso };

                $('#formCampos [data-campo]').each(function () {
                    datos[$(this).data('campo')] = $(this).val();
                });

                if (formulario.id) {
                    datos.id = formulario.id;
                }

                var esEdicion = !!formulario.id;
                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

                api('api/gestion/' + (esEdicion ? 'actualizar' : 'crear'), datos)
                .done(function () {
                    window.location.reload();
                })
                .fail(function (xhr) {
                    $('#formAvisoTexto').text(mensajeDe(xhr, 'No se pudo guardar.'));
                    $('#formAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });

            $('.js-borrar').on('click', function () {

                var $boton = $(this);

                if (!confirm('¿Eliminar este registro?')) {
                    return;
                }

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                api('api/gestion/eliminar', { recurso: $boton.data('recurso'), id: $boton.data('id') })
                .done(function () {
                    window.location.reload();
                })
                .fail(function (xhr) {
                    alert(mensajeDe(xhr, 'No se pudo eliminar.'));
                    $boton.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                });
            });
        }

        // -------------------------------------------------
        // Menú lateral (las 3 rayas del navbar)
        // El plugin PushMenu de AdminLTE no se registra en esta
        // instalación, así que el comportamiento va aquí.
        // -------------------------------------------------
        var CLASE_CERRADO = 'lg-menu-cerrado';
        var MOVIL = 991;

        var estaAbierto = function () {
            return !document.body.classList.contains(CLASE_CERRADO);
        };

        var aplicarMenu = function (abierto) {

            if (abierto) {
                document.body.classList.remove(CLASE_CERRADO);
            } else {
                document.body.classList.add(CLASE_CERRADO);
            }

            try {
                localStorage.setItem(CLASE_CERRADO, abierto ? '1' : '0');
            } catch (e) {}

            // El icono pasa de rayas a aspa
            $('[data-widget="pushmenu"] i')
                .removeClass('fa-bars fa-times')
                .addClass(abierto ? 'fa-bars' : 'fa-times');
        };

        // Restaura la preferencia, pero en móvil siempre arranca cerrado
        try {
            var guardado = localStorage.getItem(CLASE_CERRADO);

            aplicarMenu(guardado === '1' && window.innerWidth > MOVIL);
        } catch (e) {
            aplicarMenu(true);
        }

        $('[data-widget="pushmenu"]').on('click', function (e) {
            e.preventDefault();
            aplicarMenu(!estaAbierto());
        });

        // En móvil, al navegar se cierra el menú
        $('.main-sidebar .nav-link').on('click', function () {
            if (window.innerWidth <= MOVIL) {
                aplicarMenu(false);
            }
        });

        // Tecla Escape cierra el menú
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && !estaAbierto()) {
                aplicarMenu(true);
            }
        });

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
        // Peticiones a la API
        // -------------------------------------------------
        var api = function (ruta, datos) {

            return $.ajax({
                url: APP_URL + '?page=' + ruta,
                type: 'POST',
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                data: JSON.stringify(datos)
            });
        };

        var mensajeDe = function (xhr, porDefecto) {

            try {
                return JSON.parse(xhr.responseText).message || porDefecto;
            } catch (e) {
                return porDefecto;
            }
        };

        // -------------------------------------------------
        // Cambio de estado de un pedido
        // -------------------------------------------------
        $('.js-estado-pedido').on('click', function () {

            var $boton = $(this);
            var original = $boton.html();

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

            api('api/pedido/estado', {
                id: $boton.data('id'),
                estado: $boton.data('estado')
            })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo actualizar el pedido.'));
                $boton.prop('disabled', false).html(original);
            });
        });

        // -------------------------------------------------
        // Modal de cobro (permite pago partido)
        // -------------------------------------------------
        var $cobro = $('#modalCobro');

        if ($cobro.length) {

            var cobro = { id: 0, total: 0, lineas: [] };

            var pintarCobro = function () {

                var $filas = $('#cobroFilas');
                var suma = 0;

                $filas.empty();

                if (!cobro.lineas.length) {
                    cobro.lineas.push({ metodo_pago_id: $('#cobroMetodoNuevo').val(), monto: cobro.total });
                }

                cobro.lineas.forEach(function (linea, indice) {

                    suma += parseFloat(linea.monto) || 0;

                    var $fila = $('<tr></tr>');

                    var $select = $('<select class="lg-select" style="min-height:38px;"></select>');

                    $('#cobroMetodoNuevo option').each(function () {
                        $select.append($('<option></option>').attr('value', $(this).val()).text($(this).text()));
                    });
                    $select.val(linea.metodo_pago_id);

                    var $monto = $('<input type="number" class="lg-input" step="0.01" min="0" style="min-height:38px;">')
                        .val(linea.monto);

                    $select.on('change', function () {
                        cobro.lineas[indice].metodo_pago_id = $(this).val();
                    });

                    $monto.on('input', function () {
                        cobro.lineas[indice].monto = $(this).val();
                        actualizarResumenCobro();
                    });

                    var $quitar = $('<button type="button" class="lg-btn lg-btn--sm lg-btn--ghost" title="Quitar">' +
                                   '<i class="fa fa-times"></i></button>');

                    $quitar.on('click', function () {
                        cobro.lineas.splice(indice, 1);
                        if (!cobro.lineas.length) {
                            cobro.lineas.push({ metodo_pago_id: $('#cobroMetodoNuevo').val(), monto: cobro.total });
                        }
                        pintarCobro();
                    });

                    var $celdaMonto = $('<td></td>').append($monto);
                    var $celdaQuitar = $('<td></td>').append($quitar);

                    $fila.append($('<td></td>').append($select));
                    $fila.append($celdaMonto);
                    $fila.append($celdaQuitar);

                    $filas.append($fila);
                });

                actualizarResumenCobro();
            };

            var actualizarResumenCobro = function () {

                var suma = 0;

                cobro.lineas.forEach(function (l) {
                    suma += parseFloat(l.monto) || 0;
                });

                var diferencia = cobro.total - suma;
                var $aviso = $('#cobroAviso');

                if (Math.abs(diferencia) < 0.01) {
                    $aviso.hide();
                    return;
                }

                $('#cobroAvisoTexto').text(
                    diferencia > 0
                        ? 'Falta ' + soles(diferencia) + ' para completar el total.'
                        : 'Te pasaste por ' + soles(Math.abs(diferencia)) + '.'
                );
                $aviso.show();
            };

            $('#cobroAgregarLinea').on('click', function () {

                var suma = 0;

                cobro.lineas.forEach(function (l) {
                    suma += parseFloat(l.monto) || 0;
                });

                cobro.lineas.push({
                    metodo_pago_id: $('#cobroMetodoNuevo').val(),
                    monto: Math.max(0, cobro.total - suma).toFixed(2)
                });

                pintarCobro();
            });

            $('#cobroConfirmar').on('click', function () {

                var suma = 0;

                cobro.lineas.forEach(function (l) {
                    suma += parseFloat(l.monto) || 0;
                });

                if (Math.abs(cobro.total - suma) > 0.01) {
                    $('#cobroAvisoTexto').text('El pago debe coincidir con el total del pedido.');
                    $('#cobroAviso').show();
                    return;
                }

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cobrando...');

                api('api/pedido/cobrar', { id: cobro.id, pagos: cobro.lineas })
                .done(function () {
                    window.location.reload();
                })
                .fail(function (xhr) {
                    $('#cobroAvisoTexto').text(mensajeDe(xhr, 'No se pudo registrar el cobro.'));
                    $('#cobroAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });

            $('.js-abrir-cobro').on('click', function () {

                var $boton = $(this);

                cobro.id = $boton.data('id');
                cobro.total = parseFloat($boton.data('total')) || 0;
                cobro.lineas = [{
                    metodo_pago_id: $('#cobroMetodoNuevo').val(),
                    monto: cobro.total
                }];

                $('#cobroMesa').text($boton.data('mesa') || '');
                $('#cobroTotal').text(soles(cobro.total));
                $('#cobroAviso').hide();

                pintarCobro();
                $cobro.modal('show');
            });
        }

        // -------------------------------------------------
        // Eliminar un pedido
        // -------------------------------------------------
        $('.js-eliminar-pedido').on('click', function () {

            var $boton = $(this);

            if (!confirm('¿Eliminar este pedido? Esta acción no se puede deshacer.')) {
                return;
            }

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            api('api/pedido/eliminar', { id: $boton.data('id') })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo eliminar el pedido.'));
                $boton.prop('disabled', false).html('<i class="fa fa-trash"></i>');
            });
        });

        // AdminLTE crea contextos de apilamiento en el sidebar y el contenido:
        // cualquier modal dentro de ellos quedaría detrás. Los movemos al body.
        $('.modal').appendTo(document.body);

        // -------------------------------------------------
        // Mesas: reservar, liberar, agregar y eliminar
        // -------------------------------------------------
        $('.js-reservar-mesa').on('click', function () {

            var $boton = $(this);
            var nombre = $boton.data('mesa');
            var quien = prompt('¿A nombre de quién se reserva la ' + nombre + '? (opcional)');

            if (quien === null) {
                return;
            }

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            api('api/mesa/reservar', { id: $boton.data('id'), nombre: quien })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo reservar la mesa.'));
                $boton.prop('disabled', false).html('<i class="fa fa-calendar"></i> Reservar');
            });
        });

        $('.js-liberar-mesa').on('click', function () {

            var $boton = $(this);

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            api('api/mesa/liberar', { id: $boton.data('id') })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo liberar la mesa.'));
                $boton.prop('disabled', false).html('<i class="fa fa-check"></i> Liberar');
            });
        });

        $('.js-eliminar-mesa').on('click', function () {

            var $boton = $(this);
            var mesa = $boton.data('mesa');

            if (!confirm('¿Eliminar la ' + mesa + '? Solo se puede si no tiene pedidos registrados.')) {
                return;
            }

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            api('api/mesa/eliminar', { id: $boton.data('id') })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo eliminar la mesa.'));
                $boton.prop('disabled', false).html('<i class="fa fa-trash"></i>');
            });
        });

        var $mesaModal = $('#modalMesa');

        if ($mesaModal.length) {

            $('.js-agregar-mesa').on('click', function () {
                $('#mesaAviso').hide();
                $('#mesaCapacidad').val(4);
                $mesaModal.modal('show');
            });

            $('#mesaGuardar').on('click', function () {

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Agregando...');

                api('api/mesa/crear', { capacidad: parseInt($('#mesaCapacidad').val(), 10) || 4 })
                .done(function () {
                    window.location.reload();
                })
                .fail(function (xhr) {
                    $('#mesaAvisoTexto').text(mensajeDe(xhr, 'No se pudo agregar la mesa.'));
                    $('#mesaAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });
        }

        // -------------------------------------------------
        // Modal de pedido
        // -------------------------------------------------
        var $modal = $('#modalPedido');

        if (!$modal.length) {
            return;
        }

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
            cargarPedidosDeLaMesa();
            $modal.modal('show');
        };

        /**
         * Muestra los pedidos que ya tiene la mesa.
         */
        var cargarPedidosDeLaMesa = function () {

            var $wrap = $('#pedidoExistentesWrap');
            var $lista = $('#pedidoExistentes');

            if (!pedido.mesaId) {
                $wrap.hide();
                return;
            }

            $wrap.show();
            $lista.html('<p class="lg-muted mb-0"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');

            $.get(APP_URL + '?page=api/pedido/listar&mesa_id=' + pedido.mesaId)
            .done(function (r) {

                if (!r.pedidos || !r.pedidos.length) {
                    $lista.html('<p class="lg-muted mb-0">Esta mesa a&uacute;n no tiene pedidos.</p>');
                    return;
                }

                var html = '';

                r.pedidos.forEach(function (p) {
                    html +=
                        '<div class="lg-mesa lg-mesa--' + p.estado_clase + '" style="cursor:default;margin-bottom:8px;">' +
                            '<div class="lg-mesa-top">' +
                                '<div>' +
                                    '<h3 class="lg-mesa-name">P-' + String(p.id).padStart(4, '0') + '</h3>' +
                                    '<div class="lg-mesa-cap">' + p.items.length + ' producto(s) &middot; ' + soles(p.total) + '</div>' +
                                '</div>' +
                                '<span class="lg-mesa-badge lg-mesa-badge--' + p.estado_clase + '">' +
                                    p.estado_etiqueta +
                                '</span>' +
                            '</div>' +
                            '<div class="lg-mesa-foot" style="border-top:0;padding-top:8px;">' +
                                p.items.map(function (i) {
                                    return '<span style="margin-right:10px;">' + i.cantidad + 'x ' + i.nombre + '</span>';
                                }).join('') +
                            '</div>' +
                        '</div>';
                });

                $lista.html(html);
            })
            .fail(function () {
                $lista.html('<p class="lg-muted mb-0">No se pudieron cargar los pedidos de la mesa.</p>');
            });
        };

        // Abre el modal desde las tarjetas de mesa
        $('.lg-mesa-main').on('click', function () {

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

            api('api/pedido/crear', {
                mesa_id: pedido.mesaId,
                items: pedido.items.map(function (item) {
                    return { id: item.id, cantidad: item.cantidad };
                })
            })
            .done(function () {
                window.location.href = APP_URL + '?page=mesas&panel=pedidos';
            })
            .fail(function (xhr) {
                mostrarAviso(mensajeDe(xhr, 'No se pudo enviar el pedido.'));
                $boton.prop('disabled', false).html(original);
            });
        });

        $modal.on('hidden.bs.modal', function () {
            pedido.items = [];
            pintarPedido();
        });

    });

})(jQuery);
