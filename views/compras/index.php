<?php
/**
 * Vista: compras (proveedores).
 * El registro de compras con sus ítems queda para la segunda etapa:
 * la tabla compras exige detalle_compras y control de stock.
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = 'proveedores';
$paginaActual = 'compras';
$icono = 'fa fa-shopping-cart';
$titulo = 'Compras';
$subtitulo = 'Gestiona los proveedores de la pollería';

$recursosExtra = [
    ['recurso' => 'proveedores', 'pagina' => 'compras', 'titulo' => 'Proveedores', 'icono' => 'fa fa-truck']
];

require APP_ROOT . '/views/admin/gestion.php';
