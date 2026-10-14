<?php

/**
 * Permisos de la pollería.
 *
 * En la base de datos los permisos se guardan como claves cortas
 * ("pedidos_estado"). Aquí se traducen a algo que un dueño pueda entender y
 * se agrupan por módulo, para que al crear un rol se marquen las casillas
 * entendiendo qué se está concediendo y no por el nombre de la clave.
 */

/**
 * Módulos del sistema a los que tiene acceso un rol.
 *
 * Sirve para la tabla de la pantalla: en vez de mostrar 18 casillas, dice
 * qué menús puede ver esa persona.
 *
 * @param array $idsPermisos ids de permiso del rol
 * @param array $grupos      resultado de permisosAgrupados()
 *
 * @return array [['titulo' => ..., 'icono' => ...], ...]
 */
function modulosDelRol(array $idsPermisos, array $grupos): array
{
    $modulos = [];

    foreach ($grupos as $grupo) {

        foreach ($grupo['permisos'] as $permiso) {

            if (in_array((int) $permiso['id'], $idsPermisos, true)) {

                $modulos[] = ['titulo' => $grupo['titulo'], 'icono' => $grupo['icono']];

                break;
            }
        }
    }

    return $modulos;
}

/**
 * @return array grupos en el orden en que se muestran
 */
function gruposDePermisos(): array
{
    return [
        'Resumen' => [
            'icono'  => 'fa fa-home',
            'titulo' => 'Resumen y ventas',
            'nota'   => 'Lo que ve el rol del negocio.',
            'items'  => [
                'dashboard' => [
                    'nombre' => 'Ver el tablero (Dashboard)',
                    'ayuda'  => 'Resumen de ventas, gastos y pedidos del día.',
                ],
                'ventas' => [
                    'nombre' => 'Ver Ventas y la caja',
                    'ayuda'  => 'Historial de ventas y movimientos de dinero.',
                ],
            ],
        ],

        'Mesas y Pedidos' => [
            'icono'  => 'fa fa-table',
            'titulo' => 'Mesas y Pedidos',
            'nota'   => 'El día a día en el salón y en la cocina.',
            'items'  => [
                'mesas' => [
                    'nombre' => 'Ver las mesas',
                    'ayuda'  => 'Entrar al salón y ver qué mesa está libre u ocupada.',
                ],
                'mesas_reservar' => [
                    'nombre' => 'Reservar mesas',
                    'ayuda'  => 'Marcar una mesa como reservada a nombre de alguien. Solo reservar, no crearla.',
                ],
                'mesas_editar' => [
                    'nombre' => 'Agregar y eliminar mesas',
                    'ayuda'  => 'Crear mesas nuevas o quitar las que ya no se usan.',
                ],
                'pedidos_ver' => [
                    'nombre' => 'Ver los pedidos',
                    'ayuda'  => 'Solo consultar la lista de pedidos, sin poder tocarla.',
                ],
                'pedidos_crear' => [
                    'nombre' => 'Registrar pedidos',
                    'ayuda'  => 'Crear pedidos en una mesa y enviarlos a cocina.',
                ],
                'pedidos_editar' => [
                    'nombre' => 'Modificar o anular pedidos',
                    'ayuda'  => 'Corregir productos o cancelar un pedido. Devuelve el stock.',
                ],
                'pedidos_estado' => [
                    'nombre' => 'Cambiar el estado de un pedido',
                    'ayuda'  => 'Aceptar, marcar como listo o servir. Es lo que usa la cocina.',
                ],
                'cobrar' => [
                    'nombre' => 'Cobrar los pedidos',
                    'ayuda'  => 'Registrar el pago de una mesa. Al cobrar, la mesa se libera.',
                ],
            ],
        ],

        'Menú del día' => [
            'icono'  => 'fa fa-book',
            'titulo' => 'Menú del día',
            'nota'   => 'La carta de hoy y qué queda de cada plato.',
            'items'  => [
                'menu_dia' => [
                    'nombre' => 'Ver el menú del día',
                    'ayuda'  => 'Los platos y bebidas de la carta con su stock.',
                ],
                'menu_dia_editar' => [
                    'nombre' => 'Marcar platos como agotados',
                    'ayuda'  => 'Poner un plato en "agotado" cuando ya se terminó.',
                ],
            ],
        ],

        'Inventario' => [
            'icono'  => 'fa fa-cube',
            'titulo' => 'Inventario',
            'nota'   => 'Productos, existencias y ajustes de stock.',
            'items'  => [
                'inventario' => [
                    'nombre' => 'Ver productos y stock',
                    'ayuda'  => 'Consultar qué hay y cuánto queda.',
                ],
                'inventario_editar' => [
                    'nombre' => 'Crear productos y ajustar stock',
                    'ayuda'  => 'Agregar productos y hacer ingresos o salidas de stock.',
                ],
            ],
        ],

        'Finanzas' => [
            'icono'  => 'fa fa-folder-open',
            'titulo' => 'Finanzas',
            'nota'   => 'Dinero que entra y que sale.',
            'items'  => [
                'finanzas' => [
                    'nombre' => 'Ver ingresos, egresos y gastos',
                    'ayuda'  => 'Consultar el detalle del dinero.',
                ],
                'servicios' => [
                    'nombre' => 'Ver servicios (luz, agua, internet)',
                    'ayuda'  => 'Pagos de servicios básicos y sus vencimientos.',
                ],
                'reportes' => [
                    'nombre' => 'Ver y exportar reportes',
                    'ayuda'  => 'Reportes por día, semana o mes, y descarga en Excel.',
                ],
            ],
        ],

        'Administración' => [
            'icono'  => 'fa fa-cogs',
            'titulo' => 'Administración del sistema',
            'nota'   => ' Solo para el dueño o encargado.',
            'items'  => [
                'personal' => [
                    'nombre' => 'Ver Personal',
                    'ayuda'  => 'Trabajadores, asistencia y pagos.',
                ],
                'configuracion' => [
                    'nombre' => 'Administrar Configuración',
                    'ayuda'  => 'Crear usuarios, editar roles y los datos del negocio.',
                ],
            ],
        ],
    ];
}

/**
 * Permiso de la base de datos traducido a nombre y ayuda legible.
 *
 * @param array $fila fila de la tabla permisos (id, nombre, descripcion)
 *
 * @return array ['clave' => ..., 'nombre' => ..., 'ayuda' => ...]
 */
function permisoExplicado(array $fila): array
{
    $clave = (string) $fila['nombre'];

    foreach (gruposDePermisos() as $grupo) {

        if (isset($grupo['items'][$clave])) {

            return [
                'clave'  => $clave,
                'nombre' => $grupo['items'][$clave]['nombre'],
                'ayuda'  => $grupo['items'][$clave]['ayuda'],
            ];
        }
    }

    // Permiso sin explicar en el archivo: al menos se muestra la descripción
    // que dejó quien lo creó, y si tampoco hay, la clave legible.
    return [
        'clave'  => $clave,
        'nombre' => ucfirst(str_replace('_', ' ', $clave)),
        'ayuda'  => (string) ($fila['descripcion'] ?? ''),
    ];
}

/**
 * Ordena los permisos de la base dentro de los grupos definidos arriba.
 *
 * Los que no están en ningún grupo se devuelven al final, bajo "Otros", para
 * que un permiso agregado desde la base no desaparezca de la pantalla.
 *
 * @param array $permisos filas de la tabla permisos
 *
 * @return array [['titulo' => ..., 'nota' => ..., 'icono' => ..., 'permisos' => [...]], ...]
 */
function permisosAgrupados(array $permisos): array
{
    $porClave = [];

    foreach ($permisos as $fila) {
        $porClave[(string) $fila['nombre']] = $fila;
    }

    $grupos = [];

    foreach (gruposDePermisos() as $grupo) {

        $items = [];

        foreach (array_keys($grupo['items']) as $clave) {

            if (!isset($porClave[$clave])) {
                continue;
            }

            $items[] = permisoExplicado($porClave[$clave]) + ['id' => (int) $porClave[$clave]['id']];

            unset($porClave[$clave]);
        }

        if ($items) {
            $grupos[] = [
                'titulo'   => $grupo['titulo'],
                'nota'     => trim($grupo['nota'] ?? ''),
                'icono'    => $grupo['icono'] ?? 'fa fa-circle',
                'permisos' => $items,
            ];
        }
    }

    // Lo que no se pudo ubicar
    if ($porClave) {

        $otros = [];

        foreach ($porClave as $fila) {
            $otros[] = permisoExplicado($fila) + ['id' => (int) $fila['id']];
        }

        $grupos[] = [
            'titulo'   => 'Otros',
            'nota'     => ' Permisos sin agrupar.',
            'icono'    => 'fa fa-question-circle',
            'permisos' => $otros,
        ];
    }

    return $grupos;
}
