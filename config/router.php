<?php

/**
 * Router: define las páginas disponibles de la aplicación.
 *
 * Cada ruta puede tener:
 *   - vista   : archivo dentro de /views (sin extensión) que se incluirá
 *   - publica : si es true no requiere sesión
 *   - titulo  : título del documento
 *   - accion  : acción especial (API, logout, exportar)
 *   - permiso : permiso necesario para entrar
 */

function rutas(): array
{
    return [
        'login' => [
            'vista'   => 'auth/login',
            'publica' => true
        ],

        'logout' => [
            'accion' => 'logout'
        ],

        'dashboard' => [
            'vista'    => 'admin/dashboard',
            'titulo'   => 'Dashboard',
            'permiso'  => 'dashboard'
        ],

        'ventas' => [
            'vista'   => 'ventas/index',
            'titulo'  => 'Ventas',
            'permiso' => 'ventas'
        ],

        'mesas' => [
            'vista'   => 'mesas/index',
            'titulo'  => 'Mesas',
            'permiso' => 'mesas'
        ],

        'pedidos' => [
            'vista'   => 'mesas/pedidos',
            'titulo'  => 'Pedidos',
            'permiso' => 'pedidos_ver'
        ],

        'inventario' => [
            'vista'   => 'inventario/index',
            'titulo'  => 'Inventario',
            'permiso' => 'inventario'
        ],

        // El historial de movimientos es para quien ajusta el stock, no solo
        // para quien lo consulta.
        'inventario/movimientos' => [
            'vista'   => 'inventario/movimientos',
            'titulo'  => 'Movimientos de inventario',
            'permiso' => 'inventario_editar'
        ],

            'menu_del_dia' => [
                'vista'   => 'menu_del_dia/index',
                'titulo'  => 'Menú del día',
                'permiso' => 'menu_dia'
            ],

        'finanzas' => [
            'vista'   => 'finanzas/index',
            'titulo'  => 'Finanzas',
            'permiso' => 'finanzas'
        ],

        'personal' => [
            'vista'   => 'personal/index',
            'titulo'  => 'Personal',
            'permiso' => 'personal'
        ],

        'servicios' => [
            'vista'   => 'servicios/index',
            'titulo'  => 'Servicios',
            'permiso' => 'servicios'
        ],

        'reportes' => [
            'vista'   => 'reportes/index',
            'titulo'  => 'Reportes',
            'permiso' => 'reportes'
        ],

        'configuracion' => [
            'vista'   => 'configuracion/index',
            'titulo'  => 'Configuración',
            'permiso' => 'configuracion'
        ],

        // -----------------------------------------------
        // Acciones AJAX
        // -----------------------------------------------
        'api/pedido/crear' => [
            'accion'  => 'pedido_crear',
            'api'     => true,
            'permiso' => 'pedidos_crear'
        ],
        'api/pedido/estado' => [
            'accion'  => 'pedido_estado',
            'api'     => true,
            'permiso' => 'pedidos_estado'
        ],
        'api/pedido/listar' => [
            'accion'  => 'pedido_listar',
            'api'     => true,
            'soloGet' => true,
            'permiso' => 'pedidos_ver'
        ],
        'api/pedido/resumen' => [
            'accion'  => 'pedido_resumen',
            'api'     => true,
            'soloGet' => true,
            'permiso' => 'pedidos_ver'
        ],

        /**
         * Latido del sistema.
         *
         * Lo consulta TODAS las pantallas para saber si algo cambió en la
         * base y refrescarse solas, sin que nadie tenga que actualizar.
         */
        'api/pulso' => [
            'accion'  => 'pulso',
            'api'     => true,
            'soloGet' => true
        ],
        'api/pedido/actualizar' => [
            'accion'  => 'pedido_actualizar',
            'api'     => true,
            'permiso' => 'pedidos_editar'
        ],
        'api/pedido/eliminar' => [
            'accion'  => 'pedido_eliminar',
            'api'     => true,
            'permiso' => 'pedidos_editar'
        ],
        'api/pedido/cobrar' => [
            'accion'  => 'pedido_cobrar',
            'api'     => true,
            'permiso' => 'cobrar'
        ],

        'api/mesa/reservar' => [
            'accion'  => 'mesa_reservar',
            'api'     => true,
            'permiso' => 'mesas_reservar'
        ],
        'api/mesa/liberar' => [
            'accion'  => 'mesa_liberar',
            'api'     => true,
            'permiso' => 'mesas_reservar'
        ],
        // Agregar y eliminar mesas es solo del administrador. Antes estas dos
        // rutas pedían 'mesas', que el mesero tiene, así que podía llamarlas
        // directo aunque el botón no le saliera.
        'api/mesa/crear' => [
            'accion'  => 'mesa_crear',
            'api'     => true,
            'permiso' => 'mesas_editar'
        ],
        'api/mesa/eliminar' => [
            'accion'  => 'mesa_eliminar',
            'api'     => true,
            'permiso' => 'mesas_editar'
        ],

        'api/gestion/listar' => [
            'accion'  => 'gestion_listar',
            'api'     => true,
            'soloGet' => true
        ],
        'api/gestion/opciones' => [
            'accion'  => 'gestion_opciones',
            'api'     => true,
            'soloGet' => true
        ],
        // Estas cuatro no declaran permiso: cada recurso lleva el suyo
        // y lo revisa GestionController antes de tocar la base.
        'api/gestion/crear' => [
            'accion'  => 'gestion_crear',
            'api'     => true
        ],
        'api/gestion/actualizar' => [
            'accion'  => 'gestion_actualizar',
            'api'     => true
        ],
        'api/gestion/eliminar' => [
            'accion'  => 'gestion_eliminar',
            'api'     => true
        ],

        'api/inventario/ajustar' => [
            'accion'  => 'inventario_ajustar',
            'api'     => true,
            'permiso' => 'inventario_editar'
        ],

        'api/menu/agotado' => [
            'accion'  => 'menu_agotado',
            'api'     => true,
            'permiso' => 'menu_dia_editar'
        ],

        'api/menu/catalogo' => [
            'accion'  => 'menu_catalogo',
            'api'     => true,
            'soloGet' => true,
            'permiso' => 'pedidos_crear'
        ],

        'api/personal/asistencia' => [
            'accion'  => 'personal_asistencia',
            'api'     => true,
            'permiso' => 'personal'
        ],
        'api/personal/pago' => [
            'accion'  => 'personal_pago',
            'api'     => true,
            'permiso' => 'personal'
        ],

        'reportes/exportar' => [
            'accion'  => 'reporte_exportar',
            'permiso' => 'reportes'
        ]
    ];
}

/**
 * Resuelve la página solicitada.
 * Devuelve null si la ruta no existe.
 */
function resolverRuta(?string $pagina): ?array
{
    $rutas = rutas();

    $pagina = $pagina === null || $pagina === '' ? 'dashboard' : $pagina;

    return $rutas[$pagina] ?? null;
}

/**
 * Ejecuta la acción de logout y regresa al login.
 */
function accionLogout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    redirigir('?page=login');
}
