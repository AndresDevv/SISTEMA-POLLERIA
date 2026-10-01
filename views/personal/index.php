<?php
/**
 * Vista: personal (usuarios y colaboradores).
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = $_GET['tab'] ?? 'usuarios';

if (!in_array($recurso, ['usuarios', 'empleados'], true)) {
    $recurso = 'usuarios';
}

$paginaActual = 'personal';
$icono = 'fa fa-users';
$titulo = 'Personal';
$subtitulo = 'Administra a los colaboradores y sus accesos';

$recursosExtra = [
    ['recurso' => 'usuarios',  'pagina' => 'personal&tab=usuarios',  'titulo' => 'Usuarios',      'icono' => 'fa fa-user'],
    ['recurso' => 'empleados', 'pagina' => 'personal&tab=empleados', 'titulo' => 'Colaboradores', 'icono' => 'fa fa-users']
];

require APP_ROOT . '/views/admin/gestion.php';
