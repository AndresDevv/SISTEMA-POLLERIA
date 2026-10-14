<?php

/**
 * Paginación de tablas.
 *
 * Todas las tablas del sistema pasan por aquí para que ninguna se alargue
 * hasta el infinito. Se hace sobre el array ya arma la vista (no sobre SQL),
 * porque así el mismo helper sirve para el CRUD genérico y para las tablas
 * escritas a mano, y ninguna tiene que hacer paginación por su cuenta.
 */

/**
 * Número de página pedido en la URL.
 */
function paginaActual(int $porPagina = 25): int
{
    $pagina = (int) ($_GET['p'] ?? 1);

    return max(1, $pagina);
}

/**
 * Recorta un array de filas a la página pedida.
 *
 * @return array ['filas' => [], 'pagina' => int, 'totalPaginas' => int,
 *               'total' => int, 'desde' => int, 'hasta' => int]
 */
function paginar(array $filas, int $porPagina = 25): array
{
    $porPagina = max(1, $porPagina);
    $total     = count($filas);

    // Sin filas no hay nada que paginar
    if ($total === 0) {
        return [
            'filas'        => [],
            'pagina'       => 1,
            'totalPaginas' => 1,
            'total'        => 0,
            'desde'        => 0,
            'hasta'        => 0,
        ];
    }

    $totalPaginas = (int) ceil($total / $porPagina);

    // Si piden una página que ya no existe (borraron filas, filtraron, etc),
    // se cae en la última en vez de mostrar una tabla vacía sin explicación.
    $pagina = min(paginaActual($porPagina), $totalPaginas);
    $pagina = max(1, $pagina);

    $desde = ($pagina - 1) * $porPagina;

    return [
        'filas'        => array_slice($filas, $desde, $porPagina),
        'pagina'       => $pagina,
        'totalPaginas' => $totalPaginas,
        'total'        => $total,
        'desde'        => $desde + 1,
        'hasta'        => min($desde + $porPagina, $total),
    ];
}

/**
 * Imprime los botones de página, conservando el resto de la URL.
 *
 * La página se cambia con "?p=", así que cualquier otro parámetro de la URL
 * (pestaña, panel, búsqueda) se mantiene y no se pierde al paginar.
 *
 * @param array  $meta      resultado de paginar()
 * @param string $resumen   texto a la izquierda, p. ej. "Mostrando 1-25 de 63"
 */
function barraPaginacion(array $meta, string $resumen = ''): void
{
    // Con una sola página no hay nada que paginar
    if ($meta['totalPaginas'] <= 1) {
        return;
    }

    $pagina    = $meta['pagina'];
    $totalPag  = $meta['totalPaginas'];

    /** Enlace a una página, arrastrando los demás parámetros. */
    $enlace = static function (int $destino): string {
        $params = $_GET;
        $params['p'] = $destino;

        return '?' . http_build_query($params);
    };

    /** Botón de una página (o flecha). */
    $boton = static function (string $texto, int $destino, bool $activo = false, bool $inactivo = false) use ($enlace): string {
        $clase = 'lg-pag-boton';

        if ($activo) {
            $clase .= ' is-activo';
        }

        if ($inactivo) {
            return '<span class="' . $clase . ' is-inactivo">' . $texto . '</span>';
        }

        return '<a href="' . htmlspecialchars($enlace($destino)) . '" class="' . $clase . '">' . $texto . '</a>';
    };

    /**
     * Ventana de páginas alrededor de la actual, para no generar 40 botones.
     *
     * Se excluyen la primera y la última porque se sacan aparte, siempre, una
     * vez cada una. Si la ventana las incluyera también, se verían repetidas
     * ("< 1 1 2 2 >" al cambiar de página) y el contador de páginas mentiría.
     */
    $desde = max(2, $pagina - 2);
    $hasta = min($totalPag - 1, $pagina + 2);
    ?>
    <div class="lg-paginacion">
        <span class="lg-muted lg-paginacion-resumen">
            <?php if ($resumen !== ''): ?>
                <?= htmlspecialchars($resumen) ?>
            <?php else: ?>
                <?= $meta['desde'] ?>&ndash;<?= $meta['hasta'] ?> de <?= $meta['total'] ?>
            <?php endif; ?>
        </span>

        <nav class="lg-paginacion-botones">
            <?= $boton('<i class="fa fa-angle-left"></i>', $pagina - 1, false, $pagina <= 1) ?>
            <?= $boton('1', 1, $pagina === 1) ?>

            <?php if ($desde > 2): ?>
                <span class="lg-pag-puntos">&hellip;</span>
            <?php endif; ?>

            <?php for ($i = $desde; $i <= $hasta; $i++): ?>
                <?= $boton((string) $i, $i, $i === $pagina) ?>
            <?php endfor; ?>

            <?php if ($hasta < $totalPag - 1): ?>
                <span class="lg-pag-puntos">&hellip;</span>
            <?php endif; ?>

            <?= $boton((string) $totalPag, $totalPag, $pagina === $totalPag) ?>
            <?= $boton('<i class="fa fa-angle-right"></i>', $pagina + 1, false, $pagina >= $totalPag) ?>
        </nav>
    </div>
    <?php
}
