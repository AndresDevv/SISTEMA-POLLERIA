/* =========================================================
   LOS GOMEZ - Comportamiento de las vistas
   ========================================================= */

(function ($) {
    'use strict';

    $(function () {

        // -------------------------------------------------
        // CRUD genérico de los módulos administrativos
        // -------------------------------------------------
        /* Se busca cada vez que se usa. El motor de tiempo real repone los
           modales cada 5 segundos y una referencia guardada apuntaria al
           elemento viejo, que ya no esta en la pagina: el formulario se
           llenaba pero no se abria. */
        var formModal = function () {
            return $('#modalFormulario');
        };

        if (formModal().length) {

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

            /**
             * Deja un número como se escribe a mano.
             *
             * La base guarda 2.50, pero en el formulario se ve "2.5": los
             * ceros de más solo estorban cuando se teclea.
             */
            var numeroEditable = function (valor) {
                var n = parseFloat(valor);

                if (isNaN(n)) {
                    return '';
                }

                return String(n);
            };

            /**
             * Convierte lo que se escribió en un número.
             *
             * Acepta coma o punto como separador decimal, porque el teclado de
             * las tablets no siempre trae el punto a la vista.
             */
            var aNumero = function (texto) {
                return parseFloat(String(texto === null || texto === undefined ? '' : texto)
                    .replace(',', '.')) || 0;
            };

            var pintarFormulario = function (valores) {

                var $contenedor = $('#formCampos');

                $contenedor.empty();

                Object.keys(formulario.campos).forEach(function (campo) {

                    /**
                     * "_calculo" no es un campo del formulario.
                     *
                     * Viaja dentro del mismo objeto de campos, pero son las
                     * instrucciones para armar el total. Si se dibujara,
                     * aparecería un campo vacío sin etiqueta al final, por eso
                     * se salta.
                     */
                    if (campo.charAt(0) === '_') {
                        return;
                    }

                    var regla = formulario.campos[campo];

                    /**
                     * "soloNuevo" no se muestra al editar.
                     *
                     * Sirve para el stock de los productos: se escribe al
                     * crearlos, pero una vez existentes la unica forma de
                     * moverlo es el boton Ajustar, que deja el movimiento en el
                     * historial. Si el campo saliera al editar, el stock se
                     * moveria en silencio y quedarian dos caminos para lo
                     * mismo, uno con rastro y otro sin el.
                     */
                    if (regla.soloNuevo && formulario.id) {
                        return;
                    }

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

                        // Los decimales admiten coma o punto, porque en el
                        // teclado de la tablet muchas veces se escribe "2,5".
                        $controlo = $('<input type="text" inputmode="decimal" class="lg-input">')
                            .val(valor === null || valor === undefined || valor === '' ? '' : numeroEditable(valor));

                        $controlo.on('input', function () {
                            this.value = this.value.replace(/[^0-9.,]/g, '');
                        });

                    } else if (regla.tipo === 'calculado') {

                        /**
                         * El total no se escribe: se arma solo.
                         *
                         * Se muestra como campo normal para que se lea de un
                         * vistazo, pero no se puede editar a mano. El número
                         * que se guarda lo calcula el servidor; este de aquí
                         * solo evita teclearlo.
                         */
                        $controlo = $('<input type="text" class="lg-input" readonly style="background:#F7F7F8;font-weight:700;">')
                            .val(valor ? soles(valor) : soles(0));

                    } else if (regla.tipo === 'digitos') {

                        // Solo números: el teclado del móvil muestra el
                        // teclado numérico y se descartan letras y símbolos
                        $controlo = $('<input type="text" inputmode="numeric" pattern="[0-9]*" class="lg-input">');
                        $controlo.val(valor === null || valor === undefined ? '' : String(valor).replace(/\D/g, ''));

                        $controlo.on('input', function () {
                            this.value = this.value.replace(/\D/g, '');
                        });

                    } else {

                        $controlo = $('<input type="text" class="lg-input">').val(valor);
                    }

                    $controlo.attr('data-campo', campo);

                    if (regla.requerido && regla.tipo !== 'check') {
                        $controlo.attr('required', 'required');
                    }

                    // Pista dentro del campo, cuando el recurso la declara
                    if (regla.placeholder) {
                        $controlo.attr('placeholder', regla.placeholder);
                    }

                    $grupo.append($label).append($controlo);

                    /* Ayuda bajo el campo.
                       Explica qué se espera escribir cuando el nombre del
                       campo puede prestarse a confusión (por ejemplo, que
                       "Unidad de medida" es una palabra como "litros" y no
                       un número). Como el texto es largo, el campo ocupa
                       las dos columnas para que se lea completo. */
                    if (regla.ayuda) {
                        $grupo.addClass('is-ancho');

                        var $explica = $('<small class="lg-muted d-block mt-1"></small>')
                            .text(regla.ayuda);

                        $grupo.append($explica);
                    }

                    // Ayuda para los campos que solo aceptan dígitos
                    if (regla.tipo === 'digitos') {
                        var $ayuda = $('<small class="lg-muted"></small>')
                            .text('Solo ' + regla.digitos + ' dígitos, sin letras ni símbolos.');

                        $grupo.append($ayuda);

                        $controlo.on('input', function () {
                            var largo = this.value.length;
                            var bien = largo === Number(regla.digitos) || largo === 0;

                            $ayuda.css('color', bien ? '' : '#EF4444');
                        });
                    }

                    $contenedor.append($grupo);
                });

                /**
                 * El total se va solo con cantidad por precio.
                 *
                 * Se recalcula en el formulario para que quien compra vea
                 * cuanto sale antes de guardar. Es solo una ayuda para leerlo
                 * bien: el número que queda guardado lo calcula el servidor,
                 * que es el que suma en Caja y en el Dashboard.
                 */
                var camposTotal = formulario.campos._calculo || null;

                if (camposTotal) {
                    var recalcular = function () {
                        var cantidad = aNumero($('#formCampos [data-campo="' + camposTotal.cantidad + '"]').val());
                        var precio = aNumero($('#formCampos [data-campo="' + camposTotal.precio + '"]').val());
                        var $total = $('#formCampos [data-campo="' + camposTotal.campo + '"]');

                        $total.val(soles(cantidad * precio));
                    };

                    $('#formCampos [data-campo="' + camposTotal.cantidad + '"],'
                        + ' #formCampos [data-campo="' + camposTotal.precio + '"]').on('input', recalcular);

                    recalcular();
                }
            };

            var abrirFormulario = function (recurso, campos, fila) {

                formulario.recurso = recurso;
                formulario.campos = campos;
                formulario.id = fila ? (fila.id || 0) : 0;

                $('#formTitulo').text((fila ? 'Editar' : 'Nuevo') + ' registro');
                $('#formAviso').hide();

                cargarRelaciones(campos, function () {
                    pintarFormulario(fila);
                    formModal().modal('show');
                });
            };

            $(document).on('click', '.js-nuevo', function () {
                abrirFormulario($(this).data('recurso'), $(this).data('campos'), null);
            });

            $(document).on('click', '.js-editar', function () {
                var $boton = $(this);
                abrirFormulario($boton.data('recurso'), $boton.data('campos'), $boton.data('fila'));
            });

            $(document).on('click', '#formGuardar', function () {

                var datos = { recurso: formulario.recurso };

                /**
                 * Primero se revisan los campos obligatorios.
                 *
                 * El navegador no valida solo: el botón de guardar no es
                 * un "submit" del formulario, así que el "required" que
                 * llevan los campos no se ejecuta y una compra podía
                 * quedar guardada sin producto, sin cantidad o sin
                 * precio. Aquí se revisa a mano y, si falta algo, se
                 * avisa dentro del modal y no se envía nada.
                 */
                var faltantes = [];

                for (var campoObligatorio in formulario.campos) {

                    var reglaOblig = formulario.campos[campoObligatorio];

                    if (!reglaOblig.requerido || reglaOblig.tipo === 'check') {
                        continue;
                    }

                    var $control = $('#formCampos [data-campo="' + campoObligatorio + '"]');

                    if (!$control.length) {
                        continue;
                    }

                    if ($.trim(String($control.val() || '')) === '') {
                        faltantes.push(reglaOblig.etiqueta || campoObligatorio);
                        $control.addClass('is-error');
                    } else {
                        $control.removeClass('is-error');
                    }
                }

                if (faltantes.length) {
                    $('#formAvisoTexto').text(
                        faltantes.length === 1
                            ? 'Falta completar: ' + faltantes[0] + '.'
                            : 'Faltan completar: ' + faltantes.join(', ') + '.'
                    );
                    $('#formAviso').show();

                    // Se lleva el cursor al primer campo que falta
                    $('#formCampos .is-error').first().trigger('focus');
                    return;
                }

                // Revisa antes de mandar: si un campo de dígitos va mal, se
                // avisa dentro del modal en vez de enviar y recargar
                for (var campo in formulario.campos) {

                    var regla = formulario.campos[campo];
                    var $c = $('#formCampos [data-campo="' + campo + '"]');
                    var valor = $c.length ? String($c.val() || '').replace(/\D/g, '') : '';

                    if (!regla.digitos) {
                        continue;
                    }

                    if (valor !== '' && valor.length !== Number(regla.digitos)) {
                        $('#formAvisoTexto').text(
                            regla.etiqueta + ' debe tener ' + regla.digitos
                            + ' dígitos (escribiste ' + valor.length + ').'
                        );
                        $('#formAviso').show();
                        $c.trigger('focus');
                        return;
                    }
                }

                $('#formCampos [data-campo]').each(function () {
                    var $c = $(this);
                    var campo = String($c.data('campo'));

                    if (campo.charAt(0) === '_') {
                        return;
                    }

                    var valor = $c.val();

                    /**
                     * Los decimales van como número.
                     *
                     * Se limpian los separadores de miles y se cambia la coma
                     * por punto, para que "1,50" llegue al servidor como 1.50 y
                     * no como un texto que no se sabe interpretar.
                     */
                    if ($c.is('[inputmode="decimal"]')) {
                        datos[campo] = aNumero(valor);
                        return;
                    }

                    datos[campo] = valor;
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

                    /**
                     * Se repinta la pantalla en vez de recargarla.
                     *
                     * Si se recargara, las cifras de Caja y del Dashboard
                     * tardarian en verse y habria que volver a entrar. Con el
                     * refresco del motor de tiempo real, el total del dia queda
                     * al dia en el momento en que se guarda la compra.
                     */
                    formModal().modal('hide');

                    window.setTimeout(function () {
                        window.refrescarContenido();
                    }, 350);
                })
                .fail(function (xhr) {
                    $('#formAvisoTexto').text(mensajeDe(xhr, 'No se pudo guardar.'));
                    $('#formAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });

            /* Al corregir un campo que había quedado marcado, se le quita
               el resaltado y se esconde el aviso: así el formulario deja
               de verse "en rojo" apenas la persona arregla lo que faltaba. */
            $(document).on('input change', '#formCampos .is-error', function () {

                var $c = $(this);

                if ($.trim(String($c.val() || '')) !== '') {
                    $c.removeClass('is-error');
                }

                if (!$('#formCampos .is-error').length) {
                    $('#formAviso').hide();
                }
            });

            $(document).on('click', '.js-borrar', function () {

                var $boton = $(this);
                var recurso = $boton.data('recurso');

                /**
                 * Las compras avisan antes de borrarse.
                 *
                 * Cada compra ya tiene un egreso entrado en el dia, asi que
                 * quitarla cambia lo que el negocio gasto. El aviso lo dice de
                 * forma explicita, y no como un "¿esta seguro?" generico del
                 * que no se entiende la consecuencia.
                 */
                var mensaje = $boton.data('aviso') || '¿Eliminar este registro?';

                if (!confirm(mensaje)) {
                    return;
                }

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                api('api/gestion/eliminar', { recurso: recurso, id: $boton.data('id') })
                .done(function () {

                    // Igual que al guardar: se repinta, no se recarga la pagina.
                    window.setTimeout(function () {
                        window.refrescarContenido();
                    }, 350);
                })
                .fail(function (xhr) {
                    alert(mensajeDe(xhr, 'No se pudo eliminar.'));
                    $boton.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                });
            });
        }

        // -------------------------------------------------
        // Buscador que filtra la tabla sin recargar la página
        // -------------------------------------------------
        /* Va aquí, antes de los bloques que solo corren cuando la página
           trae el modal de pedidos, porque esas pantallas salen antes de
           tiempo y este buscador tiene que quedar registrado siempre.

           El formulario manda a su página con el parámetro "buscar". En
           vez de recargar toda la pantalla —se pierde el menú, la pestaña
           activa y el punto donde se estaba—, se pide la página al
           servidor y se repinta solo el contenido, igual que hace el
           sondeo en tiempo real.

           La dirección exacta (con su pestaña, si la tiene) la trae el
           formulario en "data-buscar-url", así que sirve igual para
           Personal, Inventario, Finanzas, Servicios o Configuración. */
        $(document).on('submit', 'form.input-group', function (e) {

            e.preventDefault();

            var $form = $(this);
            var termino = $.trim($form.find('input[name="buscar"]').val());
            var base = $form.data('buscar-url') || APP_URL;

            var url = base;

            if (termino) {
                url += '&buscar=' + encodeURIComponent(termino);
            }

            // La URL nueva queda en el historial: el botón "atrás" del
            // navegador vuelve a la lista completa sin recargar.
            window.history.pushState({}, '', url);

            /* El campo se desenfoca antes de repintar: con un campo
               enfocado el motor de tiempo real entiende que alguien
               sigue escribiendo y pospone el repintado. */
            $form.find('input[name="buscar"]').blur();

            window.refrescarContenido();
        });

        /* Al volver o avanzar con el botón del navegador, el contenido
           se repinta para que quede alineado con la URL. */
        window.addEventListener('popstate', function () {
            if (window.refrescarContenido) {
                window.refrescarContenido();
            }
        });

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

        /* En tablet el menú es una capa encima del contenido, así que se
           cierra al tocar al costado, como en cualquier aplicación de móvil.
           Eso evita tener que volver a tocar las rayas de arriba, que con el
           menú abierto quedan tapadas.

           Se escucha en fase de captura para que el toque no llegue antes al
           botón o a la fila que hay debajo, y se descartan el propio botón de
           las rayas, lo que está dentro del menú y los modales.

           Se usa addEventListener y no jQuery porque jQuery toma un tercer
           argumento booleano como datos del evento, no como fase de captura:
           el manejador se quedaba sin función y el menú no cerraba. */
        document.addEventListener('click', function (e) {

            if (window.innerWidth > MOVIL || !estaAbierto()) {
                return;
            }

            var objetivo = e.target;

            if (!objetivo || !objetivo.closest) {
                return;
            }

            if (objetivo.closest('[data-widget="pushmenu"]')
                || objetivo.closest('.main-sidebar')
                || objetivo.closest('.modal')) {
                return;
            }

            aplicarMenu(false);
        }, true);

        // Tecla Escape cierra el menú
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && estaAbierto() && window.innerWidth <= MOVIL) {
                aplicarMenu(false);
            }
        });

        // -------------------------------------------------
        // Búsqueda rápida del catálogo (vista Pedidos)
        // -------------------------------------------------
        $(document).on('input', '#filtroCatalogoPedidos', function () {

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
        // Delegado: los segmentos tambien se repueven al refrescar la pantalla
        $(document).on('click', '.lg-segmento', function () {
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

        // Se exponen fuera del cierre para que los scripts que van dentro de
        // las vistas puedan usarlas sin tener que incluirlas otra vez.
        window.api = api;
        window.apiGet = apiGet;

        var mensajeDe = function (xhr, porDefecto) {

            try {
                return JSON.parse(xhr.responseText).message || porDefecto;
            } catch (e) {
                return porDefecto;
            }
        };

        window.mensajeDe = mensajeDe;

        // -------------------------------------------------
        /**
         * Suena cuando entra un pedido que el cocinero todavia no habia visto.
         *
         * Es la campana de un restaurante: dos golpes, "din" agudo y "dong"
         * grave. Se sintetiza con Web Audio en vez de un archivo mp3: no hay
         * que cargar nada, funciona sin conexion y pesa cero.
         *
         * Una campana de verdad no es una sola nota: es un fundamental con
         * varios parciales que se van apagando a ritmos distintos. Por eso
         * suenan metalicos y no como un pitido, y por eso mismo el segundo
         * golpe suena mas grave y dura mas.
         *
         * Los navegadores no dejan sonar nada hasta que la persona toca algo,
         * asi que ademas del sonido se avisa en pantalla.
         */
        var Alarma = (function () {

            var contexto = null;
            var primerToque = false;

            /** Crea el AudioContext la primera vez (los navegadores lo exigen). */
            var despertar = function () {

                if (primerToque) {
                    return;
                }

                primerToque = true;

                var Contexto = window.AudioContext || window.webkitAudioContext;

                if (Contexto) {
                    try { contexto = new Contexto(); } catch (e) { contexto = null; }
                }
            };

            /**
             * Un golpe de campana.
             *
             * Cada parcial es una frecuencia propia con su propio decaimiento:
             * los agudos se apagan antes que los graves, que es justo lo que
             * hace que una campana suene a campana y no a beep.
             */
            var golpe = function (base, inicio, fuerza) {

                // Proporciones reales de una campana de restaurant
                var parciales = [
                    { razon: 1.00, nivel: 1.00, decaimiento: 1.00 },  // fundamental
                    { razon: 2.00, nivel: 0.55, decaimiento: 0.62 },  // octava
                    { razon: 2.76, nivel: 0.38, decaimiento: 0.45 },  // quinta maior
                    { razon: 4.07, nivel: 0.20, decaimiento: 0.30 },  // dos octavas
                    { razon: 5.43, nivel: 0.12, decaimiento: 0.20 },  // partial agudo
                    { razon: 0.50, nivel: 0.30, decaimiento: 1.35 }   // subgrave, da cuerpo
                ];

                parciales.forEach(function (p) {

                    var oscilador = contexto.createOscillator();
                    var volumen = contexto.createGain();

                    oscilador.type = 'sine';
                    oscilador.frequency.value = base * p.razon;

                    var pico = fuerza * p.nivel;
                    var vida = 1.9 * p.decaimiento;

                    // Ataque curtido y caida exponencial: el timbre de metal
                    volumen.gain.setValueAtTime(0.0001, inicio);
                    volumen.gain.exponentialRampToValueAtTime(pico, inicio + 0.006);
                    volumen.gain.exponentialRampToValueAtTime(0.0001, inicio + vida);

                    oscilador.connect(volumen);
                    volumen.connect(contexto.destination);

                    oscilador.start(inicio);
                    oscilador.stop(inicio + vida + 0.05);
                });
            };

            return {
                despertar: despertar,

                /** Un toque de campana de restaurante. */
                tocar: function () {

                    despertar();

                    if (!contexto) {
                        return false;
                    }

                    if (contexto.state === 'suspended') {
                        contexto.resume();
                    }

                    // Un solo golpe. Suena a campana por sus parciales y por
                    // la cola larga, no por repetirlo: al sonar dos veces se
                    // confundia con dos pedidos distintos.
                    golpe(1174, contexto.currentTime, 0.28);

                    return true;
                }
            };
        })();

        // La alarma la dispara el script que va dentro de la vista de mesas
        window.Alarma = Alarma;

        // -------------------------------------------------
        // Avisosemergentes
        // -------------------------------------------------
        /**
         * Cartel flotante con un mensaje.
         *
         * Se usa para confirmar o explicar un cambio puntual, cuando el
         * refresco automático se encarga del resto y no hay un botón que
         * quede pulsado que ya lo diga.
         *
         * Vive fuera del recuadro de contenido, así que sobrevive al
         * refresco de los datos y no lo borra el que viene después.
         */
        window.Alerta = (function () {

            var DURACION_MS = 3200;

            /**
             * @param {string} texto  mensaje a mostrar
             * @param {string} tipo   'ok', 'error' o 'alerta'
             */
            var mostrar = function (texto, tipo) {

                if (!texto) {
                    return;
                }

                var iconos = {
                    ok: 'fa-check-circle',
                    error: 'fa-times-circle',
                    alerta: 'fa-bell'
                };

                var cartel = document.createElement('div');
                cartel.className = 'lg-flash lg-flash--' + (tipo || 'ok');
                cartel.setAttribute('role', tipo === 'error' ? 'alert' : 'status');

                cartel.innerHTML = '<i class="fa ' + (iconos[tipo] || iconos.ok) + '"></i>'
                    + '<div>' + texto + '</div>';

                document.body.appendChild(cartel);

                window.setTimeout(function () {
                    $(cartel).fadeOut(300, function () {
                        cartel.remove();
                    });
                }, DURACION_MS);
            };

            return {
                ok: function (texto) { mostrar(texto, 'ok'); },
                error: function (texto) { mostrar(texto, 'error'); },
                info: function (texto) { mostrar(texto, 'ok'); },
                mostrar: mostrar
            };
        })();

        // La alarma la dispara el script que va dentro de la vista de mesas
        window.Alarma = Alarma;

        // -------------------------------------------------
        // Tiempo real: toda la pantalla se sola
        // -------------------------------------------------
        /**
         * Consulta cada pocos segundos si algo cambió en el sistema y, si
         * cambió, actualiza la página sin que nadie tenga que recargar.
         *
         * No se refresca si hay un modal abierto o si alguien está escribiendo
         * en un campo: en ese caso se avisa con una barrita y se actualiza
         * apenas quede libre, para no borrar lo que la persona estaba
         * escribiendo ni dejarla a medias de un cobro.
         */
        var TiempoReal = (function () {

            var CADA_CUANTO_MS = 5000;
            var firma = '';
            var pendiente = false;
            var aviso = null;
            var arrancado = false;

            /** ¿Se puede tocar la pantalla sin riesgo? */
            var seguro = function () {

                if (document.querySelector('.modal.show')) {
                    return false;
                }

                // Un campo enfocado significa que alguien está escribiendo
                if (document.activeElement) {

                    var activo = document.activeElement;

                    if (activo.tagName === 'INPUT'
                        || activo.tagName === 'TEXTAREA'
                        || activo.tagName === 'SELECT') {
                        return false;
                    }
                }

                return true;
            };

            var mostrarBarra = function () {

                if (aviso) {
                    return;
                }

                aviso = document.createElement('div');
                aviso.className = 'lg-flash lg-flash--pulso';
                aviso.setAttribute('role', 'status');
                aviso.innerHTML = '<i class="fa fa-refresh"></i>'
                    + '<span>Hay informaci&oacute;n nueva. Actualizando...</span>';

                document.body.appendChild(aviso);
            };

            var quitarBarra = function () {

                if (aviso) {
                    aviso.remove();
                    aviso = null;
                }
            };

            /**
             * Actualiza SOLO el contenido de la pantalla, sin recargar.
             *
             * Se vuelve a pedir la página al servidor y se cambia el interior
             * del recuadro de contenido por el nuevo. Así el menú, el usuario y
             * la posición dentro de una lista se quedan donde están.
             *
             * Los modales viven fuera de ese recuadro (se mudan al body al
             * cargar) y los botones usan eventos delegados sobre document, así
             * que todo sigue funcionando tras el cambio.
             *
             * @param function|null alTerminar  se llama solo si la pantalla se
             *        repintó de verdad, que es cuando el cambio puede darse por
             *        visto.
             * @param function|null alFallar    se llama si la petición no pudo
             *        completarse, para que el cambio siga pendiente y se
             *        reintente solo en el siguiente sondeo.
             */
            var refrescarContenido = function (alTerminar, alFallar) {

                /**
                 * Con un modal abierto o un campo enfocado no se toca nada.
                 *
                 * Cambiar el contenido por debajo dejaria a medias lo que la
                 * persona esta haciendo, y ademas se perderia un modal
                 * abierto: al meter el HTML nuevo se borra el que estaba en
                 * pantalla. Se espera un poco y se reintenta cuando quede libre.
                 */
                if (!seguro()) {
                    window.setTimeout(function () {
                        refrescarContenido(alTerminar, alFallar);
                    }, 1500);
                    return;
                }

                var $contenido = $('#lg-contenido');

                if (!$contenido.length) {
                    window.location.replace(window.location.href);
                    return;
                }

                var url = window.location.href;

                $.ajax({ url: url, dataType: 'html' })
                .done(function (html) {

                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var nuevo = doc.getElementById('lg-contenido');

                    // Si la respuesta no trae el recuadro, no se toca nada
                    if (!nuevo) {
                        window.location.replace(url);
                        return;
                    }

                    // Los <script> del contenido no se ejecutan al asignar
                    // innerHTML, y está bien: el sondeo que ya corre sigue con
                    // su estado y no se duplica.
                    $contenido.html(nuevo.innerHTML);

                    /**
                     * Los modales hay que mudarlos otra vez al body.
                     *
                     * Al cargar la pagina se mudan para que sus capas queden
                     * por encima del menu. Si no se hiciera tras el refresco,
                     * cada cambio dejaria un modal nuevo atrapado dentro del
                     * contenido y el de verdad se quedaria abierto con el
                     * "Cargando pedidos..." congelado: dos elementos con el
                     * mismo id, y el primero que se encuentra gana.
                     */
                    $('body > .modal').remove();
                    $contenido.find('.modal').appendTo(document.body);

                    if (typeof alTerminar === 'function') {
                        alTerminar();
                    }
                })
                .fail(function () {

                    /**
                     * La pantalla no se pudo repintar.
                     *
                     * Se avisa para que el cambio siga marcado como pendiente y
                     * se vuelva a intentar en el siguiente sondeo. Sin esto, el
                     * fallo se comeria el cambio en silencio y la pantalla
                     * quedaria desfasada hasta que alguien recargara a mano.
                     */
                    if (typeof alFallar === 'function') {
                        alFallar();
                    }
                });
            };

            window.refrescarContenido = refrescarContenido;

            // -------------------------------------------------
            // Catálogo del modal de pedidos, al día con el modal abierto
            // -------------------------------------------------
            /* Con un modal abierto la pantalla no se repinta, porque se
               perdería el pedido que se está armando. Pero el catálogo de
               platos sí puede cambiar sin tocar nada de eso, así que se
               repinta por su cuenta.

               Solo se cambia la lista de productos: el pedido en curso, lo ya
               agregado y los totales quedan como están. El HTML lo pinta el
               servidor con el mismo partial que usa al abrir el modal, y va
               con una firma, así que si no cambió no se repinta nada y no se
               nota. Al repintar se devuelve el scroll donde estaba. */
            var catalogoVivo = (function () {

                var CADA_CUANTO_CATALOGO_MS = 6000;
                var firmaUltima = '';

                /** El catálogo, pero solo si su modal está a la vista. */
                var catalogoVisible = function () {
                    var modal = document.querySelector('.modal.show');

                    return modal ? modal.querySelector('#modalCatalogo') : null;
                };

                /**
                 * Revisa el catálogo del modal y lo repinta si cambió.
                 *
                 * @return {boolean} true si había un modal abierto y se
                 *         acaba de mirar su catálogo. Lo usa el motor de
                 *         tiempo real para saber si tiene sentido seguir
                 *         anunciando un refresco de pantalla.
                 */
                var consultar = function () {
                    var catalogo = catalogoVisible();

                    // El modal está cerrado: no hay nada que mirar y se
                    // olvida la firma, para que al abrirlo de nuevo se
                    // compare contra la de ahora y no contra la vieja.
                    if (!catalogo) {
                        firmaUltima = '';
                        return false;
                    }

                    apiGet('api/menu/catalogo')
                    .done(function (r) {

                        if (!r || !r.success || !r.html) {
                            return;
                        }

                        if (r.firma === firmaUltima) {
                            return;
                        }

                        firmaUltima = r.firma;

                        // Se vuelve a buscar el catálogo: entre la consulta y
                        // este momento el mesero pudo cerrar el modal.
                        var destino = catalogoVisible();

                        if (!destino) {
                            return;
                        }

                        var dondeEstaba = $('.modal.show .lg-modal-pane--catalogo .lg-modal-pane-scroll').scrollTop() || 0;

                        destino.innerHTML = r.html;

                        // Se devuelve el scroll donde estaba: repintar el
                        // catálogo lo sube de golpe al principio y, con
                        // veinte platos, el mesero perdería el sitio.
                        var scrolls = $('.modal.show .lg-modal-pane--catalogo .lg-modal-pane-scroll');
                        scrolls.scrollTop(dondeEstaba);

                        Alerta.info('El menú se actualizó.');
                    });

                    return true;
                };

                return {
                    consultar: consultar
                };
            })();

            /**
             * Refresca el contenido y da por visto lo que habia pendiente.
             *
             * La firma se guarda SOLO cuando la pantalla se repinto de verdad,
             * no antes. Si se guardara antes, y esa peticion fallara, el
             * siguiente sondeo compararia contra una firma ya nueva, diria
             * que no cambio nada y la pantalla se quedaria con el dato viejo
             * para siempre: el unico arreglo seria recargar a mano. Con la
             * firma donde toca, el cambio sigue pendiente y se reintenta solo
             * en el siguiente sondeo.
             *
             * @param string|null nuevaFirma  la firma del servidor, que se
             *        guarda aqui al terminar bien el refresco.
             */
            var actualizar = function (nuevaFirma) {

                quitarBarra();

                refrescarContenido(function () {

                    pendiente = false;

                    if (nuevaFirma) {
                        firma = nuevaFirma;
                    }
                }, function () {

                    /**
                     * El refresco fallo. "pendiente" se queda en true a
                     * proposito: el siguiente sondeo volvera a detectar el
                     * cambio y lo reintentara solo.
                     */
                    mostrarBarra();
                });
            };

            /** Cuando queda libre, se actualiza lo que quedó pendiente. */
            var intentarActualizar = function () {

                if (!pendiente || !seguro()) {
                    return;
                }

                // Un peldaño de margen: si el usuario sigue escribiendo, se
                // vuelve a intentar en el siguiente ciclo.
                window.setTimeout(function () {

                    if (seguro()) {
                        actualizar();
                    }

                }, 600);
            };

            var consultar = function () {

                apiGet('api/pulso', { firma: firma })
                .done(function (r) {

                    // Primera vuelta: solo se guarda la firma de referencia
                    if (!firma) {
                        firma = r.firma;
                        return;
                    }

                    /**
                     * Nada cambio desde la ultima vuelta.
                     *
                     * Pero si venia algo pendiente, se reintenta aqui y no
                     * solo al abrir o cerrar un modal: si el cambio se detecto
                     * con un modal abierto, esa senal ya habia pasado y la
                     * pantalla se quedaba congelada para siempre.
                     */
                    if (!pendiente && !r.cambia) {
                        firma = r.firma;
                        return;
                    }

                    pendiente = true;

                    /**
                     * La firma SOLO se mueve cuando la pantalla se refresca
                     * de verdad.
                     *
                     * Si se guardara antes, un cambio detectado con un modal
                     * abierto se daria por visto sin llegar a mostrarse, y la
                     * tabla se quedaria con el dato viejo para siempre.
                     */
                    if (seguro()) {
                        actualizar(r.firma);
                    } else {
                        /**
                         * Hay un modal abierto, así que la pantalla no se
                         * toca. Pero el catálogo del modal de pedidos sí
                         * puede repintarse sin perder lo que se está
                         * armando, y por eso se le pregunta aparte.
                         */
                        var catLogoActualizado = catalogoVivo.consultar();

                        /**
                         * El cartel de "actualizando" solo tiene sentido si
                         * de verdad va a venir un refresco de la pantalla.
                         * Con el modal abierto no lo hay, y dejarlo puesto
                         * prometía una actualización que el usuario no iba
                         * a ver nunca: el catálogo sí cambia, el resto no.
                         *
                         * Lo que queda pendiente se actualiza al cerrar el
                         * modal, que es donde el propio motor ya está
                         * escuchando.
                         */
                        if (!catLogoActualizado) {
                            mostrarBarra();
                        }
                    }
                });
            };

            return {

                arrancar: function () {

                    if (arrancado || !apiGet) {
                        return;
                    }

                    arrancado = true;


                    window.setInterval(consultar, CADA_CUANTO_MS);

                    /**
                     * El catálogo del modal se mira por su cuenta.
                     *
                     * El refresco normal se detiene en cuanto hay un modal
                     * abierto, así que si solo se comprobara dentro de él,
                     * un cambio que llegue con el modal abierto no volvería
                     * a mirarse hasta que se cerrara. Con este intervalo
                     * propio pasa lo contrario: se repinta el catálogo en
                     * caliente aunque la pantalla entera siga congelada.
                     */
                    window.setInterval(catalogoVivo.consultar, 6000);

                    // Si el modal se cierra o el campo pierde el foco, se
                    // actualiza lo que quedó pendiente.
                    $(document).on('shown.bs.modal hidden.bs.modal', function () {
                        window.setTimeout(intentarActualizar, 800);
                    });

                    $(document).on('focusout', function () {
                        window.setTimeout(intentarActualizar, 1200);
                    });

                    // La pestaña que vuelve al frente puede tener datos viejos
                    $(window).on('focus', function () {
                        consultar();
                    });
                }
            };
        })();

        window.TiempoReal = TiempoReal;

        // El primer clic en cualquier parte "despierta" el audio del navegador
        $(document).on('click', function () { Alarma.despertar(); });

        /**
         * El tiempo real arranca aquí y no al final del archivo a propósito:
         * más abajo hay un return temprano que corta todo lo que sigue para
         * quien no puede registrar pedidos (el usuario de cocina), y ese rol
         * necesita las actualizaciones tanto como los demás.
         */
        TiempoReal.arrancar();

        /**
         * Clic en una tarjeta de mesa.
         *
         * Va aquí y no junto al modal de pedido a propósito: más abajo hay un
         * return temprano que corta este archivo entero para quien no puede
         * registrar pedidos (el usuario de cocina), y si el binding viviera
         * después nunca se engancharía.
         *
         * Se delega en document para no depender de cuándo se pintó la rejilla.
         */
        $(document).on('click', '.lg-mesa-main', function () {

            var $card = $(this);

            // Cocina no registra pedidos: quiere ver los de esa mesa y
            // avanzar su estado.
            if ($card.data('ver-pedidos')) {
                window.abrirPedidosDeLaMesa($card.data('mesa-id'), $card.data('mesa'));
                return;
            }

            if (window.abrirModalPedido) {
                window.abrirModalPedido({
                    mesaId: $card.data('mesa-id'),
                    mesa: $card.data('mesa'),
                    capacidad: $card.data('capacidad')
                });
            }
        });

        // -------------------------------------------------
        // Detalle de los pedidos de una mesa (usuario de cocina)
        // -------------------------------------------------
        /* El modal se busca cada vez que se usa, en vez de guardarlo en una
           variable al cargar la pagina.

           El motor de tiempo real, cada 5 segundos, cambia el HTML del
           contenido y vuelve a poner los modales en el body. El modal viejo
           se borra y entra uno nuevo: una referencia guardada se queda
           apuntando a un elemento que ya no esta en la pagina, y al abrirlo
           no aparece nada. Por eso se vuelve a buscar cada vez. */
        var modalMesa = function () {
            return $('#modalMesaPedidos');
        };

        if (modalMesa().length) {

            /**
             * Los cuatro pasos por los que pasa un pedido en cocina.
             *
             * El índice es el estado; el último ya está servido y no lleva
             * botón, porque ya no hay nada que hacer en cocina.
             */
            var PASOS = [
                { clave: 'pendiente',  nombre: 'Recibido', accion: 'preparando', texto: 'Aceptar',        icono: 'fa fa-play' },
                { clave: 'preparando', nombre: 'En cocina', accion: 'preparado',  texto: 'Marcar listo',  icono: 'fa fa-check' },
                { clave: 'preparado',  nombre: 'Listo',     accion: 'entregado',  texto: 'Servir',        icono: 'fa fa-cutlery' },
                { clave: 'entregado',  nombre: 'Servido',   accion: '',           texto: '',              icono: '' }
            ];

            var indiceDelEstado = function (estado) {
                for (var i = 0; i < PASOS.length; i++) {
                    if (PASOS[i].clave === estado) { return i; }
                }
                return 0;
            };

            /** Las rayitas de progreso del pie de la comanda. */
            var pintarPasos = function (estado) {
                var actual = indiceDelEstado(estado);

                return PASOS.map(function (paso, i) {
                    var clase = i < actual ? ' is-hecho' : (i === actual ? ' is-actual' : '');
                    return '<span class="lg-comanda-paso' + clase + '">'
                        + '<span class="lg-comanda-punto"></span>' + paso.nombre
                        + '</span>';
                }).join('');
            };

            window.pintarPedidosMesa = function (mesaId) {

                var $lista = $('#mesaPedidosLista');

                $lista.html('<div class="lg-empty"><i class="fa fa-spinner fa-spin"></i>'
                    + '<p class="mb-0">Cargando pedidos...</p></div>');

                apiGet('api/pedido/listar', { mesa_id: mesaId })
                .done(function (r) {

                    var pedidos = r.pedidos || [];

                    if (!pedidos.length) {
                        $('#mesaPedidosResumen').text('');
                        $lista.html('<div class="lg-empty"><i class="fa fa-check-circle-o"></i>'
                            + '<p class="mb-0">Esta mesa no tiene pedidos sin cobrar.</p></div>');
                        return;
                    }

                    var porServir = pedidos.filter(function (p) {
                        return p.estado !== 'entregado';
                    }).length;

                    $('#mesaPedidosResumen').text(
                        pedidos.length + ' comanda(s) · ' + porServir + ' por preparar · ' + soles(
                            pedidos.reduce(function (suma, p) { return suma + p.total; }, 0)
                        )
                    );

                    // Orden de llegada: la primera comanda va arriba
                    var html = '';

                    pedidos.forEach(function (p) {

                        var paso = PASOS[indiceDelEstado(p.estado)];
                        var unidades = (p.items || []).reduce(function (s, i) {
                            return s + (parseInt(i.cantidad, 10) || 0);
                        }, 0);

                        html +=
                            '<div class="lg-comanda lg-comanda--' + p.estado + '">' +
                                '<div class="lg-comanda-cuerpo">' +

                                    '<div class="lg-comanda-cabeza">' +
                                        '<span class="lg-comanda-codigo">P-' + String(p.id).padStart(4, '0')
                                            + '<small>' + unidades + ' producto(s)</small></span>' +
                                        '<span class="lg-comanda-total">' + soles(p.total) + '</span>' +
                                    '</div>' +

                                    '<ul class="lg-comanda-items">' +
                                        (p.items || []).map(function (i) {
                                            return '<li class="lg-comanda-item">'
                                                + '<span class="lg-comanda-cantidad">' + i.cantidad + '</span>'
                                                + '<span class="lg-comanda-nombre">' + i.nombre + '</span>'
                                            + '</li>';
                                        }).join('') +
                                    '</ul>' +

                                    '<div class="lg-comanda-pie">' +
                                        '<div class="lg-comanda-pasos">' + pintarPasos(p.estado) + '</div>' +
                                        (paso.accion
                                            ? '<button type="button" class="lg-btn lg-btn--primary lg-btn--sm lg-cocina-avanzar"'
                                                + ' data-id="' + p.id + '" data-estado="' + paso.accion + '">'
                                                + '<i class="' + paso.icono + '"></i> ' + paso.texto + '</button>'
                                            : '<span class="lg-muted" style="font-size:.8rem;">'
                                                + '<i class="fa fa-check"></i> Servido, esperando que lo cobren</span>') +
                                    '</div>' +

                                '</div>' +
                            '</div>';
                    });

                    $lista.html(html);
                })
                .fail(function () {
                    $lista.html('<div class="lg-empty"><i class="fa fa-exclamation-circle"></i>'
                        + '<p class="mb-0">No se pudieron cargar los pedidos de la mesa.</p></div>');
                });
            };

            window.abrirPedidosDeLaMesa = function (mesaId, mesa) {

        modalMesa().data('mesa-id', mesaId);
                $('#mesaPedidosTitulo').text(mesa || 'Mesa');
                $('#mesaPedidosResumen').text('Cargando...');

                modalMesa().modal('show');

                window.pintarPedidosMesa(mesaId);
            };
        }

        /**
         * Formatea un monto. Vive aquí y no dentro de un bloque suelto porque
         * lo usan el modal de pedidos, el de cobro y el de stock.
         */
        var soles = function (monto) {
            return 'S/ ' + Number(monto || 0).toFixed(2);
        };

        // -------------------------------------------------
        // Cambio de estado de un pedido
        // -------------------------------------------------
        // El estado de un pedido SOLO se cambia desde las tarjetas de cocina
        // (panel general o detalle de mesa). La tabla de "Ver pedidos" es
        // de consulta.
        $(document).on('click', '.lg-cocina-avanzar', function () {

            var $boton = $(this);
            var original = $boton.html();

            $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

            api('api/pedido/estado', {
                id: $boton.data('id'),
                estado: $boton.data('estado')
            })
            .done(function () {

                // Si el detalle está abierto se repinta en el sitio; si no,
                // se recarga la cuadrícula para que la mesa cambie de estado.
                if ($('#mesaPedidosLista').length && $('#modalMesaPedidos').hasClass('show')) {
                    window.pintarPedidosMesa($('#modalMesaPedidos').data('mesa-id'));
                }

                window.location.reload();
            })
            .fail(function (xhr) {
                alert(mensajeDe(xhr, 'No se pudo actualizar el pedido.'));
                $boton.prop('disabled', false).html(original);
            });
        });

        // -------------------------------------------------
        // Caja: cobro de la cuenta completa de una mesa
        // -------------------------------------------------
        /* El modal se busca cada vez que se usa: el motor de tiempo real
           repone los modales y una referencia guardada apuntaria al
           elemento viejo, que ya no esta en la pagina. */
        var modalCaja = function () {
            return $('#modalCaja');
        };

        if (modalCaja().length) {

            // Estado del cobro en curso
            var caja = { mesaId: 0, mesa: '', total: 0, lineas: [], recibido: 0 };

            /** Pinta el detalle de la cuenta: cada pedido con sus platos. */
            var pintarCuenta = function (pedidos) {

                var $contenedor = $('#cajaCuenta').empty();

                if (!pedidos.length) {
                    $contenedor.append('<p class="lg-muted mb-0">Esta mesa no tiene pedidos por cobrar.</p>');
                    return;
                }

                pedidos.forEach(function (pedido) {

                    var $bloque = $('<div class="lg-caja-pedido"></div>');

                    $bloque.append(
                        $('<div class="lg-caja-pedido-head"></div>')
                            .append($('<span></span>').text('P-' + String(pedido.id).padStart(4, '0')))
                            .append($('<span class="lg-muted"></span>').text(soles(pedido.total)))
                    );

                    (pedido.items || []).forEach(function (item) {

                        $bloque.append(
                            $('<div class="lg-caja-item"></div>')
                                .append($('<span></span>').text(item.cantidad + ' × ' + item.nombre))
                                .append($('<span class="lg-muted"></span>').text(soles(item.subtotal)))
                        );
                    });

                    $contenedor.append($bloque);
                });
            };

            /** Repinta las filas de métodos de pago. */
            var pintarCaja = function () {

                var $filas = $('#cajaFilas');
                var suma = 0;

                $filas.empty();

                if (!caja.lineas.length) {
                    caja.lineas.push({ metodo_pago_id: $('#cajaMetodoNuevo').val(), monto: caja.total });
                }

                caja.lineas.forEach(function (linea, indice) {

                    suma += parseFloat(linea.monto) || 0;

                    var $select = $('<select class="lg-select" style="min-height:38px;"></select>');

                    $('#cajaMetodoNuevo option').each(function () {
                        $select.append($('<option></option>').attr('value', $(this).val()).text($(this).text()));
                    });
                    $select.val(linea.metodo_pago_id);

                    var $monto = $('<input type="number" class="lg-input" step="0.01" min="0" style="min-height:38px;">')
                        .val(linea.monto);

                    $select.on('change', function () {
                        caja.lineas[indice].metodo_pago_id = $(this).val();
                    });

                    $monto.on('input', function () {
                        caja.lineas[indice].monto = $(this).val();
                        actualizarResumenCaja();
                    });

                    var $quitar = $('<button type="button" class="lg-btn lg-btn--sm lg-btn--ghost" title="Quitar">' +
                                   '<i class="fa fa-times"></i></button>');

                    $quitar.on('click', function () {
                        caja.lineas.splice(indice, 1);
                        if (!caja.lineas.length) {
                            caja.lineas.push({ metodo_pago_id: $('#cajaMetodoNuevo').val(), monto: caja.total });
                        }
                        pintarCaja();
                    });

                    $filas.append(
                        $('<tr></tr>')
                            .append($('<td></td>').append($select))
                            .append($('<td></td>').append($monto))
                            .append($('<td></td>').append($quitar))
                    );
                });

                actualizarResumenCaja();
            };

            /**
             * Revisa que lo pagado cuadre con el total y calcula el vuelto.
             *
             * El vuelto solo tiene sentido si el cliente paga en efectivo con
             * un billete grande: se compara lo que entregó contra el total.
             */
            var actualizarResumenCaja = function () {

                var suma = 0;

                caja.lineas.forEach(function (l) {
                    suma += parseFloat(l.monto) || 0;
                });

                var diferencia = caja.total - suma;
                var $aviso = $('#cajaAviso');

                if (Math.abs(diferencia) < 0.01) {
                    $aviso.hide();
                } else {
                    $('#cajaAvisoTexto').text(
                        diferencia > 0
                            ? 'Falta ' + soles(diferencia) + ' para completar la cuenta.'
                            : 'Lo indicado se pasa por ' + soles(Math.abs(diferencia)) + '.'
                    );
                    $aviso.show();
                }

                // Vuelto: con cuánto paga menos el total de la cuenta
                var recibido = parseFloat($('#cajaRecibido').val()) || 0;
                var vuelto   = recibido - caja.total;

                if (recibido > 0 && vuelto >= -0.01) {

                    $('#cajaVuelto').text(soles(Math.max(0, vuelto)));
                    $('#cajaVueltoTexto').text(
                        'El cliente paga con ' + soles(recibido) + ' y la cuenta es ' + soles(caja.total) + '.'
                    );
                    $('#cajaVueltoCaja').show();

                } else if (recibido > 0) {

                    $('#cajaVuelto').text(soles(0));
                    $('#cajaVueltoTexto').text('Con ese monto no alcanza para cubrir la cuenta.');
                    $('#cajaVueltoCaja').show();

                } else {
                    $('#cajaVueltoCaja').hide();
                }
            };

            // Botones de billetes: llenan "con cuánto paga"
            $(document).on('click', '.js-billete', function () {
                $('#cajaRecibido').val($(this).data('monto'));
                actualizarResumenCaja();
            });

            $(document).on('input', '#cajaRecibido', actualizarResumenCaja);

            $(document).on('click', '#cajaAgregarLinea', function () {

                var suma = 0;

                caja.lineas.forEach(function (l) {
                    suma += parseFloat(l.monto) || 0;
                });

                caja.lineas.push({
                    metodo_pago_id: $('#cajaMetodoNuevo').val(),
                    monto: Math.max(0, caja.total - suma).toFixed(2)
                });

                pintarCaja();
            });

            // Abrir el modal: se pide la cuenta al servidor
            $(document).on('click', '.js-abrir-caja', function () {

                var $boton = $(this);

                caja.mesaId = $boton.data('mesa-id');
                caja.mesa   = $boton.data('mesa') || '';

                $('#cajaMesa').text(caja.mesa);
                $('#cajaCuenta').html('<p class="lg-muted mb-0">Cargando la cuenta...</p>');
                $('#cajaAviso').hide();
                $('#cajaRecibido').val('');
                $('#cajaVueltoCaja').hide();
                $('#cajaFilas').empty();

                apiGet('api/caja/cuenta', { mesa_id: caja.mesaId })
                .done(function (r) {

                    if (!r || !r.success) {
                        alert('No se pudo cargar la cuenta de la mesa.');
                        return;
                    }

                    caja.total = parseFloat(r.total) || 0;
                    caja.lineas = [{
                        metodo_pago_id: $('#cajaMetodoNuevo').val(),
                        monto: caja.total
                    }];

                    $('#cajaMesa').text(r.mesa || caja.mesa);
                    $('#cajaTotal').text(soles(caja.total));

                    pintarCuenta(r.pedidos || []);
                    pintarCaja();
                    modalCaja().modal('show');
                })
                .fail(function () {
                    alert('No se pudo cargar la cuenta de la mesa.');
                });
            });

            // Confirmar el cobro de toda la cuenta
            $(document).on('click', '#cajaConfirmar', function () {

                var suma = 0;

                caja.lineas.forEach(function (l) {
                    suma += parseFloat(l.monto) || 0;
                });

                if (Math.abs(caja.total - suma) > 0.01) {
                    $('#cajaAvisoTexto').text('El pago debe coincidir con el total de la cuenta.');
                    $('#cajaAviso').show();
                    return;
                }

                var recibido = parseFloat($('#cajaRecibido').val()) || 0;

                if (recibido > 0 && recibido < caja.total - 0.01) {
                    $('#cajaAvisoTexto').text('El cliente no puede pagar con menos que el total.');
                    $('#cajaAviso').show();
                    return;
                }

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cobrando...');

                api('api/caja/cobrar', {
                    mesa_id: caja.mesaId,
                    pagos: caja.lineas,
                    monto_recibido: recibido
                })
                .done(function () {
                    /* Se cierra el modal antes de refrescar: el motor de
                       tiempo real no repinta la pantalla mientras haya un
                       modal abierto. */
                    modalCaja().modal('hide');

                    $boton.prop('disabled', false).html(textoOriginal);

                    window.setTimeout(function () {
                        window.refrescarContenido();
                    }, 400);
                })
                .fail(function (xhr) {
                    $('#cajaAvisoTexto').text(mensajeDe(xhr, 'No se pudo registrar el cobro.'));
                    $('#cajaAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });
        }

        // -------------------------------------------------
        // Eliminar un pedido
        // -------------------------------------------------
        $(document).on('click', '.js-eliminar-pedido', function () {

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
        /* Se busca cada vez que se usa. El motor de tiempo real repone los
           modales cada 5 segundos y una referencia guardada apuntaria al
           elemento viejo, que ya no esta en la pagina. */
        var stockModal = function () {
            return $('#modalStock');
        };

        if (stockModal().length) {

            var stock = { id: 0 };

            var ajustarEtiqueta = function () {

                var tipo = $('#stockTipo').val();
                var texto = tipo === 'entrada' ? 'Cantidad a ingresar'
                          : tipo === 'salida' ? 'Cantidad a descontar'
                          : 'Stock real (queda en este valor)';

                $('#stockEtiqueta').text(texto);
            };

            $(document).on('click', '.js-ajustar-stock', function () {

                var $boton = $(this);

                stock.id = $boton.data('id');

                $('#stockProducto').text($boton.data('producto'));
                $('#stockActual').text($boton.data('stock'));
                $('#stockTipo').val('entrada');
                $('#stockCantidad').val(0);
                $('#stockAviso').hide();

                ajustarEtiqueta();
        stockModal().modal('show');
            });

            $(document).on('change', '#stockTipo', ajustarEtiqueta);

            $(document).on('click', '#stockGuardar', function () {

                var $boton = $(this);
                var textoOriginal = $boton.html();

                $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

                api('api/inventario/ajustar', {
                    producto_id: stock.id,
                    tipo: $('#stockTipo').val(),
                    cantidad: parseInt($('#stockCantidad').val(), 10) || 0
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

        $(document).on('click', '.js-asistencia', function () {

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

        $(document).on('click', '#pagoGuardar', function () {

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
        /* Se busca cada vez que se usa. El motor de tiempo real repone los
           modales cada 5 segundos y una referencia guardada apuntaria al
           elemento viejo, que ya no esta en la pagina. */
        var modalReserva = function () {
            return $('#modalReserva');
        };

        if (modalReserva().length) {

            var reservaActual = { id: 0 };

            $(document).on('click', '.js-reservar-mesa', function () {

                reservaActual.id = $(this).data('id');

                $('#reservaMesaTitulo').text($(this).data('mesa'));
                $('#reservaNombre').val('');
                $('#reservaHora').val(
                    new Date().toTimeString().substring(0, 5)
                );
                $('#reservaAviso').hide();

        modalReserva().modal('show');

                setTimeout(function () {
                    $('#reservaNombre').trigger('focus');
                }, 300);
            });

            $(document).on('click', '#reservaGuardar', function () {

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
        modalReserva().modal('hide');
                    window.location.reload();
                })
                .fail(function (xhr) {
                    $('#reservaAvisoTexto').text(mensajeDe(xhr, 'No se pudo reservar la mesa.'));
                    $('#reservaAviso').show();
                    $boton.prop('disabled', false).html(textoOriginal);
                });
            });
        }

        $(document).on('click', '.js-liberar-mesa', function () {

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

        $(document).on('click', '.js-eliminar-mesa', function () {

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

            $(document).on('click', '.js-agregar-mesa', function () {
                $('#mesaAviso').hide();
                $('#mesaCapacidad').val(4);
                $mesaModal.modal('show');
            });

            $(document).on('click', '#mesaGuardar', function () {

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
        /* Se busca cada vez que se usa. El motor de tiempo real repone los
           modales cada 5 segundos y una referencia guardada apuntaria al
           elemento viejo, que ya no esta en la pagina. */
        var modalPedido = function () {
            return $('#modalPedido');
        };

        if (!modalPedido().length) {
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
        modalPedido().modal('show');
        };

        // El clic en la tarjeta de mesa (que se engancha antes del return
        // temprano) llega aquí por nombre global.
        window.abrirModalPedido = abrirModal;

        /**
         * Muestra los pedidos que ya tiene la mesa.
         *
         * El servidor ya devuelve solo los de hoy: los de días anteriores no
         * se listan, porque llenaban el modal y tapaban el catálogo.
         */
        var cargarPedidosDeLaMesa = function () {

            var $wrap = $('#pedidoExistentesWrap');
            var $lista = $('#pedidoExistentes');

            if (!pedido.mesaId) {
                $wrap.hide();
                return;
            }

            $wrap.show();
            $wrap.removeClass('is-abierto');
            $('#pedidoExistentesToggle').attr('aria-expanded', 'false');
            $('#pedidoExistentesResumen').text('cargando...');
            $lista.html('<p class="lg-muted mb-0"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');

            $.get(APP_URL + '?page=api/pedido/listar&mesa_id=' + pedido.mesaId)
            .done(function (r) {

                if (!r.pedidos || !r.pedidos.length) {
                    $('#pedidoExistentesResumen').text('');
                    $lista.html('<p class="lg-muted mb-0">Esta mesa a&uacute;n no tiene pedidos hoy.</p>');
                    return;
                }

                // Resumen en una línea: el plegable arranca cerrado para que
                // el catálogo quede siempre visible sin deslizar
                var suma = 0;

                r.pedidos.forEach(function (p) {
                    suma += p.total;
                });

                $('#pedidoExistentesResumen').text(
                    '· ' + r.pedidos.length + ' pedido(s) · ' + soles(suma)
                );

                var html = '';

                r.pedidos.forEach(function (p) {
                    html +=
                        '<div class="lg-mesa lg-mesa--' + p.estado_clase + '" style="cursor:default;">' +
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
                $('#pedidoExistentesResumen').text('');
                $lista.html('<p class="lg-muted mb-0">No se pudieron cargar los pedidos de la mesa.</p>');
            });
        };

        // Plegable de pedidos ya registrados
        $(document).on('click', '#pedidoExistentesToggle', function () {

            var $wrap = $('#pedidoExistentesWrap');
            var abierto = $wrap.toggleClass('is-abierto').hasClass('is-abierto');

            $(this).attr('aria-expanded', abierto ? 'true' : 'false');
        });

        // El clic en las tarjetas de mesa se engancha arriba del todo, antes
        // del return temprano, para que también funcione sin este modal.

        // Abre el modal desde los botones de mesa
        $('[data-abrir-pedido]').on('click', function () {

            var $boton = $(this);

            abrirModal({
                mesaId: $boton.data('mesa-id'),
                mesa: $boton.data('mesa'),
                capacidad: $boton.data('capacidad')
            });
        });

        /* Agrega un producto al pedido.

           Se escucha sobre document y no sobre cada botón, porque el catálogo
           se repinta: al abrir el modal, al cambiar lo que haya en la cocina y
           cada vez que el motor de tiempo real repone la pantalla. Un manejador
           enganchado a los botones se queda en los viejos y los nuevos no
           hacen nada al tocarlos. Con document siempre están todos. */
        $(document).on('click', '.modal-producto', function () {

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

        // Delegado, por lo mismo que el catalogo: estos campos se pierden
        // cuando el motor de tiempo real repone la pantalla.
        $(document).on('input change', '#modalBuscar, #modalCategoria', filtrarCatalogo);

        // Descarta el pedido en curso
        $(document).on('click', '#pedidoLimpiar', function () {
            pedido.items = [];
            pintarPedido();
        });

        // Envía el pedido a la cocina
        $(document).on('click', '#pedidoEnviarCocina', function () {

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

        /* Se engancha cada vez que se abre el modal, y no una sola vez al
           cargar la pagina: al reponerlo el motor de tiempo real, el
           manejador que se habia quedado enganchado al elemento viejo deja
           de escuchar, y el pedido en curso se limpiaba solo una vez. */
        modalPedido().one('shown.bs.modal', function () {

            modalPedido().on('hidden.bs.modal', function () {
                pedido.items = [];
                pintarPedido();
            });
        });

    });

})(jQuery);
