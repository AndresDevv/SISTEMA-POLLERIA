<?php

/**
 * Catálogos de estados del sistema y sus estilos.
 * Centraliza los colores semánticos para que vistas y modelos no se dupliquen.
 */

/**
 * Estados posibles de una mesa.
 *
 * Son los tres del enum de la tabla mesas: 'libre', 'ocupada', 'reservada'.
 * "Pagada" y "Por pagar" se quitaron porque no eran estados de mesa, sino
 * de cobro, y solo confundían la vista del salón.
 */
function estadosMesa(): array
{
    return [
        'libre'     => ['etiqueta' => 'Libre',     'icono' => 'fa fa-check',    'color' => '#10B981'],
        'ocupada'   => ['etiqueta' => 'Ocupada',   'icono' => 'fa fa-times',    'color' => '#EF4444'],
        'reservada' => ['etiqueta' => 'Reservada', 'icono' => 'fa fa-calendar', 'color' => '#F59E0B']
    ];
}

/**
 * Estados posibles de un pedido.
 */
function estadosPedido(): array
{
    return [
        'pendiente'  => ['etiqueta' => 'Pendiente',  'pildora' => 'lg-pill--ambar'],
        'preparando' => ['etiqueta' => 'En cocina',  'pildora' => 'lg-pill--rojo'],
        'listo'      => ['etiqueta' => 'Listo',      'pildora' => 'lg-pill--verde'],
        'servido'    => ['etiqueta' => 'Servido',    'pildora' => 'lg-pill--pizarra'],
        'pagado'     => ['etiqueta' => 'Pagado',     'pildora' => 'lg-pill--verde'],
        'anulado'    => ['etiqueta' => 'Anulado',    'pildora' => 'lg-pill--rojo']
    ];
}

/**
 * Estados de pago de una venta.
 */
function estadosPago(): array
{
    return [
        'pagado'    => ['etiqueta' => 'Pagado',    'pildora' => 'lg-pill--verde'],
        'pendiente' => ['etiqueta' => 'Pendiente', 'pildora' => 'lg-pill--ambar'],
        'anulado'   => ['etiqueta' => 'Anulado',   'pildora' => 'lg-pill--rojo']
    ];
}

/**
 * Niveles de stock de un producto.
 */
function estadosStock(): array
{
    return [
        'alto'     => ['etiqueta' => 'Stock alto',    'pildora' => 'lg-pill--verde'],
        'bajo'     => ['etiqueta' => 'Stock bajo',    'pildora' => 'lg-pill--ambar'],
        'agotado'  => ['etiqueta' => 'Agotado',       'pildora' => 'lg-pill--rojo'],
        'critico'  => ['etiqueta' => 'Stock crítico', 'pildora' => 'lg-pill--rojo']
    ];
}

/**
 * Color hexadecimal de un estado de mesa (para la leyenda).
 */
function colorEstado(string $estado): string
{
    $estados = estadosMesa();

    return $estados[$estado]['color'] ?? '#94A3B8';
}

/**
 * Devuelve la etiqueta de un estado de mesa.
 */
function etiquetaEstadoMesa(string $estado): string
{
    $estados = estadosMesa();

    return $estados[$estado]['etiqueta'] ?? ucfirst($estado);
}

/**
 * Devuelve la clase CSS de la p��ldora para un estado de pedido.
 */
function pildoraPedido(string $estado): string
{
    $estados = estadosPedido();

    return $estados[$estado]['pildora'] ?? 'lg-pill--pizarra';
}

/**
 * Devuelve la clase CSS de la píldora para un estado de pago.
 */
function pildoraPago(string $estado): string
{
    $estados = estadosPago();

    return $estados[$estado]['pildora'] ?? 'lg-pill--pizarra';
}

/**
 * Calcula el nivel de stock a partir de la cantidad y el mínimo configurado.
 */
function nivelStock(int $cantidad, int $minimo): string
{
    if ($cantidad <= 0) {
        return 'agotado';
    }

    if ($cantidad <= $minimo) {
        return 'critico';
    }

    return $cantidad <= ($minimo * 2) ? 'bajo' : 'alto';
}

/**
 * Devuelve la etiqueta y la pildora del nivel de stock.
 */
function infoStock(int $cantidad, int $minimo): array
{
    $nivel   = nivelStock($cantidad, $minimo);
    $estados = estadosStock();

    return $estados[$nivel];
}

/**
 * Formatea un monto en soles: 1234.5 → S/ 1,234.50
 */
function soles(float $monto): string
{
    return 'S/ ' . number_format($monto, 2);
}
