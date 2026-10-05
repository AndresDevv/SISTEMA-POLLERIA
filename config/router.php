<?php

/**
 * Router: define las páginas disponibles de la aplicación.
 *
 * Cada ruta puede tener:
 *   - vista     : archivo dentro de /views (sin extensión) que se incluirá
 *   - publica   : si es true no requiere sesión
 *   - titulo    : título del documento
 *   - permiso   : si es true exige un rol con acceso a la página
 */

function rutas(): array
{
    return [
        'login' => [
            'vista'   => 'auth/login',
            'publica' => true
        ],

        'logout' => [
            'accion'  => 'logout'
        ],

        'dashboard' => [
            'vista'   => 'admin/dashboard',
            'titulo'  => 'Dashboard'
        ],

        'ventas' => [
            'vista'   => 'ventas/index',
            'titulo'  => 'Ventas'
        ],

        'mesas' => [
            'vista'   => 'mesas/index',
            'titulo'  => 'Mesas'
        ],

        'pedidos' => [
            'vista'   => 'mesas/pedidos',
            'titulo'  => 'Pedidos'
        ],

        'inventario' => [
            'vista'   => 'inventario/index',
            'titulo'  => 'Inventario'
        ],

        'compras' => [
            'vista'   => 'compras/index',
            'titulo'  => 'Compras'
        ],

        'finanzas' => [
            'vista'   => 'finanzas/index',
            'titulo'  => 'Finanzas',
            'permiso' => true
        ],

        'personal' => [
            'vista'   => 'personal/index',
            'titulo'  => 'Personal',
            'permiso' => true
        ],

        'servicios' => [
            'vista'   => 'servicios/index',
            'titulo'  => 'Servicios'
        ],

        'reportes' => [
            'vista'   => 'reportes/index',
            'titulo'  => 'Reportes',
            'permiso' => true
        ],

        'categorias' => [
            'vista'   => 'inventario/categorias',
            'titulo'  => 'Categorías'
        ],

        'reportes/exportar' => [
            'accion' => 'reporte_exportar',
            'permiso' => true
        ],

        'configuracion' => [
            'vista'   => 'configuracion/index',
            'titulo'  => 'Configuración',
            'permiso' => true
        ],

        // Acciones AJAX (no renderizan vistas)
        'api/pedido/crear' => [
            'accion' => 'pedido_crear',
            'api'    => true
        ],
        'api/pedido/estado' => [
            'accion' => 'pedido_estado',
            'api'    => true
        ],
        // API de mesas: reservar, agregar y eliminar
        'api/mesa/reservar' => [
            'accion' => 'mesa_reservar',
            'api'    => true
        ],
        'api/mesa/liberar' => [
            'accion' => 'mesa_liberar',
            'api'    => true
        ],
        'api/mesa/crear' => [
            'accion' => 'mesa_crear',
            'api'    => true
        ],
        'api/mesa/eliminar' => [
            'accion' => 'mesa_eliminar',
            'api'    => true
        ],

        'api/pedido/listar' => [
            'accion' => 'pedido_listar',
            'api'    => true,
            'soloGet' => true
        ],
        'api/pedido/actualizar' => [
            'accion' => 'pedido_actualizar',
            'api'    => true
        ],
        'api/pedido/eliminar' => [
            'accion' => 'pedido_eliminar',
            'api'    => true
        ],
        'api/pedido/cobrar' => [
            'accion' => 'pedido_cobrar',
            'api'    => true
        ],

        // CRUD genérico de los módulos administrativos
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
        'api/gestion/crear' => [
            'accion' => 'gestion_crear',
            'api'    => true
        ],
        'api/gestion/actualizar' => [
            'accion' => 'gestion_actualizar',
            'api'    => true
        ],
        'api/gestion/eliminar' => [
            'accion' => 'gestion_eliminar',
            'api'    => true
        ],
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
