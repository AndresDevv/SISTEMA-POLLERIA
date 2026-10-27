<?php

/**
 * Front controller del sistema POLLERIA.
 * Todas las páginas se sirven por: index.php?page=<nombre>
 *
 * El orden de las comprobaciones es importante:
 *   1. resolver ruta
 *   2. proteger la sesión      (nadie entra sin iniciar sesión)
 *   3. revisar el permiso      (cada rol solo accede a lo suyo)
 *   4. ejecutar la acción      (API, exportación o render de la vista)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/models/Permiso.php';
require_once __DIR__ . '/config/router.php';
require_once __DIR__ . '/config/menu.php';
require_once __DIR__ . '/config/estados.php';
require_once __DIR__ . '/config/paginacion.php';

$pagina = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : 'dashboard';
$ruta   = resolverRuta($pagina);

// --- Cerrar sesión -------------------------------------------------------
if (($ruta['accion'] ?? '') === 'logout') {
    accionLogout();
}

// --- Protección de sesión ------------------------------------------------
// Todas las rutas, incluidas las de API, exigen sesión iniciada.
$esPublica = !empty($ruta['publica']);

if (!$esPublica && !sesionActiva()) {

    // Las APIs responden JSON, no con una redirección a la página de login
    if (!empty($ruta['api'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
        exit;
    }

    redirigir('?page=login');
}

// --- Control de permisos -------------------------------------------------
if (!$esPublica && !empty($ruta['permiso']) && !esPermitido($ruta['permiso'])) {

    // La pantalla de inicio y el logo apuntan al dashboard, que el mesero y
    // el cocina no tienen. En vez de un error, los mandamos a la primera
    // página que sí pueden ver.
    $esPantallaDeInicio = ($pagina === 'dashboard' || $ruta === null);

    if ($esPantallaDeInicio) {
        redirigir('?page=' . primeraPaginaPermitida());
    }

    if (!empty($ruta['api'])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para esta acción.']);
        exit;
    }

    http_response_code(403);
    $ruta = [
        'vista'  => 'errors/403',
        'titulo' => 'Sin permisos'
    ];
}

// --- Ruta inexistente ----------------------------------------------------
if ($ruta === null) {
    http_response_code(404);
    $ruta = [
        'vista'  => 'errors/404',
        'titulo' => 'Página no encontrada'
    ];
}

// --- Si ya hay sesión y pide el login, va a su página de inicio -----------
if ($esPublica && sesionActiva() && $pagina === 'login') {
    redirigir('?page=' . primeraPaginaPermitida());
}

// --- Exportación de reportes (CSV) --------------------------------------
if (($ruta['accion'] ?? '') === 'reporte_exportar') {

    require __DIR__ . '/controllers/ReporteController.php';

    exit;
}

// --- Peticiones AJAX -----------------------------------------------------
$accionesApi = [
    'pedido_crear'       => ['PedidoController', 'crear'],
    'pedido_estado'      => ['PedidoController', 'cambiarEstado'],
    'pedido_listar'      => ['PedidoController', 'listar'],
    'pedido_resumen'     => ['PedidoController', 'resumen'],
    'pedido_actualizar'  => ['PedidoController', 'actualizar'],
    'pedido_eliminar'    => ['PedidoController', 'eliminar'],
    'caja_cuenta'        => ['PedidoController', 'cuentaMesa'],
    'caja_cobrar'        => ['PedidoController', 'cobrarMesa'],
    'pulso'              => ['PulsoController', 'pulso'],
    'mesa_reservar'      => ['MesaController', 'reservar'],
    'mesa_liberar'       => ['MesaController', 'liberar'],
    'mesa_crear'         => ['MesaController', 'crear'],
    'mesa_eliminar'      => ['MesaController', 'eliminar'],
    'gestion_listar'     => ['GestionController', 'listar'],
    'gestion_opciones'   => ['GestionController', 'opciones'],
    'gestion_crear'      => ['GestionController', 'crear'],
    'gestion_actualizar' => ['GestionController', 'actualizar'],
    'gestion_eliminar'   => ['GestionController', 'eliminar'],
    'menu_agotado'        => ['OperacionController', 'menuAgotado'],
    'menu_catalogo'       => ['OperacionController', 'menuCatalogo'],
    'inventario_ajustar' => ['OperacionController', 'inventarioAjustar'],
    'personal_asistencia' => ['OperacionController', 'asistencia'],
    'personal_pago'      => ['OperacionController', 'pago']
];

$accionActual = $ruta['accion'] ?? '';

if (isset($accionesApi[$accionActual])) {

    /**
     * Si mandan JSON, tiene que ser JSON válido.
     *
     * Sin esto, un cuerpo mal formado (por ejemplo enviado en otra
     * codificación, con las tildes corruptas) llegaba al controlador como
     * si estuviera vacío y respondía "campo no indicado", que no ayuda a
     * encontrar el problema.
     */
    $tipoContenido = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (str_contains($tipoContenido, 'json')) {
        $cuerpoCrudo = file_get_contents('php://input');

        if (is_string($cuerpoCrudo) && trim($cuerpoCrudo) !== ''
            && json_decode($cuerpoCrudo, true) === null) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Los datos llegaron mal formados. Revisa que se envíen en UTF-8.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Las de solo lectura aceptan GET, el resto solo POST
    $soloGet = !empty($ruta['soloGet']);
    $metodoCorrecto = $soloGet
        ? $_SERVER['REQUEST_METHOD'] === 'GET'
        : $_SERVER['REQUEST_METHOD'] === 'POST';

    if (!$metodoCorrecto) {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    [$controlador, $metodo] = $accionesApi[$accionActual];

    require_once __DIR__ . '/controllers/' . $controlador . '.php';

    $api = new $controlador();
    $api->$metodo();
}

// --- Roles (Configuración > Roles) ---------------------------------------
// Los permisos se marcan al crear el rol. Para los roles ya existentes solo
// se actualizan nombre, descripción y estado.
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $pagina === 'configuracion'
    && (isset($_POST['guardar_rol']) || isset($_POST['crear_rol']) || isset($_POST['eliminar_rol']))) {

    require_once __DIR__ . '/config/database.php';

    $db = conexionDB();

    $rolId  = (int) ($_POST['rol_id'] ?? 0);
    $nombre = trim((string) ($_POST['nombre'] ?? ''));

    // --- Crear un rol nuevo ---------------------------------------------
    if (isset($_POST['crear_rol'])) {

        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $marcados    = $_POST['permisos'] ?? [];

        if ($nombre === '') {

            $_SESSION['mensaje'] = [
                'tipo'  => 'error',
                'texto' => 'Escribe el nombre del rol.'
            ];

        } else {
            $existe = $db->prepare("SELECT id FROM roles WHERE nombre = :nombre");
            $existe->execute([':nombre' => $nombre]);

            if ($existe->fetch()) {

                $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Ya existe un rol con ese nombre.'];

            } elseif (!is_array($marcados) || !$marcados) {

                $_SESSION['mensaje'] = [
                    'tipo'  => 'error',
                    'texto' => 'Marca al menos un permiso para el rol, si no no podrá ver nada.'
                ];

            } else {
                $db->prepare(
                    "INSERT INTO roles (nombre, descripcion, estado) VALUES (:nombre, :descripcion, 1)"
                )->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);

                $nuevoId = (int) $db->lastInsertId();

                // Solo se guardan los permisos marcados y que existen en la base
                $validos = array_map(
                    static fn (array $p): int => (int) $p['id'],
                    $db->query("SELECT id FROM permisos")->fetchAll()
                );

                $stmt = $db->prepare(
                    "INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (:rol, :permiso)"
                );

                foreach ($marcados as $id) {

                    $id = (int) $id;

                    if (in_array($id, $validos, true)) {
                        $stmt->execute([':rol' => $nuevoId, ':permiso' => $id]);
                    }
                }

                $cuenta = count(array_unique(array_map('intval', $marcados)));

                $_SESSION['mensaje'] = [
                    'tipo'  => 'ok',
                    'texto' => 'Rol "' . $nombre . '" creado con ' . $cuenta
                            . ' permiso' . ($cuenta === 1 ? '' : 's')
                            . '. Ya puede usarse al agregar usuarios.'
                ];
            }
        }

        Permiso::limpiarCache();
        redirigir('?page=configuracion&tab=roles');
    }

    // --- Eliminar un rol ------------------------------------------------
    if (isset($_POST['eliminar_rol'])) {

        $enUso = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = :rol");
        $enUso->execute([':rol' => $rolId]);
        $cuenta = (int) $enUso->fetchColumn();

        if ($rolId > 0 && $cuenta === 0) {

            $db->prepare("DELETE FROM rol_permiso WHERE rol_id = :rol")->execute([':rol' => $rolId]);
            $db->prepare("DELETE FROM roles WHERE id = :rol")->execute([':rol' => $rolId]);

            $_SESSION['mensaje'] = ['tipo' => 'ok', 'texto' => 'Rol eliminado.'];

        } elseif ($cuenta > 0) {

            $_SESSION['mensaje'] = [
                'tipo'  => 'error',
                'texto' => 'Ese rol tiene usuarios asignados. Quítaselos antes de eliminarlo.'
            ];
        }

        Permiso::limpiarCache();
        redirigir('?page=configuracion&tab=roles');
    }

    // --- Guardar nombre, descripción y estado ---------------------------
    if ($rolId > 0 && $nombre !== '') {

        $db->prepare(
            "UPDATE roles SET nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id"
        )->execute([
            ':nombre'      => $nombre,
            ':descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            ':estado'      => isset($_POST['estado']) ? (int) $_POST['estado'] : 1,
            ':id'          => $rolId,
        ]);

        Permiso::limpiarCache();
    }

    redirigir('?page=configuracion&tab=roles');
}

// --- Login: procesamiento del formulario ---------------------------------
$error = '';

if ($pagina === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {

        $error = 'Completa todos los campos.';

    } else {

        require_once __DIR__ . '/controllers/AuthController.php';

        $authController = new AuthController();
        $resultado     = $authController->iniciarSesion($usuario, $password);

        if ($resultado['success']) {

            $authController->crearSesion($resultado['usuario']);
            Permiso::limpiarCache();
            redirigir('?page=' . primeraPaginaPermitida());

        } else {

            $error = $resultado['message'];
        }
    }
}

// --- Renderizado ---------------------------------------------------------
$tituloPagina = $ruta['titulo'] ?? 'Sistema';
$archivoVista = APP_ROOT . '/views/' . $ruta['vista'] . '.php';

if (!is_file($archivoVista)) {
    http_response_code(404);
    $archivoVista = APP_ROOT . '/views/errors/404.php';
    $tituloPagina = 'Página no encontrada';
}

$conLayout = sesionActiva();

if ($conLayout) {
    // El header ya incluye navbar y sidebar
    require APP_ROOT . '/views/layouts/header.php';

    // Aviso de la acción anterior (rol creado, eliminado, etc). Se muestra
    // una sola vez y se borra al imprimirlo.
    if (!empty($_SESSION['mensaje'])) {

        $aviso = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);

        $esError = ($aviso['tipo'] ?? '') === 'error';
        ?>
        <div class="lg-flash <?= $esError ? 'lg-flash--error' : 'lg-flash--ok' ?>" role="status">
            <i class="fa <?= $esError ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
            <span><?= htmlspecialchars($aviso['texto'] ?? '') ?></span>
        </div>
        <script>
        (function () {
            var $aviso = $('.lg-flash').first();
            window.setTimeout(function () { $aviso.fadeOut(400); }, 4500);
        })();
        </script>
        <?php
    }
}

require $archivoVista;

if ($conLayout) {
    require APP_ROOT . '/views/layouts/footer.php';
}