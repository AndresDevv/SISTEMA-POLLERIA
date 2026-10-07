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
    'pedido_cobrar'      => ['PedidoController', 'cobrar'],
    'mesa_reservar'      => ['MesaController', 'reservar'],
    'mesa_liberar'       => ['MesaController', 'liberar'],
    'mesa_crear'         => ['MesaController', 'crear'],
    'mesa_eliminar'      => ['MesaController', 'eliminar'],
    'gestion_listar'     => ['GestionController', 'listar'],
    'gestion_opciones'   => ['GestionController', 'opciones'],
    'gestion_crear'      => ['GestionController', 'crear'],
    'gestion_actualizar' => ['GestionController', 'actualizar'],
    'gestion_eliminar'   => ['GestionController', 'eliminar'],
    'compra_registrar'   => ['OperacionController', 'compraRegistrar'],
    'compra_anular'     => ['OperacionController', 'compraAnular'],
    'compra_detalle'   => ['OperacionController', 'compraDetalle'],
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

// --- Guardado de roles y permisos (Configuración > Roles) ---------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $pagina === 'configuracion'
    && isset($_POST['guardar_rol'])) {

    require_once __DIR__ . '/config/database.php';

    $rolId   = (int) ($_POST['rol_id'] ?? 0);
    $estado  = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;
    $permisos = $_POST['permisos'] ?? [];

    if ($rolId > 0) {

        $db = conexionDB();

        $stmt = $db->prepare("UPDATE roles SET estado = :estado WHERE id = :id");
        $stmt->execute([':estado' => $estado, ':id' => $rolId]);

        $db->prepare("DELETE FROM rol_permiso WHERE rol_id = :rol")->execute([':rol' => $rolId]);

        if (is_array($permisos) && $permisos) {

            $stmt = $db->prepare(
                "INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (:rol, :permiso)"
            );

            foreach ($permisos as $permisoId) {
                $stmt->execute([':rol' => $rolId, ':permiso' => (int) $permisoId]);
            }
        }

        // Si cambiaron los permisos, se limpia la cache de la sesion
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
}

require $archivoVista;

if ($conLayout) {
    require APP_ROOT . '/views/layouts/footer.php';
}