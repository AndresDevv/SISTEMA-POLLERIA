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
    'Roles y permisos',
    'Marca qué puede ver y hacer cada rol del sistema'
);

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
?>

<div class="lg-info mb-3">
    <i class="fa fa-info-circle"></i>
    <span>
        El <strong>administrador</strong> ve todo, as&iacute; que su rol tiene marcado
        <em>todos los permisos</em>. Un rol sin permisos solo podr&aacute; iniciar sesi&oacute;n.
    </span>
</div>

<?php foreach ($roles as $rol): ?>
    <form method="POST" action="<?= url('configuracion') ?>&tab=roles" class="lg-card lg-card--flat mb-3">

        <input type="hidden" name="rol_id" value="<?= (int) $rol['id'] ?>">

        <div class="lg-card-head flex-wrap">
            <span class="lg-card-icon"><i class="fa fa-user-tag"></i></span>

            <div class="flex-grow-1">
                <h2 class="lg-card-title"><?= htmlspecialchars($rol['nombre']) ?></h2>
                <p class="lg-card-subtitle">
                    <?= htmlspecialchars($rol['descripcion'] ?? '') ?>
                    &middot; <?= (int) $rol['usuarios'] ?> usuario(s)
                </p>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <select name="estado" class="lg-select" style="max-width:130px;">
                    <option value="1" <?= (int) $rol['estado'] === 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= (int) $rol['estado'] === 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>

                <button type="submit" name="guardar_rol" value="<?= (int) $rol['id'] ?>"
                        class="lg-btn lg-btn--sm lg-btn--primary">
                    <i class="fa fa-check"></i> Guardar
                </button>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($permisos as $permiso): ?>
                <?php $marcado = in_array((int) $permiso['id'], $porRol[$rol['id']] ?? [], true); ?>
                <label class="lg-chip"
                       style="cursor:pointer;
                              border-color:<?= $marcado ? 'var(--lg-rojo)' : 'var(--lg-borde)' ?>;
                              background:<?= $marcado ? 'var(--lg-rojo-claro)' : 'var(--lg-blanco)' ?>;"
                       title="<?= htmlspecialchars($permiso['descripcion'] ?? '') ?>">
                    <input type="checkbox" name="permisos[]" value="<?= (int) $permiso['id'] ?>"
                           <?= $marcado ? 'checked' : '' ?> style="margin-right:6px;">
                    <?= htmlspecialchars($permiso['nombre']) ?>
                </label>
            <?php endforeach; ?>
        </div>

    </form>
<?php endforeach; ?>