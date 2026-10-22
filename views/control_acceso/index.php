<?php
/**
 * Vista: Control de acceso — pantalla para que el personal marque entrada/salida.
 *
 * Variables esperadas:
 *   - $empleados: array de empleados activos con id, nombre, usuario, pin
 *   - $empleadoSel: empleado seleccionado (si hay POST)
 *   - $estado: 'sin_registro' | 'entrada' | 'salida' | 'completado' | 'crear_pin'
 *   - $mensaje: mensaje de éxito/error
 *   - $accion: 'entrada' | 'salida'
 */

require_once __DIR__ . '/../../models/Personal.php';
require_once __DIR__ . '/../../models/Asistencia.php';

$empleadoModel = new Personal();
$asistenciaModel = new Asistencia();

$empleados = $empleadoModel->listarActivos();

$empleadoSel = null;
$estado = 'sin_registro';
$mensaje = '';
$accion = '';
$crearPin = false;

// Si viene un empleado seleccionado (GET o POST)
$empleadoId = (int) ($_GET['emp'] ?? $_POST['empleado_id'] ?? 0);

if ($empleadoId > 0) {
    $empleadoSel = $empleadoModel->buscarPorId($empleadoId);
    
    if (!$empleadoSel) {
        $mensaje = 'Empleado no encontrado.';
        $empleadoSel = null;
    } else {
        // Verificar estado de asistencia de hoy
        $hoy = date('Y-m-d');
        $asistenciaHoy = $asistenciaModel->obtenerDeHoy($empleadoId);
        
        if (!$asistenciaHoy) {
            $estado = 'sin_registro';
            $accion = 'entrada';
        } elseif ($asistenciaHoy['hora_entrada'] && !$asistenciaHoy['hora_salida']) {
            $estado = 'entrada';
            $accion = 'salida';
        } else {
            $estado = 'completado';
        }
        
        // Si el empleado no tiene PIN y viene a marcar, ir a crear PIN
        if (empty($empleadoSel['pin']) && $estado !== 'completado') {
            $estado = 'crear_pin';
        }
    }
}

// Procesar POST: crear PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pin'])) {
    $empleadoId = (int) $_POST['empleado_id'];
    $pin1 = trim($_POST['pin1'] ?? '');
    $pin2 = trim($_POST['pin2'] ?? '');
    
    if ($pin1 === '' || $pin2 === '') {
        $mensaje = 'Debe ingresar el PIN en ambos campos.';
    } elseif (strlen($pin1) !== 4 || !ctype_digit($pin1)) {
        $mensaje = 'El PIN debe ser de 4 dígitos numéricos.';
    } elseif ($pin1 !== $pin2) {
        $mensaje = 'Los PINs no coinciden.';
    } else {
        $ok = $empleadoModel->actualizarPin($empleadoId, $pin1);
        if ($ok) {
            $mensaje = 'PIN creado correctamente. Ahora puede marcar su asistencia.';
            // Recargar empleado
            $empleadoSel = $empleadoModel->buscarPorId($empleadoId);
            // Recalcular estado
            $asistenciaHoy = $asistenciaModel->obtenerDeHoy($empleadoId);
            if (!$asistenciaHoy) {
                $estado = 'sin_registro';
                $accion = 'entrada';
            } elseif ($asistenciaHoy['hora_entrada'] && !$asistenciaHoy['hora_salida']) {
                $estado = 'entrada';
                $accion = 'salida';
            } else {
                $estado = 'completado';
            }
        } else {
            $mensaje = 'Error al guardar el PIN.';
        }
    }
}

// Procesar POST: marcar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar'])) {
    $empleadoId = (int) $_POST['empleado_id'];
    $pinIngresado = trim($_POST['pin'] ?? '');
    $accion = $_POST['accion'] ?? '';
    
    $empleadoSel = $empleadoModel->buscarPorId($empleadoId);
    
    if (!$empleadoSel) {
        $mensaje = 'Empleado no encontrado.';
    } elseif (empty($empleadoSel['pin'])) {
        $mensaje = 'Este empleado no tiene PIN configurado.';
        $estado = 'crear_pin';
    } elseif ($pinIngresado !== $empleadoSel['pin']) {
        $mensaje = 'PIN incorrecto.';
        $estado = ($accion === 'entrada') ? 'sin_registro' : 'entrada';
    } else {
        // PIN correcto, registrar asistencia
        $ok = false;
        if ($accion === 'entrada') {
            $ok = $asistenciaModel->marcarEntrada($empleadoId);
        } elseif ($accion === 'salida') {
            $ok = $asistenciaModel->marcarSalida($empleadoId);
        }
        
        if ($ok) {
            // Registrar en log de auditoría
            $asistenciaModel->registrarLog($empleadoId, $accion, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $mensaje = ucfirst($accion) . ' registrada a las ' . date('H:i:s');
            // Recargar estado
            $asistenciaHoy = $asistenciaModel->obtenerDeHoy($empleadoId);
            if (!$asistenciaHoy) {
                $estado = 'sin_registro';
                $accion = 'entrada';
            } elseif ($asistenciaHoy['hora_entrada'] && !$asistenciaHoy['hora_salida']) {
                $estado = 'entrada';
                $accion = 'salida';
            } else {
                $estado = 'completado';
            }
        } else {
            $mensaje = 'Error al registrar la ' . $accion . '.';
        }
    }
}
?>

<?php encabezadoPagina(
    'fa fa-clock-o',
    'Control de acceso',
    'Marque su entrada y salida con su PIN'
); ?>

<!-- Lista de empleados -->
<div class="lg-card mb-3">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-users"></i></span>
        <div>
            <h2 class="lg-card-title">Personal activo</h2>
            <p class="lg-card-subtitle">Toque su nombre para marcar entrada o salida</p>
        </div>
    </div>

    <div class="input-group mb-3 lg-buscar">
        <input type="search"
               class="lg-input"
               id="filtroEmpleados"
               placeholder="Buscar por nombre o usuario..."
               autocomplete="off"
               aria-label="Buscar empleado">
        <span class="input-group-btn">
            <button type="button" class="lg-btn lg-btn--primary" id="btnLimpiarBusqueda" title="Limpiar">
                <i class="fa fa-times"></i>
            </button>
        </span>
    </div>

    <?php if (empty($empleados)): ?>
        <div class="lg-empty">
            <i class="fa fa-users"></i>
            <p class="mb-0">No hay personal activo registrado.</p>
        </div>
    <?php else: ?>
        <div class="row" id="listaEmpleados">
            <?php foreach ($empleados as $emp): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3 empleado-card" 
                     data-nombre="<?= htmlspecialchars(strtolower($emp['nombre'])) ?>"
                     data-usuario="<?= htmlspecialchars(strtolower($emp['usuario'])) ?>">
                    <button type="button" 
                            class="lg-btn lg-btn--outline lg-btn--block btn-seleccionar-empleado"
                            data-empleado-id="<?= (int) $emp['id'] ?>"
                            style="min-height: 80px; font-size: 1.1rem;">
                        <i class="fa fa-user fa-lg d-block mb-1"></i>
                        <strong><?= htmlspecialchars($emp['nombre']) ?></strong>
                        <br>
                        <small class="text-muted">@<?= htmlspecialchars($emp['usuario']) ?></small>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Área de acción (PIN / Confirmación) -->
<div id="areaAccion" class="lg-card mb-3" style="display: <?= $empleadoSel ? 'block' : 'none' ?>;">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-key"></i></span>
        <div>
            <h2 class="lg-card-title" id="tituloAccion">Marcar asistencia</h2>
            <p class="lg-card-subtitle" id="subtituloAccion">Ingrese su PIN de 4 dígitos</p>
        </div>
    </div>

    <div class="lg-card-body">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= strpos($mensaje, 'Error') !== false || strpos($mensaje, 'incorrecto') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Crear PIN -->
        <?php if ($estado === 'crear_pin' && $empleadoSel): ?>
            <form method="post" id="formCrearPin">
                <input type="hidden" name="empleado_id" value="<?= (int) $empleadoSel['id'] ?>">
                <input type="hidden" name="crear_pin" value="1">
                <div class="form-group">
                    <label class="font-weight-bold">Cree su PIN de 4 dígitos</label>
                    <div class="input-group mb-2">
                        <input type="password" 
                               class="lg-input text-center form-control-lg" 
                               name="pin1" 
                               maxlength="4" 
                               pattern="\d{4}" 
                               placeholder="****" 
                               required
                               autocomplete="off"
                               style="font-size: 2rem; letter-spacing: 0.5em;">
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" 
                               class="lg-input text-center form-control-lg" 
                               name="pin2" 
                               maxlength="4" 
                               pattern="\d{4}" 
                               placeholder="****" 
                               required
                               autocomplete="off"
                               style="font-size: 2rem; letter-spacing: 0.5em;">
                    </div>
                    <small class="text-muted d-block mb-3">El PIN debe ser de 4 dígitos numéricos. Solo usted debe conocerlo.</small>
                    <button type="submit" name="crear_pin" class="lg-btn lg-btn--primary lg-btn--block" style="min-height: 56px; font-size: 1.2rem;">
                        <i class="fa fa-save"></i> Guardar PIN
                    </button>
                </div>
            </form>

        <!-- Marcar Entrada / Salida -->
        <?php elseif (($estado === 'sin_registro' || $estado === 'entrada') && $empleadoSel): ?>
            <form method="post" id="formMarcar">
                <input type="hidden" name="empleado_id" value="<?= (int) $empleadoSel['id'] ?>">
                <input type="hidden" name="marcar" value="1">
                <input type="hidden" name="accion" id="inputAccion" value="<?= htmlspecialchars($accion) ?>">
                
                <div class="text-center mb-3">
                    <div class="d-inline-block p-3 rounded" style="background: <?= $accion === 'entrada' ? '#E8F5E9' : '#FFF3E0' ?>;">
                        <i class="fa fa-<?= $accion === 'entrada' ? 'sign-in' : 'sign-out' ?> fa-3x" style="color: <?= $accion === 'entrada' ? '#2E7D32' : '#EF6C00' ?>;"></i>
                    </div>
                    <h4 class="mt-2 mb-1"><?= htmlspecialchars($empleadoSel['nombre']) ?></h4>
                    <small class="text-muted">@<?= htmlspecialchars($empleadoSel['usuario']) ?></small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">PIN de 4 dígitos</label>
                    <div class="input-group mb-3">
                        <input type="password" 
                               class="lg-input text-center form-control-lg" 
                               name="pin" 
                               id="inputPin"
                               maxlength="4" 
                               pattern="\d{4}" 
                               placeholder="****" 
                               required
                               autocomplete="off"
                               autofocus
                               style="font-size: 2.5rem; letter-spacing: 0.8em;">
                    </div>
                </div>

                <input type="hidden" name="marcar" value="1">
                <input type="hidden" name="accion" value="<?= htmlspecialchars($accion) ?>">
                
                <button type="submit" class="lg-btn lg-btn--<?= $accion === 'entrada' ? 'success' : 'warning' ?> lg-btn--block" style="min-height: 64px; font-size: 1.3rem;">
                    <i class="fa fa-<?= $accion === 'entrada' ? 'sign-in' : 'sign-out' ?>"></i> 
                    Confirmar <?= ucfirst($accion) ?>
                </button>
            </form>

        <!-- Jornada completada -->
        <?php elseif ($estado === 'completado' && $empleadoSel): ?>
            <div class="text-center py-4">
                <div class="d-inline-block p-4 rounded-circle" style="background: #E8F5E9;">
                    <i class="fa fa-check-circle fa-4x" style="color: #2E7D32;"></i>
                </div>
                <h3 class="mt-3 text-success">Jornada completada</h3>
                <p class="text-muted mb-1"><strong><?= htmlspecialchars($empleadoSel['nombre']) ?></strong></p>
                <p class="text-muted">@<?= htmlspecialchars($empleadoSel['usuario']) ?></p>
                <hr class="my-3">
                <p class="mb-1">Entrada: <strong><?= date('H:i', strtotime($asistenciaHoy['hora_entrada'])) ?></strong></p>
                <p class="mb-0">Salida: <strong><?= date('H:i', strtotime($asistenciaHoy['hora_salida'])) ?></strong></p>
            </div>

        <!-- Sin registro (empleado sin PIN) -->
        <?php elseif ($estado === 'sin_registro' && $empleadoSel && empty($empleadoSel['pin'])): ?>
            <div class="text-center py-4">
                <div class="d-inline-block p-4 rounded-circle" style="background: #FFF3E0;">
                    <i class="fa fa-key fa-4x" style="color: #EF6C00;"></i>
                </div>
                <h3 class="mt-3">PIN no configurado</h3>
                <p class="text-muted mb-3">Debe crear su PIN antes de marcar asistencia.</p>
                <button type="button" class="lg-btn lg-btn--primary" onclick="volverALista()" style="min-width: 200px;">
                    <i class="fa fa-arrow-left"></i> Crear PIN
                </button>
            </div>
        <?php endif; ?>

        <div class="text-center mt-3">
            <button type="button" class="lg-btn lg-btn--outline" onclick="volverALista()" style="min-width: 160px;">
                <i class="fa fa-arrow-left"></i> Volver
            </button>
        </div>
    </div>
</div>

<!-- JavaScript para búsqueda y navegación -->
<script>
(function () {
    var filtro = document.getElementById('filtroEmpleados');
    var lista = document.getElementById('listaEmpleados');
    var areaAccion = document.getElementById('areaAccion');
    var btnLimpiar = document.getElementById('btnLimpiarBusqueda');
    var cards = lista ? lista.querySelectorAll('.empleado-card') : [];

    // Filtrado en tiempo real
    if (filtro) {
        filtro.addEventListener('input', function () {
            var termino = this.value.toLowerCase().trim();
            cards.forEach(function (card) {
                var nombre = card.dataset.nombre || '';
                var usuario = card.dataset.usuario || '';
                var coincide = termino === '' || nombre.indexOf(termino) !== -1 || usuario.indexOf(termino) !== -1;
                card.style.display = coincide ? '' : 'none';
            });
        });
    }

    // Limpiar búsqueda
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            if (filtro) {
                filtro.value = '';
                filtro.dispatchEvent(new Event('input'));
                filtro.focus();
            }
        });
    }

    // Seleccionar empleado
    document.querySelectorAll('.btn-seleccionar-empleado').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var empId = this.dataset.empleadoId;
            window.location.href = '?page=control_acceso&emp=' + empId;
        });
    });

    // Auto-enviar PIN al completar 4 dígitos
    var inputPin = document.getElementById('inputPin');
    if (inputPin) {
        inputPin.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length === 4) {
                var form = this.closest('form');
                if (form) form.submit();
            }
        });
    }

    // Auto-enviar PIN al crear (segundo campo)
    var pin2 = document.querySelector('input[name="pin2"]');
    if (pin2) {
        pin2.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length === 4) {
                var form = this.closest('form');
                if (form) form.submit();
            }
        });
    }

    // Volver a lista
    window.volverALista = function () {
        window.location.href = '?page=control_acceso';
    };

    // Focus en PIN al cargar
    if (inputPin) {
        setTimeout(function () { inputPin.focus(); }, 100);
    }
    var pin1 = document.querySelector('input[name="pin1"]');
    if (pin1) {
        setTimeout(function () { pin1.focus(); }, 100);
    }
})();
</script>