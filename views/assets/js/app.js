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

                    $grupo.append($label).append($controlo);

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

            $(document).on('click', '.js-nuevo', function () {
                abrirFormulario($(this).data('recurso'), $(this).data('campos'), null);
            });

            $(document).on('click', '.js-editar', function () {
                var $boton = $(this);
                abrirFormulario($boton.data('recurso'), $boton.data('campos'), $boton.data('fila'));
            });

            $('#formGuardar').on('click', function () {

                var datos = { recurso: formulario.recurso };

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

            $(document).on('click', '.js-borrar', function () {

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
             */
            var refrescarContenido = function (alTerminar) {

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
                        refrescarContenido(alTerminar);
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
             * @param string|null nuevaFirma  la firma del servidor, que se
             *        guarda aqui y no antes: si se guardara antes de refrescar
             *        de verdad, un cambio detectado con un modal abierto se
             *        daria por visto sin llegar a verse nunca.
             */
            var actualizar = function (nuevaFirma) {

                quitarBarra();
                pendiente = false;

                if (nuevaFirma) {
                    firma = nuevaFirma;
                }

                refrescarContenido();
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
        var $modalMesa = $('#modalMesaPedidos');

        if ($modalMesa.length) {

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

                $modalMesa.data('mesa-id', mesaId);
                $('#mesaPedidosTitulo').text(mesa || 'Mesa');
                $('#mesaPedidosResumen').text('Cargando...');

                $modalMesa.modal('show');

                window.pintarPedidosMesa(mesaId);
            };
        }

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

            $(document).on('click', '.js-abrir-cobro', function () {

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

            $(document).on('click', '.js-ajustar-stock', function () {

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
                    cantidad: parseInt($('#stockCantidad').val(), 10) || 0,
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

                // La cantidad son unidades enteras; el precio sí lleva céntimos
                var $cantidad = $('<input type="number" class="lg-input" step="1" min="1" style="min-height:38px;">').val(item.cantidad);
                $cantidad.on('input', function () {
                    compra.items[indice].cantidad = parseInt($(this).val(), 10) || 0;
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
                        return suma + parseInt(i.cantidad, 10);
                    }, 0)
                    + ' unidad(es)</th>'
                    + '<th style="text-align:right;">' + soles(c.total) + '</th></tr></tfoot>'
                    + '</table></div>';

                $('#compraDetalleCuerpo').html(cuerpo);

                $('#compraAnular').toggle(!datos.anulada);
            };

            $(document).on('click', '.js-ver-compra', function () {

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

            $(document).on('click', '.js-anular-compra', function () {
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

            $(document).on('click', '.js-reservar-mesa', function () {

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
        $('#pedidoExistentesToggle').on('click', function () {

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
