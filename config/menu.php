<?php

/**
 * Menú lateral del sistema.
 *
 * La visibilidad la decide \Permiso a partir de las tablas
 * permisos / rol_permiso, no una lista fija de roles.
 *
 * "oculto": la página existe y se puede llegar por URL, pero no aparece en el menú
 *           (por ejemplo, "Pedidos", que cuelga de Mesas y Pedidos).
 */

function menuSistema(): array
{
    return [
        [
            'titulo' => 'Dashboard',
            'icono'  => 'fa fa-home',
            'pagina' => 'dashboard'
        ],
        [
            'titulo' => 'Ventas',
            'icono'  => 'fa fa-bar-chart',
            'pagina' => 'ventas'
        ],
        [
            'titulo' => 'Mesas y Pedidos',
            'icono'  => 'fa fa-table',
            'pagina' => 'mesas'
        ],
        [
            'titulo' => 'Pedidos',
            'icono'  => 'fa fa-list-alt',
            'pagina' => 'pedidos',
            'oculto' => true
        ],
        [
            'titulo' => 'Inventario',
            'icono'  => 'fa fa-cube',
            'pagina' => 'inventario'
        ],
        [
            'titulo' => 'Compras',
            'icono'  => 'fa fa-shopping-cart',
            'pagina' => 'compras'
        ],
        [
            'titulo' => 'Finanzas',
            'icono'  => 'fa fa-folder-open',
            'pagina' => 'finanzas'
        ],
        [
            'titulo' => 'Personal',
            'icono'  => 'fa fa-users',
            'pagina' => 'personal'
        ],
        [
            'titulo' => 'Servicios',
            'icono'  => 'fa fa-bolt',
            'pagina' => 'servicios'
        ],
        [
            'titulo' => 'Reportes',
            'icono'  => 'fa fa-file-text',
            'pagina' => 'reportes'
        ],
        [
            'titulo' => 'Configuración',
            'icono'  => 'fa fa-cog',
            'pagina' => 'configuracion'
        ]
    ];
}

/**
 * Rol actual tal como viene de la base.
 */
function rolActual(): string
{
    return trim((string) ($_SESSION['rol'] ?? ''));
}

/**
 * Indica si el usuario en sesión puede ver una página,
 * según los permisos de su rol.
 */
function esPermitido(string $pagina): bool
{
    return \Permiso::puede($pagina);
}

/**
 * Indica si un item del menú es visible para el usuario en sesión.
 */
function menuVisible(array $item): bool
{
    if (!empty($item['roles'])) {
        $rol = strtolower(rolActual());

        if ($rol !== '' && !in_array($rol, array_map('strtolower', $item['roles']), true)) {
            return false;
        }
    }

    return esPermitido($item['pagina']);
}

/**
 * Construye la URL de una página interna.
 */
function url(string $pagina = ''): string
{
    return $pagina === '' ? BASE_URL : BASE_URL . '?page=' . $pagina;
}

/**
 * Primer nombre del usuario en sesión, para el saludo del menú.
 * "Mesero de Prueba" -> "Mesero"
 */
function primerNombreDelUsuario(): string
{
    $completo = trim((string) ($_SESSION['nombre'] ?? ''));

    if ($completo === '') {
        return '';
    }

    $partes = preg_split('/\s+/', $completo);

    return $partes[0] ?? '';
}

/**
 * Primera página que el usuario en sesión puede ver.
 *
 * El mesero y el cocina no tienen dashboard, así que al entrar tienen que
 * caer en algo que sí puedan ver y no en un error de permisos.
 */
function primeraPaginaPermitida(): string
{
    foreach (menuSistema() as $item) {
        if (empty($item['oculto']) && menuVisible($item)) {
            return $item['pagina'];
        }
    }

    return 'dashboard';
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

/**
 * Imprime las pestañas de un módulo.
 *
 * @param array $pestañas [['recurso' => 'x', 'titulo' => 'X', 'url' => '...'], ...]
 */
function PestanasModulo(array $pestañas, string $actual): void
{
    ?>
    <div class="lg-segmentos mb-3">
        <?php foreach ($pestañas as $p): ?>
            <a href="<?= htmlspecialchars($p['url']) ?>"
               class="lg-segmento<?= $p['clave'] === $actual ? ' is-activo' : '' ?>">
                <?php if (!empty($p['icono'])): ?>
                    <i class="fa <?= htmlspecialchars($p['icono']) ?>"></i>
                <?php endif; ?>
                <?= htmlspecialchars($p['titulo']) ?>
                <?php if (isset($p['contador'])): ?>
                    <span class="lg-segmento-badge"><?= (int) $p['contador'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}