<?php

/**
 * ConfiguraciÃ³n general de la aplicaciÃ³n.
 */

// RaÃ­z fÃ­sica del proyecto
define('APP_ROOT', dirname(__DIR__));

// RaÃ­z web del proyecto (ej: /POLLERIA)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
define('BASE_URL', rtrim($scriptDir, '/') . '/');

// Datos de la aplicaciÃ³n
define('APP_NOMBRE', 'LOS GOMEZ');
define('APP_SIGLA', 'LOS GOMEZ');

// VersiÃ³n de los assets (rompe la cachÃ© del navegador al cambiarla)
define('APP_VERSION', '1.9.0');

// Rutas de assets
define('ASSETS_URL', BASE_URL . 'views/assets/');
define('ADMINLTE_URL', BASE_URL . 'views/dist/');
define('BOWER_URL', BASE_URL . 'views/bower_components/');
define('ADMINLTE_SKIN', 'skin-black-light');

// Zona horaria por defecto
date_default_timezone_set('America/Lima');
