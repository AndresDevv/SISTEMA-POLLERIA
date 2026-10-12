<?php

/**
 * Permisos de la pollería.
 *
 * En la base de datos los permisos se guardan como claves cortas
 * ("pedidos_estado"). Aquí se traducen a algo que un dueño pueda entender,
 * y además se definen los PERFILES de cada tipo de persona: mesero, cocina,
 * cajero, limpieza, administrador.
 *
 * El sistema ya sabe qué puede ver cada perfil. Al crear un rol se le copian
 * los permisos de su perfil, así que en la pantalla de Roles no hay que
 * marcar casillas una por una.
 */

/**
 * Perfiles de la pollería: qué puede ver y hacer cada tipo de persona.
 *
 * 'todos' => true significa que el rol ve absolutamente todo, sin importar
 * lo que diga la tabla rol_permiso. Es lo que pasa con el administrador.
 *
 * @return array perfiles en el orden en que se ofrecen
 */
function perfilesPredefinidos(): array
{
    return [
        'mesero' => [
            'nombre'      => 'Mesero',
            'descripcion' => 'Atiende el salón: ve las mesas, registra pedidos, envía a cocina y cobra.',
            'icono'       => 'fa fa-user',
            'todos'       => false,
            'permisos'    => ['mesas', 'mesas_reservar', 'pedidos_ver', 'pedidos_crear', 'inventario', 'cobrar'],
        ],

        'cocina' => [
            'nombre'      => 'Cocina',
            'descripcion' => 'Prepara lo que llega de las mesas y ve el stock de los productos.',
            'icono'       => 'fa fa-fire',
            'todos'       => false,
            'permisos'    => ['mesas', 'pedidos_ver', 'inventario', 'pedidos_estado'],
        ],

        'cajero' => [
            'nombre'      => 'Cajero',
            'descripcion' => 'Cobra los pedidos y lleva el control de la caja y los reportes del día.',
            'icono'       => 'fa fa-money',
            'todos'       => false,
            'permisos'    => ['dashboard', 'ventas', 'mesas', 'pedidos_ver', 'cobrar', 'finanzas', 'reportes'],
        ],

        'limpieza' => [
            'nombre'      => 'Limpieza',
            'descripcion' => 'Registra las compras de insumos y lleva el control de las mesas.',
            'icono'       => 'fa fa-shield',
            'todos'       => false,
            'permisos'    => ['mesas', 'inventario', 'inventario_editar', 'compras'],
        ],

        'administrador' => [
            'nombre'      => 'Administrador',
            'descripcion' => 'Acceso completo: ve todos los menús y puede configurarlo todo.',
            'icono'       => 'fa fa-star',
            'todos'       => true,
            'permisos'    => [],
        ],
    ];
}

/**
 * Perfil que corresponde a un nombre de rol, o null si es uno a medida.
 *
 * Compara sin tildes y sin mayúsculas, así que "Cocina", "cocina" y
 * "COCINERO" caen al mismo perfil.
 */
function perfilDeRol(string $nombreRol): ?array
{
    $normal = function (string $texto): string {
        $texto = mb_strtolower(trim($texto));

        return (string) preg_replace(
            ['/á|ä|â/', '/é|ë|ê/', '/í|ï|î/', '/ó|ö|ô/', '/ú|ü|û/', '/ñ/'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $texto
        );
    };

    $buscado = $normal($nombreRol);

    foreach (perfilesPredefinidos() as $perfil) {

        if ($normal($perfil['nombre']) === $buscado) {
            return $perfil;
        }
    }

    return null;
}

/**
 * Permisos (ids) que le tocan a un perfil.
 *
 * @param array $perfil      una entrada de perfilesPredefinidos()
 * @param array $permisos    filas de la tabla permisos (id, nombre)
 * @param bool  $esAdmin     si el rol es administrador, devuelve todo
 *
 * @return array ids de permiso
 */
function permisosDelPerfil(array $perfil, array $permisos, bool $esAdmin = false): array
{
    if (!empty($perfil['todos']) || $esAdmin) {
        return array_map(static fn (array $p): int => (int) $p['id'], $permisos);
    }

    $ids = [];

    foreach ($permisos as $permiso) {

        if (in_array((string) $permiso['nombre'], $perfil['permisos'], true)) {
            $ids[] = (int) $permiso['id'];
        }
    }

    return $ids;
}

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

        'Inventario' => [
            'icono'  => 'fa fa-cube',
            'titulo' => 'Inventario y Compras',
            'nota'   => ' Productos, stock y proveedores.',
            'items'  => [
                'inventario' => [
                    'nombre' => 'Ver productos y stock',
                    'ayuda'  => 'Consultar qué hay y cuánto queda.',
                ],
                'inventario_editar' => [
                    'nombre' => 'Crear productos y ajustar stock',
                    'ayuda'  => 'Agregar productos y hacer ingresos o salidas de stock.',
                ],
                'compras' => [
                    'nombre' => 'Registrar compras',
                    'ayuda'  => 'Anotar compras a proveedores. Cuenta como gasto del día.',
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
