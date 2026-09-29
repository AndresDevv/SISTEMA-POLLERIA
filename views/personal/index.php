<?php
/**
 * Vista: personal.
 */

$personal = [
    ['nombre' => 'Carlos Gómez',    'usuario' => 'admin',   'rol' => 'Administrador', 'turno' => 'Tarde', 'estado' => 'activo'],
    ['nombre' => 'María Fernández', 'usuario' => 'mesero1', 'rol' => 'Mesero',        'turno' => 'Tarde', 'estado' => 'activo'],
    ['nombre' => 'Jorge Ramírez',   'usuario' => 'cocina1', 'rol' => 'Cocinero',      'turno' => 'Mañana','estado' => 'activo'],
    ['nombre' => 'Luis Chávez',      'usuario' => 'cocina2', 'rol' => 'Cocinero',      'turno' => 'Tarde', 'estado' => 'activo'],
    ['nombre' => 'Ana Torres',       'usuario' => 'cajero1', 'rol' => 'Cajero',       'turno' => 'Tarde', 'estado' => 'inactivo']
];
?>

<?php
encabezadoPagina(
    'fa fa-users',
    'Personal',
    'Administra a los colaboradores y sus roles',
    '<button type="button" class="lg-btn lg-btn--primary"><i class="fa fa-user-plus"></i> Nuevo usuario</button>'
);
?>

<div class="lg-card">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-list"></i></span>
        <div>
            <h2 class="lg-card-title">Lista de colaboradores</h2>
            <p class="lg-card-subtitle">Usuarios con acceso al sistema</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="lg-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Turno</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personal as $p): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($p['nombre']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($p['usuario']) ?></td>
                        <td><?= htmlspecialchars($p['rol']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($p['turno']) ?></td>
                        <td>
                            <?php if ($p['estado'] === 'activo'): ?>
                                <span class="lg-pill lg-pill--verde">Activo</span>
                            <?php else: ?>
                                <span class="lg-pill lg-pill--pizarra">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost">
                                <i class="fa fa-pencil"></i> Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
