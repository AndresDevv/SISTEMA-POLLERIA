<?php

/**
 * Configuración general de la aplicación.
 */

// Raíz física del proyecto
define('APP_ROOT', dirname(__DIR__));

// Raíz web del proyecto (ej: /POLLERIA)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
define('BASE_URL', rtrim($scriptDir, '/') . '/');

// Datos de la aplicación
define('APP_NOMBRE', "Makako's");

/**
 * El logo va como imagen en el menú lateral, no como texto.
 *
 * Este valor queda solo para el <title> de la pestaña del navegador y para
 * textos donde una imagen no serve (correos, exportaciones).
 */
define('APP_SIGLA', "Makako's");

// Versión de los assets (rompe la caché del navegador al cambiarla)
define('APP_VERSION', '7.0.4');

// Rutas de assets
define('ASSETS_URL', BASE_URL . 'views/assets/');
define('IMAGENES_URL', BASE_URL . 'assets/img/');
define('ADMINLTE_URL', BASE_URL . 'views/dist/');
define('BOWER_URL', BASE_URL . 'views/bower_components/');
define('ADMINLTE_SKIN', 'skin-black-light');

// Zona horaria por defecto
date_default_timezone_set('America/Lima');
