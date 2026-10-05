<?php

/**
 * Front controller del sistema POLLERIA.
 * Todas las páginas se sirven por: index.php?page=<nombre>
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/router.php';
require_once __DIR__ . '/config/menu.php';
require_once __DIR__ . '/config/estados.php';

$pagina = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : 'dashboard';
$ruta   = resolverRuta($pagina);

// --- Acción logout -------------------------------------------------------
if (isset($ruta['accion']) && $ruta['accion'] === 'logout') {
    accionLogout();
}

// --- Exportación de reportes (CSV) --------------------------------------
if (($ruta['accion'] ?? '') === 'reporte_exportar') {

    if (!sesionActiva()) {
        redirigir('?page=login');
    }

    if (!esPermitido('reportes')) {
        http_response_code(403);
        exit;
    }

    require __DIR__ . '/controllers/ReporteController.php';

    exit;
}

// --- Peticiones AJAX -----------------------------------------------------
$accionesApi = [
    'pedido_crear'      => ['PedidoController', 'crear'],
    'pedido_estado'     => ['PedidoController', 'cambiarEstado'],
    'pedido_listar'     => ['PedidoController', 'listar'],
    'pedido_actualizar' => ['PedidoController', 'actualizar'],
    'pedido_eliminar'   => ['PedidoController', 'eliminar'],
    'pedido_cobrar'     => ['PedidoController', 'cobrar'],
    'mesa_reservar'     => ['MesaController', 'reservar'],
    'mesa_liberar'      => ['MesaController', 'liberar'],
    'mesa_crear'        => ['MesaController', 'crear'],
    'mesa_eliminar'     => ['MesaController', 'eliminar'],
    'gestion_listar'    => ['GestionController', 'listar'],
    'gestion_opciones'  => ['GestionController', 'opciones'],
    'gestion_crear'     => ['GestionController', 'crear'],
    'gestion_actualizar' => ['GestionController', 'actualizar'],
    'gestion_eliminar'  => ['GestionController', 'eliminar']
];

$accionActual = $ruta['accion'] ?? '';

if (isset($accionesApi[$accionActual])) {

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

// --- Ruta inexistente ----------------------------------------------------
if ($ruta === null) {
    http_response_code(404);
    $ruta = [
        'vista'  => 'errors/404',
        'titulo' => 'Página no encontrada'
    ];
}

// --- Protección de sesión ------------------------------------------------
$esPublica = !empty($ruta['publica']);

if (!$esPublica && !sesionActiva()) {
    redirigir('?page=login');
}

// --- Control de permisos por rol ----------------------------------------
if (!$esPublica && !empty($ruta['permiso']) && !esPermitido($pagina)) {
    http_response_code(403);
    $ruta = [
        'vista'  => 'errors/403',
        'titulo' => 'Sin permisos'
    ];
}

// Si ya hay sesión y pide el login, va directo al dashboard
if ($esPublica && sesionActiva() && $pagina === 'login') {
    redirigir('?page=dashboard');
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
            redirigir('?page=dashboard');

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
