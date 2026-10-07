<?php
/**
 * Vista: servicios (pagos de luz, agua, internet, etc.).
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = 'servicios';
$paginaActual = 'servicios';
$icono = 'fa fa-bolt';
$titulo = 'Servicios';
$subtitulo = 'Controla los servicios básicos y sus vencimientos';

// El módulo solo tiene un recurso, así que no hace falta tira de pestañas
$recursosExtra = [];

require APP_ROOT . '/views/admin/gestion.php';
