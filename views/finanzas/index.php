<?php
/**
 * Vista: finanzas (ingresos y compras de insumos).
 *
 * Las compras ocupan el lugar que antes ocupaban los egresos: cada compra
 * de insumo es, en el fondo, una salida de dinero del día, y así es como
 * suma en la tarjeta Caja de Ventas y en el Egresos de hoy del Dashboard.
 * La pestaña de Gastos se quitó por ser una duplicación de la anterior.
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso = $_GET['tab'] ?? 'compras';

if (!in_array($recurso, ['compras', 'ingresos'], true)) {
    $recurso = 'compras';
}

$paginaActual = 'finanzas';
$icono = 'fa fa-folder-open';
$titulo = 'Finanzas';
$subtitulo = 'Registra los ingresos y las compras de insumos del negocio';

$recursosExtra = [
    ['recurso' => 'compras',  'pagina' => 'finanzas&tab=compras',  'titulo' => 'Compras',  'icono' => 'fa fa-shopping-cart'],
    ['recurso' => 'ingresos', 'pagina' => 'finanzas&tab=ingresos', 'titulo' => 'Ingresos', 'icono' => 'fa fa-arrow-up']
];

/**
 * Aviso al borrar una compra.
 *
 * Cada compra ya suma al egreso del día, así que borrarla cambia lo que el
 * negocio gastó. Se dice con palabras claras en vez del "¿está seguro?"
 * de siempre, que no deja ver qué se está perdiendo.
 */
$avisoBorrado = '¿Está seguro que quiere eliminar esta compra?'
    . "\n\n"
    . 'Esta compra ya tiene un egreso agregado a los gastos del día.'
    . "\n"
    . 'Si la elimina, también se quitará ese egreso y las cifras de Caja'
    . "\n"
    . 'y del Dashboard se ajustarán.';

require APP_ROOT . '/views/admin/gestion.php';
