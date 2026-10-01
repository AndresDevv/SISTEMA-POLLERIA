<?php
/**
 * Vista: categorías de productos.
 */

require_once APP_ROOT . '/models/Gestion.php';

$recurso  = 'categorias';
$paginaActual = 'categorias';
$icono = 'fa fa-tags';
$titulo = 'Categorías';
$subtitulo = 'Agrupa los productos del inventario';

$recursosExtra = [
    ['recurso' => 'productos',  'pagina' => 'inventario', 'titulo' => 'Productos',  'icono' => 'fa fa-cube'],
    ['recurso' => 'categorias', 'pagina' => 'categorias', 'titulo' => 'Categorías', 'icono' => 'fa fa-tags']
];

require APP_ROOT . '/views/admin/gestion.php';
