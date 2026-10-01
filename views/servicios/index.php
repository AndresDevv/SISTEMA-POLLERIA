<?php
/**
 * Vista: servicios (pagos de luz, agua, internet, etc.).
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = 'servicios';
$paginaActual = 'servicios';
$icono = 'fa fa-cutlery';
$titulo = 'Servicios';
$subtitulo = 'Controla los servicios básicos y sus vencimientos';

$recursosExtra = [
    ['recurso' => 'servicios', 'pagina' => 'servicios', 'titulo' => 'Servicios', 'icono' => 'fa fa-bolt']
];

require APP_ROOT . '/views/admin/gestion.php';
