<?php
/**
 * Vista: configuración del negocio.
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = 'configuracion_negocio';
$paginaActual = 'configuracion';
$icono = 'fa fa-cog';
$titulo = 'Configuración';
$subtitulo = 'Datos del negocio y preferencias generales';

$recursosExtra = [
    ['recurso' => 'configuracion_negocio', 'pagina' => 'configuracion', 'titulo' => 'Negocio', 'icono' => 'fa fa-cog']
];

require APP_ROOT . '/views/admin/gestion.php';
