<?php
/**
 * Vista: configuración (usuarios, roles y datos del negocio).
 *
 * Usuarios y Negocio los resuelve el CRUD genérico, que ya imprime el
 * encabezado y las pestañas. La pestaña de Roles se dibuja aquí porque
 * los permisos se guardan con un formulario propio.
 */

require_once APP_ROOT . '/models/Gestion.php';
require_once APP_ROOT . '/models/Permiso.php';

$pestana = $_GET['tab'] ?? 'usuarios';

if (!in_array($pestana, ['usuarios', 'roles', 'negocio'], true)) {
    $pestana = 'usuarios';
}

/**
 * Pestañas del módulo.
 */
$pestanasConfig = static function (string $actual): void {
    ?>
    <div class="lg-segmentos mb-3">
        <a href="<?= url('configuracion') ?>" class="lg-segmento<?= $actual === 'usuarios' ? ' is-activo' : '' ?>">
            <i class="fa fa-user"></i> Usuarios
        </a>
        <a href="<?= url('configuracion') ?>&tab=roles" class="lg-segmento<?= $actual === 'roles' ? ' is-activo' : '' ?>">
            <i class="fa fa-id-badge"></i> Roles
        </a>
        <a href="<?= url('configuracion') ?>&tab=negocio" class="lg-segmento<?= $actual === 'negocio' ? ' is-activo' : '' ?>">
            <i class="fa fa-cog"></i> Negocio
        </a>
    </div>
    <?php
};

if ($pestana !== 'roles'):

    $pestanaActual = $pestana;

    $recursosExtra = [
        ['clave' => 'usuarios', 'recurso' => $pestana === 'negocio' ? 'configuracion_negocio' : 'usuarios',
            'pagina' => 'configuracion',                   'titulo' => 'Usuarios', 'icono' => 'fa fa-user'],
        ['clave' => 'roles',    'recurso' => 'usuarios', 'pagina' => 'configuracion&tab=roles',
            'titulo' => 'Roles', 'icono' => 'fa fa-id-badge'],
        ['clave' => 'negocio',  'recurso' => 'usuarios', 'pagina' => 'configuracion&tab=negocio',
            'titulo' => 'Negocio', 'icono' => 'fa fa-cog']
    ];

    $recurso       = $pestana === 'negocio' ? 'configuracion_negocio' : 'usuarios';
    $icono         = 'fa fa-cog';
    $titulo        = 'Configuración';
    $subtitulo     = $pestana === 'negocio'
        ? 'Datos del negocio'
        : 'Usuarios con acceso al sistema';

    require APP_ROOT . '/views/admin/gestion.php';

    return;

endif;
$pestanasConfig($pestana);

encabezadoPagina(
    'fa fa-id-badge',
    'Roles',
    'Cada rol es un tipo de persona. Al crear un rol marcas qué menús puede ver.'
);

require_once APP_ROOT . '/config/permisos.php';

$db = conexionDB();

$roles = $db->query(
    "SELECT r.id, r.nombre, r.descripcion, r.estado,
            (SELECT COUNT(*) FROM usuarios u WHERE u.rol_id = r.id) AS usuarios
        FROM roles r
        ORDER BY r.id"
)->fetchAll();

$permisos = $db->query("SELECT id, nombre, descripcion FROM permisos ORDER BY id")->fetchAll();

/** Permiso ya asignado a cada rol. */
$porRol = [];

foreach ($db->query("SELECT rol_id, permiso_id FROM rol_permiso")->fetchAll() as $fila) {
    $porRol[$fila['rol_id']][] = (int) $fila['permiso_id'];
}

// Los permisos traducidos y agrupados por módulo, para resumir qué ve cada rol
$grupos = permisosAgrupados($permisos);

$totalPermisos = count($permisos);

// Paginación de la tabla de roles
$pagRoles = paginar($roles, 20);
/**
 * El administrador puede ser cualquier rol con el permiso "configuracion".
 * Si lo tiene, ve todos los menús sin importar la lista.
 */
$permisoConfig = null;

foreach ($permisos as $p) {
    if ((string) $p['nombre'] === 'configuracion') {
        $permisoConfig = (int) $p['id'];
    }
}

$esAdmin = static function (array $ids) use ($permisoConfig): bool {
    return $permisoConfig !== null && in_array($permisoConfig, $ids, true);
};
?>

<!-- ============================ AGREGAR ROL NUEVO ======================= -->

<div class="lg-card mb-3">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-plus-circle"></i></span>
        <div class="flex-grow-1">
            <h2 class="lg-card-title">Agregar un rol nuevo</h2>
            <p class="lg-card-subtitle">
                Escribe el nombre, para qu&eacute; sirve y marca qu&eacute; puede ver.
            </p>
        </div>
    </div>

    <form method="POST" action="<?= url('configuracion') ?>&tab=roles">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="lg-label" for="rolNombre">Nombre del rol</label>
                <input type="text" class="lg-input" id="rolNombre" name="nombre"
                        placeholder="Cajero" maxlength="40" required>
            </div>

            <div class="col-md-8 mb-3">
                <label class="lg-label" for="rolDescripcion">Para qué sirve</label>
                <input type="text" class="lg-input" id="rolDescripcion" name="descripcion"
                    placeholder="Cobra los pedidos y lleva la caja del día" maxlength="160">
            </div>
        </div>

        <label class="lg-label">
            Qué menús y botones puede usar este rol
        </label>

        <?php foreach ($grupos as $grupo): ?>
            <div class="lg-rol-grupo">
                <div class="lg-rol-grupo-head">
                    <i class="<?= htmlspecialchars($grupo['icono']) ?>"></i>
                    <span><?= htmlspecialchars($grupo['titulo']) ?></span>
                    <?php if ($grupo['nota'] !== ''): ?>
                        <small class="lg-muted"><?= htmlspecialchars($grupo['nota']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="lg-rol-grupo-items">
                    <?php foreach ($grupo['permisos'] as $permiso): ?>
                        <label class="lg-chip lg-chip--permiso">
                            <input type="checkbox" name="permisos[]" value="<?= (int) $permiso['id'] ?>"
                                class="js-permiso-nuevo">
                            <span>
                                <strong><?= htmlspecialchars($permiso['nombre']) ?></strong>
                                <small class="lg-muted d-block"><?= htmlspecialchars($permiso['ayuda']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="submit" name="crear_rol" value="1" class="lg-btn lg-btn--primary">
                <i class="fa fa-plus"></i> Crear rol
            </button>

            <button type="button" class="lg-btn lg-btn--ghost js-marcar-todo">
                <i class="fa fa-check"></i> Marcar todos
            </button>

            <button type="button" class="lg-btn lg-btn--ghost js-desmarcar-todo">
                <i class="fa fa-times"></i> Desmarcar todos
            </button>

            <span class="lg-muted js-cuenta">
                0 de <?= $totalPermisos ?> permisos marcados
            </span>
        </div>
    </form>
</div>

<!-- ================================ ROLES ============================== -->

<div class="lg-card">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-id-badge"></i></span>
        <div class="flex-grow-1">
            <h2 class="lg-card-title">Roles del sistema</h2>
            <p class="lg-card-subtitle">
                <i class="fa fa-info-circle"></i>
                Al crear un usuario solo eligirás su rol, y éste ya
                sabrá qué menús puede ver.
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="lg-table">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Para qué sirve</th>
                    <th>Menús que puede ver</th>
                    <th class="text-center">Usuarios</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagRoles['filas'] as $rol): ?>
                    <?php
                    $rolId   = (int) $rol['id'];
                    $ids     = $porRol[$rolId] ?? [];
                    $modulos = modulosDelRol($ids, $grupos);
                    $admin   = $esAdmin($ids);
                    ?>
                    <tr>
                        <td>
                            <span class="lg-fila-nombre">
                                <span class="lg-fila-icono">
                                    <i class="fa fa-id-badge"></i>
                                </span>
                                <span class="lg-fila-texto">
                                    <strong><?= htmlspecialchars($rol['nombre']) ?></strong>
                                </span>
                            </span>
                        </td>

                        <td class="lg-muted"><?= htmlspecialchars($rol['descripcion'] ?? '') ?></td>

                        <td>
                            <span class="lg-pilas">
                                <?php if ($admin): ?>
                                    <span class="lg-pill lg-pill--verde">Todos los menús</span>
                                <?php elseif ($modulos): ?>
                                    <?php foreach ($modulos as $modulo): ?>
                                        <span class="lg-pill lg-pill--pizarra">
                                            <?= htmlspecialchars($modulo['titulo']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="lg-muted">Ninguno</span>
                                <?php endif; ?>
                            </span>
                        </td>

                        <td class="text-center"><?= (int) $rol['usuarios'] ?></td>

                        <td class="text-center">
                            <form method="POST" action="<?= url('configuracion') ?>&tab=roles">
                                <input type="hidden" name="rol_id" value="<?= $rolId ?>">
                                <input type="hidden" name="nombre" value="<?= htmlspecialchars($rol['nombre']) ?>">
                                <input type="hidden" name="descripcion" value="<?= htmlspecialchars($rol['descripcion'] ?? '') ?>">

                                <select name="estado" class="lg-select lg-select--sm"
                                        onchange="this.form.submit();">
                                    <option value="1" <?= (int) $rol['estado'] === 1 ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= (int) $rol['estado'] === 0 ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </form>
                        </td>

                        <td class="text-right">
                            <?php if ((int) $rol['usuarios'] === 0): ?>
                                <button type="submit" name="eliminar_rol" value="<?= $rolId ?>"
                                        form="form-eliminar-<?= $rolId ?>"
                                        class="lg-btn lg-btn--sm lg-btn--ghost">
                                    <i class="fa fa-trash-o"></i> Eliminar
                                </button>
                                <form id="form-eliminar-<?= $rolId ?>" method="POST"
                                    action="<?= url('configuracion') ?>&tab=roles" style="display:none;">
                                    <input type="hidden" name="rol_id" value="<?= $rolId ?>">
                                </form>
                            <?php else: ?>
                                <span class="lg-muted" style="font-size:.78rem;">
                                    En uso, no se puede eliminar
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php barraPaginacion($pagRoles); ?>

    <p class="lg-muted mt-3 mb-0" style="font-size:.82rem;">
        <i class="fa fa-info-circle"></i>
        Los permisos se marcan al crear el rol. Para cambiar qu&eacute; ve uno que ya
        existe, crea otro rol con los permisos que necesitas.
    </p>
</div>

<script>
$(function () {

    // Marcar y desmarcar en bloque, para el formulario de rol nuevo
    $('.js-marcar-todo').on('click', function () {
        $('.js-permiso-nuevo').prop('checked', true).trigger('change');
    });

    $('.js-desmarcar-todo').on('click', function () {
        $('.js-permiso-nuevo').prop('checked', false).trigger('change');
    });

    // Contador y resaltado de las casillas marcadas
    var refrescar = function () {
        var $marcadas = $('.js-permiso-nuevo:checked');

        $('.js-cuenta').text(
            $marcadas.length + ' de ' + $('.js-permiso-nuevo').length + ' permisos marcados'
        );

        $('.lg-chip--permiso').each(function () {
            $(this).toggleClass('is-activo', $(this).find('.js-permiso-nuevo').is(':checked'));
        });
    };

    $(document).on('change', '.js-permiso-nuevo', refrescar);
    refrescar();

    // Aviso si se sale con permisos marcados sin haber creado el rol
    $(window).on('beforeunload', function () {
        if ($('.js-permiso-nuevo:checked').length) {
            return 'Hay permisos marcados que no guardaste.';
        }
    });

});
</script>
