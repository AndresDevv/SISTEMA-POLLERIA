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

        /**
         * Petición de solo lectura. El router marca estas rutas como soloGet,
         * así que mandarlas por POST devuelve 405 y el modal no carga nada.
         */
        var apiGet = function (ruta, datos) {

            return $.ajax({
                url: APP_URL + '?page=' + ruta,
                type: 'GET',
                dataType: 'json',
                data: datos || {}
            });
        };

        var mensajeDe = function (xhr, porDefecto) {

            try {
                return JSON.parse(xhr.responseText).message || porDefecto;
            } catch (e) {
                return porDefecto;
            }
        };

        /**
         * Formatea un monto. Vive aquí y no dentro de un bloque suelto porque
         * lo usan el modal de pedidos, el de compras y el de stock.
         */
        var soles = function (monto) {
            return 'S/ ' + Number(monto || 0).toFixed(2);
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
        // Ajuste de stock
        // -------------------------------------------------
        var $stockModal = $('#modalStock');

        if ($stockModal.length) {

            var stock = { id: 0 };

            var ajustarEtiqueta = function () {

                var tipo = $('#stockTipo').val();
                var texto = tipo === 'entrada' ? 'Cantidad a ingresar'
                          : tipo === 'salida' ? 'Cantidad a descontar'
                          : 'Stock real (queda en este valor)';

                $('#stockEtiqueta').text(texto);
            };

            $('.js-ajustar-stock').on('click', function () {

                var $boton = $(this);

                stock.id = $boton.data('id');

                $('#stockProducto').text($boton.data('producto'));
                $('#stockActual').text($boton.data('stock'));
                $('#stockTipo').val('entrada');
                $('#stockCantidad').val(0);
                $('#stockMotivo').val('');
                $('#stockAviso').hide();

                ajustarEtiqueta();
                $stockModal.modal('show');
            });

            $('#stockTipo').on('change', ajustarEtiqueta);

            $('#stockGuardar').on('click', function () {

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

                api('api/inventario/ajustar', {
                    producto_id: stock.id,
                    tipo: $('#stockTipo').val(),
                    cantidad: parseFloat($('#stockCantidad').val()) || 0,
                    motivo: $('#stockMotivo').val()
                })
                .done(function () {
                    window.location.reload();
                })
                .fail(function (xhr) {
                    $('#stockAvisoTexto').text(mensajeDe(xhr, 'No se pudo ajustar el stock.'));
                    $('#stockAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });
        }

        // -------------------------------------------------
        // Registrar compra
        // -------------------------------------------------
        var compra = { items: [] };

        var pintarCompra = function () {

            var $filas = $('#compraItems');
            var total = 0;

            $filas.empty();

            if (!compra.items.length) {
                $filas.append(
                    '<tr><td colspan="5" class="text-center text-muted py-3">Todav&iacute;a no agregaste productos.</td></tr>'
                );
            }

            compra.items.forEach(function (item, indice) {

                var subtotal = item.precio * item.cantidad;
                total += subtotal;

                var $fila = $('<tr></tr>');

                $fila.append('<td style="font-weight:600;">' + item.nombre + '</td>');

                var $cantidad = $('<input type="number" class="lg-input" step="0.01" min="0.01" style="min-height:38px;">').val(item.cantidad);
                $cantidad.on('input', function () {
                    compra.items[indice].cantidad = parseFloat($(this).val()) || 0;
                    pintarCompra();
                });

                var $precio = $('<input type="number" class="lg-input" step="0.01" min="0" style="min-height:38px;">').val(item.precio);
                $precio.on('input', function () {
                    compra.items[indice].precio = parseFloat($(this).val()) || 0;
                    pintarCompra();
                });

                $fila.append($('<td></td>').append($cantidad));
                $fila.append($('<td></td>').append($precio));
                $fila.append('<td class="num" style="text-align:right;font-weight:600;">' + soles(subtotal) + '</td>');
                $fila.append(
                    $('<td></td>').append(
                        $('<button type="button" class="lg-btn lg-btn--sm lg-btn--ghost"><i class="fa fa-times"></i></button>')
                            .on('click', function () {
                                compra.items.splice(indice, 1);
                                pintarCompra();
                            })
                    )
                );

                $filas.append($fila);
            });

            $('#compraTotal').text(soles(total));
        };

        $('#compraCatalogo .modal-producto').on('click', function () {

            var $boton = $(this);
            var id = parseInt($boton.data('id'), 10);
            var nombre = $boton.attr('data-nombre-texto');
            var existente = null;

            compra.items.forEach(function (item) {
                if (item.id === id) { existente = item; }
            });

            if (existente) {
                existente.cantidad += 1;
            } else {
                // Se precarga con el precio de venta: el administrador lo ajusta
                // al precio real de compra, pero nunca queda en cero sin querer.
                compra.items.push({
                    id: id,
                    nombre: nombre,
                    cantidad: 1,
                    precio: parseFloat($boton.data('precio')) || 0
                });
            }

            pintarCompra();
        });

        /**
 * Filtra el catálogo por categoría y por texto a la vez.
 */
        var filtrarCatalogo = function () {

            var categoria = $('#compraFiltros .is-activo').data('categoria') || '';
            var texto = $('#compraBuscar').val().toLowerCase().trim();
            var visibles = 0;

            $('#compraCatalogo .modal-producto').each(function () {

                var $b = $(this);
                var coincideCategoria = categoria === ''
                    || String($b.data('categoria')) === categoria;
                var coincideTexto = texto === ''
                    || String($b.data('nombre')).indexOf(texto) !== -1;

                var mostrar = coincideCategoria && coincideTexto;

                $b.toggle(mostrar);

                if (mostrar) {
                    visibles++;
                }
            });

            $('#compraSinResultados').toggle(visibles === 0);
        };

        $('#compraBuscar').on('input', filtrarCatalogo);

        $('#compraFiltros').on('click', '.js-filtro-categoria', function () {

            var $chip = $(this);

            $('#compraFiltros .js-filtro-categoria').removeClass('is-activo');
            $chip.addClass('is-activo');

            filtrarCatalogo();
        });

        $('#compraGuardar').on('click', function () {

            if (!compra.items.length) {
                $('#compraAvisoTexto').text('Agrega al menos un producto.');
                $('#compraAviso').show();
                return;
            }

            var $boton = $(this);
            var textoOriginal = $boton.html();

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
            $('#compraAviso').hide();

            api('api/compra/registrar', {
                proveedor_id: parseInt($('#compraProveedor').val(), 10) || 0,
                items: compra.items.map(function (i) {
                    return { id: i.id, cantidad: i.cantidad, precio: i.precio };
                })
            })
            .done(function () {
                window.location.href = APP_URL + '?page=compras&tab=historial';
            })
            .fail(function (xhr) {
                $('#compraAvisoTexto').text(mensajeDe(xhr, 'No se pudo registrar la compra.'));
                $('#compraAviso').show();
                $boton.prop('disabled', false).html(textoOriginal);
            });
        });

        // -------------------------------------------------
        // Historial de compras: ver detalle y anular
        // -------------------------------------------------
        var $modalCompra = $('#modalCompra');

        if ($modalCompra.length) {

            var compraActual = { id: 0, anulada: false };

            var pintarDetalle = function (datos) {

                var c = datos.compra;

                $('#compraDetalleTitulo').text(
                    'Compra C-' + ('0000' + c.id).slice(-4)
                );

                var cuerpo = '<div class="lg-rows mb-3">'
                    + '<div class="lg-row"><span class="lg-row-label">Proveedor</span>'
                    + '<span class="lg-row-value">' + (c.proveedor || 'Sin proveedor') + '</span></div>'
                    + '<div class="lg-row"><span class="lg-row-label">Registró</span>'
                    + '<span class="lg-row-value">' + (c.usuario || '-') + '</span></div>'
                    + '<div class="lg-row"><span class="lg-row-label">Fecha</span>'
                    + '<span class="lg-row-value">' + c.fecha + '</span></div>'
                    + '<div class="lg-row"><span class="lg-row-label">Estado</span>'
                    + '<span class="lg-row-value">'
                    + (datos.anulada
                        ? '<span class="lg-pill lg-pill--pizarra">Anulada</span>'
                        : '<span class="lg-pill lg-pill--verde">Registrada</span>')
                    + '</span></div>'
                    + '</div>';

                if (datos.anulada) {
                    cuerpo += '<div class="lg-info mb-3"><i class="fa fa-info-circle"></i>'
                        + '<span>Esta compra está anulada: el stock ya se revirtió y '
                        + 'no cuenta en los gastos del día.</span></div>';
                }

                var filas = (c.items || []).map(function (item) {
                    return '<tr>'
                        + '<td>' + item.nombre + '</td>'
                        + '<td class="num" style="text-align:right;">' + item.cantidad + '</td>'
                        + '<td class="num" style="text-align:right;">' + soles(item.precio_unitario) + '</td>'
                        + '<td class="num" style="text-align:right;font-weight:600;">'
                        + soles(item.subtotal) + '</td>'
                        + '</tr>';
                }).join('');

                cuerpo += '<div class="table-responsive"><table class="lg-table">'
                    + '<thead><tr><th>Producto</th>'
                    + '<th style="text-align:right;">Cantidad</th>'
                    + '<th style="text-align:right;">Precio unit.</th>'
                    + '<th style="text-align:right;">Subtotal</th></tr></thead>'
                    + '<tbody>' + filas + '</tbody>'
                    + '<tfoot><tr><th colspan="3">'
                    + (c.items || []).length + ' producto(s) · '
                    + (c.items || []).reduce(function (suma, i) {
                        return suma + parseFloat(i.cantidad);
                    }, 0)
                    + ' unidad(es)</th>'
                    + '<th style="text-align:right;">' + soles(c.total) + '</th></tr></tfoot>'
                    + '</table></div>';

                $('#compraDetalleCuerpo').html(cuerpo);

                $('#compraAnular').toggle(!datos.anulada);
            };

            $('.js-ver-compra').on('click', function () {

                var id = $(this).data('id');

                compraActual = { id: id, anulada: false };

                $('#compraDetalleCuerpo').html(
                    '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x" style="opacity:0.4;"></i></div>'
                );

                $modalCompra.modal('show');

                apiGet('api/compra/detalle', { id: id })
                .done(function (r) {
                    compraActual.anulada = r.anulada;
                    pintarDetalle(r);
                })
                .fail(function (xhr) {
                    $('#compraDetalleCuerpo').html(
                        '<p class="mb-0">' + mensajeDe(xhr, 'No se pudo cargar la compra.') + '</p>'
                    );
                });
            });

            $('.js-anular-compra').on('click', function () {
                compraActual = { id: $(this).data('id'), anulada: false };
                $(this).closest('tr').find('.js-ver-compra').trigger('click');
            });

            $('#compraAnular').on('click', function () {

                if (!confirm(
                    'Se anulará la compra y se devolverá el stock al almacén.\n'
                    + 'También se quitará del gasto del día.\n\n¿Continuar?'
                )) {
                    return;
                }

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Anulando...');

                api('api/compra/anular', { id: compraActual.id })
                .done(function () {
                    $modalCompra.modal('hide');
                    window.location.reload();
                })
                .fail(function (xhr) {
                    alert(mensajeDe(xhr, 'No se pudo anular la compra.'));
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });
        }
        $('.js-asistencia').on('click', function () {

            var $boton = $(this);
            var textoOriginal = $boton.html();

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            api('api/personal/asistencia', {
                empleado_id: $boton.data('emp'),
                tipo: $boton.data('tipo')
            })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo registrar la asistencia.'));
                $boton.prop('disabled', false).html(textoOriginal);
            });
        });

        $('#pagoGuardar').on('click', function () {

            var $boton = $(this);
            var textoOriginal = $boton.html();

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            api('api/personal/pago', {
                empleado_id: parseInt($('#pagoEmpleado').val(), 10) || 0,
                periodo: $('#pagoPeriodo').val(),
                monto: parseFloat($('#pagoMonto').val()) || 0
            })
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                $('#pagoAvisoTexto').text(mensajeDe(xhr, 'No se pudo registrar el pago.'));
                $('#pagoAviso').show();
                $boton.prop('disabled', false).html(textoOriginal);
            });
        });

        // -------------------------------------------------
        // Mesas: reservar, liberar, agregar y eliminar
        // -------------------------------------------------
        var $modalReserva = $('#modalReserva');

        if ($modalReserva.length) {

            var reservaActual = { id: 0 };

            $('.js-reservar-mesa').on('click', function () {

                reservaActual.id = $(this).data('id');

                $('#reservaMesaTitulo').text($(this).data('mesa'));
                $('#reservaNombre').val('');
                $('#reservaHora').val(
                    new Date().toTimeString().substring(0, 5)
                );
                $('#reservaAviso').hide();

                $modalReserva.modal('show');

                setTimeout(function () {
                    $('#reservaNombre').trigger('focus');
                }, 300);
            });

            $('#reservaGuardar').on('click', function () {

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
                $('#reservaAviso').hide();

                api('api/mesa/reservar', {
                    id: reservaActual.id,
                    nombre: $('#reservaNombre').val(),
                    hora: $('#reservaHora').val()
                })
                .done(function () {
                    $modalReserva.modal('hide');
                    window.location.reload();
                })
                .fail(function (xhr) {
                    $('#reservaAvisoTexto').text(mensajeDe(xhr, 'No se pudo reservar la mesa.'));
                    $('#reservaAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });
        }

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

        var mostrarAviso = function (texto) {
            $('#pedidoAvisoTexto').text(texto);
            $('#pedidoAviso').stop(true, true).fadeIn(150);
        };

        var ocultarAviso = function () {
            $('#pedidoAviso').stop(true, true).fadeOut(150);
        };

        /**
         * Stock en el almacén de un producto del catálogo.
         */
        var $botonStock = function (idProducto) {
            return $('#modalCatalogo .modal-producto[data-producto-id="' + idProducto + '"]').data('stock');
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

                var $sumar = $('<button type="button" title="Agregar uno"><i class="fa fa-plus"></i></button>')
                    .on('click', function () {

                        var stock = parseInt($botonStock(item.id), 10);

                        if (stock > 0 && item.cantidad >= stock) {
                            mostrarAviso(
                                'Solo quedan ' + stock + ' de "' + item.nombre + '" en el almacén.'
                            );

                            return;
                        }

                        item.cantidad += 1;
                        pintarPedido();
                    });

                $qtd.append($sumar);

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

            // El modal solo se imprime si el rol puede registrar pedidos
            if (!$('#modalPedido').length) {
                return;
            }

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
            var stock  = parseInt($boton.data('stock'), 10);
            var existente = null;

            pedido.items.forEach(function (item) {
                if (item.nombre === nombre) {
                    existente = item;
                }
            });

            // No se deja pasar más unidades de las que hay en el almacén
            var yaPedidas = existente ? existente.cantidad : 0;

            if (stock > 0 && yaPedidas >= stock) {
                mostrarAviso(
                    'Solo quedan ' + stock + ' de "' + nombre + '" en el almacén.'
                );

                return;
            }

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
