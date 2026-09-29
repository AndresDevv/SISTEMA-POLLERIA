<?php

/**
 * Menú lateral del sistema.
 *
 * "roles" => null  : visible para cualquier usuario autenticado
 * "roles" => array : visible solo para esos roles
 *                   (si no, 'esPermitido()' decide con permisosPorDefecto())
 */

function menuSistema(): array
{
    return [
        [
            'titulo' => 'Dashboard',
            'icono'  => 'fa fa-home',
            'pagina' => 'dashboard',
            'roles'  => null
        ],
        [
            'titulo' => 'Ventas',
            'icono'  => 'fa fa-bar-chart',
            'pagina' => 'ventas',
            'roles'  => null
        ],
        [
            'titulo' => 'Mesas y Pedidos',
            'icono'  => 'fa fa-table',
            'pagina' => 'mesas',
            'roles'  => null
        ],
        [
            'titulo' => 'Pedidos',
            'icono'  => 'fa fa-list-alt',
            'pagina' => 'pedidos',
            'roles'  => null,
            'oculto' => true
        ],
        [
            'titulo' => 'Inventario',
            'icono'  => 'fa fa-cube',
            'pagina' => 'inventario',
            'roles'  => null
        ],
        [
            'titulo' => 'Compras',
            'icono'  => 'fa fa-shopping-cart',
            'pagina' => 'compras',
            'roles'  => null
        ],
        [
            'titulo' => 'Finanzas',
            'icono'  => 'fa fa-folder-open',
            'pagina' => 'finanzas',
            'roles'  => ['administrador']
        ],
        [
            'titulo' => 'Personal',
            'icono'  => 'fa fa-users',
            'pagina' => 'personal',
            'roles'  => ['administrador']
        ],
        [
            'titulo' => 'Servicios',
            'icono'  => 'fa fa-cutlery',
            'pagina' => 'servicios',
            'roles'  => null
        ],
        [
            'titulo' => 'Reportes',
            'icono'  => 'fa fa-file-text',
            'pagina' => 'reportes',
            'roles'  => ['administrador', 'supervisor']
        ],
        [
            'titulo' => 'Configuración',
            'icono'  => 'fa fa-cog',
            'pagina' => 'configuracion',
            'roles'  => ['administrador']
        ]
    ];
}

/**
 * Permisos por rol.
 *
 * Los nombres siguen la tabla `roles` de la base de datos:
 *   1 = Administrador, 2 = Mesero, 3 = Cocina
 */
function permisosPorRol(): array
{
    return [
        'administrador' => [
            'dashboard', 'ventas', 'mesas', 'pedidos', 'inventario', 'compras',
            'finanzas', 'personal', 'servicios', 'reportes', 'configuracion'
        ],
        'supervisor' => [
            'dashboard', 'ventas', 'mesas', 'pedidos', 'inventario',
            'compras', 'finanzas', 'servicios', 'reportes'
        ],
        'cajero' => [
            'dashboard', 'ventas', 'mesas', 'pedidos', 'inventario', 'compras'
        ],
        'mesero' => [
            'dashboard', 'ventas', 'mesas', 'pedidos', 'servicios'
        ],
        'cocina' => [
            'dashboard', 'mesas', 'pedidos', 'inventario', 'servicios'
        ]
    ];
}

/**
 * Rol actual en minúsculas.
 */
function rolActual(): string
{
    return strtolower(trim((string) ($_SESSION['rol'] ?? '')));
}

/**
 * Indica si el usuario en sesión puede ver una página.
 */
function esPermitido(string $pagina): bool
{
    if (esAdministrador()) {
        return true;
    }

    $permisos = permisosPorRol();
    $rol = rolActual();

    // Rol desconocido: solo el mínimo operativo
    if (!isset($permisos[$rol])) {
        return in_array($pagina, ['dashboard'], true);
    }

    return in_array($pagina, $permisos[$rol], true);
}

/**
 * Indica si un item del menú es visible para el usuario en sesión.
 */
function menuVisible(array $item): bool
{
    if (esAdministrador()) {
        return true;
    }

    if (empty($item['roles'])) {
        return esPermitido($item['pagina']);
    }

    $rolActual = strtolower((string) ($_SESSION['rol'] ?? ''));

    return in_array($rolActual, array_map('strtolower', $item['roles']), true);
}

/**
 * Construye la URL de una página interna.
 */
function url(string $pagina = ''): string
{
    return $pagina === '' ? BASE_URL : BASE_URL . '?page=' . $pagina;
}

/**
 * Marca el item de menú como activo según la página actual.
 */
function menuActivo(string $pagina): string
{
    $actual = (string) ($_GET['page'] ?? 'dashboard');

    return $actual === $pagina ? ' active' : '';
}

/**
 * Imprime el encabezado de una página: icono, título y subtítulo.
 */
function encabezadoPagina(string $icono, string $titulo, string $subtitulo = '', string $acciones = ''): void
{
    ?>
    <div class="lg-page-head">
        <div class="lg-page-head-left">
            <span class="lg-page-icon"><i class="<?= htmlspecialchars($icono) ?>"></i></span>
            <div>
                <h1><?= htmlspecialchars($titulo) ?></h1>
                <?php if ($subtitulo !== ''): ?>
                    <p class="lg-subtitle"><?= htmlspecialchars($subtitulo) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($acciones !== ''): ?>
            <div class="lg-page-head-actions"><?= $acciones ?></div>
        <?php endif; ?>
    </div>
    <?php
}
