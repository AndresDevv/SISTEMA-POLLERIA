<?php
/**
 * Vista: personal (trabajadores, asistencia y pagos).
 * Solo para quien tenga el permiso 'personal' (por defecto, administrador).
 *
 * La pestaña de trabajadores la resuelve el CRUD genérico, que ya imprime
 * el encabezado y las pestañas; las otras dos se dibujan aquí.
 */

require_once APP_ROOT . '/models/Personal.php';
require_once APP_ROOT . '/models/Gestion.php';

$personalModel = new Personal();

$pestana = $_GET['tab'] ?? 'colaboradores';

if (!in_array($pestana, ['colaboradores', 'asistencia', 'pagos'], true)) {
    $pestana = 'colaboradores';
}

$colaboradores = $personalModel->colaboradores();
$asistencias  = $personalModel->asistenciasHoy();
$pagos        = $personalModel->pagos();

/**
 * Pestañas del módulo. Las comparte el CRUD genérico y las vistas propias.
 */
$pestanasPersonal = static function (string $actual): void {
    ?>
    <div class="lg-segmentos mb-3">
        <a href="<?= url('personal') ?>" class="lg-segmento<?= $actual === 'colaboradores' ? ' is-activo' : '' ?>">
            <i class="fa fa-users"></i> Trabajadores
        </a>
        <a href="<?= url('personal') ?>&tab=asistencia" class="lg-segmento<?= $actual === 'asistencia' ? ' is-activo' : '' ?>">
            <i class="fa fa-clock-o"></i> Asistencia
        </a>
        <a href="<?= url('personal') ?>&tab=pagos" class="lg-segmento<?= $actual === 'pagos' ? ' is-activo' : '' ?>">
            <i class="fa fa-money"></i> Pagos
        </a>
    </div>
    <?php
};

if ($pestana === 'colaboradores'):

    $pestanaActual  = $pestana;
    $recurso        = 'empleados';
    $icono          = 'fa fa-users';
    $titulo         = 'Personal';
    $subtitulo      = 'Datos de los trabajadores de la pollería';

    $recursosExtra = [
        ['clave' => 'colaboradores', 'recurso' => 'empleados', 'pagina' => 'personal',
         'titulo' => 'Trabajadores', 'icono' => 'fa fa-users'],
        ['clave' => 'asistencia',    'recurso' => 'empleados', 'pagina' => 'personal&tab=asistencia',
         'titulo' => 'Asistencia', 'icono' => 'fa fa-clock-o'],
        ['clave' => 'pagos',         'recurso' => 'empleados', 'pagina' => 'personal&tab=pagos',
         'titulo' => 'Pagos', 'icono' => 'fa fa-money']
    ];

    require APP_ROOT . '/views/admin/gestion.php';

    return;

endif;

$pestanasPersonal($pestana);

encabezadoPagina(
    'fa fa-users',
    'Personal',
    'Colaboradores, asistencia y pagos del equipo'
);
?>

<?php if ($pestana === 'asistencia'): ?>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-clock-o"></i></span>
            <div>
                <h2 class="lg-card-title">Asistencia de hoy</h2>
                <p class="lg-card-subtitle"><?= date('d/m/Y') ?></p>
            </div>
        </div>

        <?php if (!$colaboradores): ?>
            <div class="lg-empty">
                <i class="fa fa-users"></i>
                <p class="mb-0">No hay colaboradores registrados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Cargo</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colaboradores as $col): ?>
                            <?php
                            $asistencia = null;
                            foreach ($asistencias as $a) {
                                if ((int) $a['empleado_id'] === (int) $col['id']) {
                                    $asistencia = $a;
                                    break;
                                }
                            }
                            ?>

                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($col['nombre']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($col['cargo'] ?? '-') ?></td>
                                <td><?= $asistencia && $asistencia['hora_entrada'] ? date('H:i', strtotime($asistencia['hora_entrada'])) : '—' ?></td>
                                <td><?= $asistencia && $asistencia['hora_salida'] ? date('H:i', strtotime($asistencia['hora_salida'])) : '—' ?></td>
                                <td>
                                    <?php if (!$asistencia): ?>
                                        <span class="lg-pill lg-pill--pizarra">Sin registro</span>
                                    <?php elseif ($asistencia['hora_salida']): ?>
                                        <span class="lg-pill lg-pill--verde">Completado</span>
                                    <?php else: ?>
                                        <span class="lg-pill lg-pill--ambar">En turno</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <?php if (!$asistencia): ?>
                                        <button type="button" class="lg-btn lg-btn--sm lg-btn--verde js-asistencia"
                                                data-emp="<?= (int) $col['id'] ?>" data-tipo="entrada">
                                            <i class="fa fa-sign-in"></i> Entrada
                                        </button>
                                    <?php elseif (!$asistencia['hora_salida']): ?>
                                        <button type="button" class="lg-btn lg-btn--sm lg-btn--primary js-asistencia"
                                                data-emp="<?= (int) $col['id'] ?>" data-tipo="salida">
                                            <i class="fa fa-sign-out"></i> Salida
                                        </button>
                                    <?php else: ?>
                                        <span class="lg-muted">Listo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="lg-card mb-3">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-money"></i></span>
            <div>
                <h2 class="lg-card-title">Registrar pago</h2>
                <p class="lg-card-subtitle">Sueldo o pago por per&iacute;odo</p>
            </div>
        </div>

        <?php if (!$colaboradores): ?>
            <div class="lg-empty"><i class="fa fa-users"></i><p class="mb-0">No hay colaboradores.</p></div>
        <?php else: ?>
            <div class="lg-filters">
                <div class="lg-field">
                    <label class="lg-label" for="pagoEmpleado">Colaborador</label>
                    <select id="pagoEmpleado" class="lg-select">
                        <?php foreach ($colaboradores as $col): ?>
                            <option value="<?= (int) $col['id'] ?>"><?= htmlspecialchars($col['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg-field">
                    <label class="lg-label" for="pagoPeriodo">Per&iacute;odo</label>
                    <input type="text" id="pagoPeriodo" class="lg-input" value="<?= date('Y-m') ?>">
                </div>

                <div class="lg-field">
                    <label class="lg-label" for="pagoMonto">Monto</label>
                    <input type="number" id="pagoMonto" class="lg-input" step="0.01" min="0" value="0">
                </div>

                <div class="lg-field" style="flex:0 0 auto;">
                    <button type="button" class="lg-btn lg-btn--primary lg-btn--sm" id="pagoGuardar">
                        <i class="fa fa-check"></i> Registrar pago
                    </button>
                </div>
            </div>

            <div class="lg-info mt-3" id="pagoAviso" style="display:none;">
                <i class="fa fa-exclamation-circle"></i>
                <span id="pagoAvisoTexto"></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="lg-card">
        <div class="lg-card-head">
            <span class="lg-card-icon"><i class="fa fa-list"></i></span>
            <div>
                <h2 class="lg-card-title">Historial de pagos</h2>
                <p class="lg-card-subtitle"><?= count($pagos) ?> pago(s) registrado(s)</p>
            </div>
        </div>

        <?php if (!$pagos): ?>
            <div class="lg-empty"><i class="fa fa-money"></i><p class="mb-0">Sin pagos registrados.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Cargo</th>
                            <th>Per&iacute;odo</th>
                            <th>Fecha de pago</th>
                            <th style="text-align:right;">Monto</th>
                            <th>Observaci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $p): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($p['empleado']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($p['cargo'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['periodo']) ?></td>
                                <td class="text-muted"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                                <td class="num" style="text-align:right;font-weight:600;"><?= soles((float) $p['monto']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($p['observacion'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>