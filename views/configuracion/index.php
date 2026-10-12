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
            <i class="fa fa-user-tag"></i> Roles
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
         'titulo' => 'Roles', 'icono' => 'fa fa-user-tag'],
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
    'fa fa-user-tag',
    'Roles',
    'Cada rol es un tipo de persona. El sistema ya sabe qu&eacute; men&uacute;s puede ver cada uno.'
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
$perfiles      = perfilesPredefinidos();

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

/** ¿Los permisos del rol coinciden con su perfil predefinido? */
$coincideConPerfil = static function (array $ids, string $nombreRol) use ($permisos, $esAdmin): bool {
    $perfil = perfilDeRol($nombreRol);

    if ($perfil === null) {
        return false;
    }

    $esperados = permisosDelPerfil($perfil, $permisos, $esAdmin($ids));
    $actuales  = $ids;
    sort($esperados);
    sort($actuales);

    return $esperados === $actuales;
};
?>

<!-- ============================ AGREGAR ROL NUEVO ======================= -->

<div class="lg-card mb-3">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-plus-circle"></i></span>
        <div class="flex-grow-1">
            <h2 class="lg-card-title">Agregar un rol nuevo</h2>
            <p class="lg-card-subtitle">
                Elige de qu&eacute; tipo de persona se trata y el sistema le asigna
                sola los men&uacute;s que le corresponden.
            </p>
        </div>
    </div>

    <form method="POST" action="<?= url('configuracion') ?>&tab=roles" class="row">
        <div class="col-md-3 mb-3">
            <label class="lg-label" for="rolNombre">Nombre del rol</label>
            <input type="text" class="lg-input" id="rolNombre" name="nombre"
                   placeholder="Cajero" maxlength="40" required>
        </div>

        <div class="col-md-5 mb-3">
            <label class="lg-label" for="rolDescripcion">Para qu&eacute; sirve</label>
            <input type="text" class="lg-input" id="rolDescripcion" name="descripcion"
                   placeholder="Cobra los pedidos y lleva la caja del d&iacute;a" maxlength="160">
        </div>

        <div class="col-md-3 mb-3">
            <label class="lg-label" for="rolPerfil">Tipo de persona</label>
            <select class="lg-select" id="rolPerfil" name="perfil">
                <?php foreach ($perfiles as $clave => $perfil): ?>
                    <option value="<?= htmlspecialchars($clave) ?>">
                        <?= htmlspecialchars($perfil['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="lg-muted" id="ayudaPerfil">
                <?= htmlspecialchars($perfiles['mesero']['descripcion']) ?>
            </small>
        </div>

        <div class="col-md-1 mb-3 d-flex align-items-end">
            <button type="submit" name="crear_rol" value="1" class="lg-btn lg-btn--primary w-100">
                <i class="fa fa-plus"></i> Crear
            </button>
        </div>
    </form>
</div>

<!-- ================================ ROLES ============================== -->

<div class="lg-card">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-user-tag"></i></span>
        <div class="flex-grow-1">
            <h2 class="lg-card-title">Roles del sistema</h2>
            <p class="lg-card-subtitle">
                Al crear un usuario soloeligir&aacute;s su rol, y &eacute;ste ya
                sabr&aacute; qu&eacute; men&uacute;s puede ver.
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="lg-table">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Para qu&eacute; sirve</th>
                    <th>Men&uacute;s que puede ver</th>
                    <th class="text-center">Usuarios</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $rol): ?>
                    <?php
                    $rolId    = (int) $rol['id'];
                    $ids      = $porRol[$rolId] ?? [];
                    $modulos  = modulosDelRol($ids, $grupos);
                    $admin    = $esAdmin($ids);
                    $perfil   = perfilDeRol($rol['nombre']);
                    ?>
                    <tr>
                        <td>
                            <span class="lg-fila-nombre">
                                <span class="lg-fila-icono">
                                    <?php // Los perfiles ya traen la clase completa, p. ej. "fa fa-star" ?>
                                    <i class="<?= htmlspecialchars($perfil['icono'] ?? 'fa fa-user-tag') ?>"></i>
                                </span>
                                <span class="lg-fila-texto">
                                    <strong><?= htmlspecialchars($rol['nombre']) ?></strong>
                                    <?php if ($perfil === null): ?>
                                        <small class="lg-pill lg-pill--pizarra">A medida</small>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </td>

                        <td class="lg-muted"><?= htmlspecialchars($rol['descripcion'] ?? '') ?></td>

                        <td>
                            <span class="lg-pilas">
                                <?php if ($admin): ?>
                                    <span class="lg-pill lg-pill--verde">Todos los men&uacute;s</span>
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

    <p class="lg-muted mt-3 mb-0" style="font-size:.82rem;">
        <i class="fa fa-info-circle"></i>
        Los permisos de cada rol son fijos y no se editan aqu&iacute;. Si necesitas
        cambiar qu&eacute; ve alguien, crea un rol nuevo con el tipo de persona
        que le corresponda.
    </p>
</div>

<script>
$(function () {

    // Al elegir el tipo de persona, se explica de qué se encarga
    var descripciones = <?= json_encode(
        array_map(static fn (array $p): string => $p['descripcion'], $perfiles),
        JSON_UNESCAPED_UNICODE
    ) ?>;

    $('#rolPerfil').on('change', function () {
        $('#ayudaPerfil').text(descripciones[this.value] || '');
    });

    // El nombre se escribe solo con el tipo de persona elegido
    $('#rolNombre').on('input', function () {
        if ($(this).data('tocado')) {
            return;
        }

        var nombre = $(this).val();

        // Solo autocompleta mientras el campo esté limpio
        if (nombre === '' || $(this).data('nombre') === nombre) {
            $(this).val($('#rolPerfil option:selected').text());
            $(this).data('nombre', $(this).val());
        }
    }).on('keyup', function () {
        $(this).data('tocado', true);
    });

});
</script>
