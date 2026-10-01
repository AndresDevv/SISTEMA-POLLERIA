<?php
/**
 * Vista: finanzas (ingresos, egresos y gastos).
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso = $_GET['tab'] ?? 'egresos';

if (!in_array($recurso, ['egresos', 'ingresos', 'gastos'], true)) {
    $recurso = 'egresos';
}

$paginaActual = 'finanzas';
$icono = 'fa fa-folder-open';
$titulo = 'Finanzas';
$subtitulo = 'Registra los ingresos y egresos del negocio';

$recursosExtra = [
    ['recurso' => 'egresos',  'pagina' => 'finanzas&tab=egresos',  'titulo' => 'Egresos',  'icono' => 'fa fa-arrow-down'],
    ['recurso' => 'ingresos', 'pagina' => 'finanzas&tab=ingresos', 'titulo' => 'Ingresos', 'icono' => 'fa fa-arrow-up'],
    ['recurso' => 'gastos',   'pagina' => 'finanzas&tab=gastos',   'titulo' => 'Gastos',   'icono' => 'fa fa-money']
];

require APP_ROOT . '/views/admin/gestion.php';
