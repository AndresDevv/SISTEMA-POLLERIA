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
