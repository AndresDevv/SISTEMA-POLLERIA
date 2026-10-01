<?php
/**
 * Vista: inventario (productos y categorías).
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = 'productos';
$paginaActual = 'inventario';
$icono = 'fa fa-cube';
$titulo = 'Inventario';
$subtitulo = 'Controla los productos, sus precios y el stock';

$recursosExtra = [
    ['recurso' => 'productos',   'pagina' => 'inventario', 'titulo' => 'Productos',   'icono' => 'fa fa-cube'],
    ['recurso' => 'categorias',  'pagina' => 'categorias', 'titulo' => 'Categorías',  'icono' => 'fa fa-tags']
];

require APP_ROOT . '/views/admin/gestion.php';
