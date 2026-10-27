<?php
/**
 * Vista genérica de administración con CRUD.
 *
 * Variables esperadas:
 *   $recurso   nombre del recurso (ver Gestion::recursos())
 *   $titulo    título de la página
 *   $subtitulo descripción
 *   $recursosExtra  otros recursos del mismo módulo, para las pestañas
 */

$recurso = $recurso ?? '';

$gestion = new Gestion();
$def     = Gestion::recurso($recurso);

if (!$def) {
    echo '<div class="lg-card"><p>Recurso no configurado.</p></div>';
    return;
}

$buscar = $_GET['buscar'] ?? '';
$todas  = $gestion->listar($recurso, $buscar);

// Los encabezados se sacan del conjunto completo, no de la página actual:
// si "?p=2" no trae filas, la tabla saldría sin columnas.
$columnas = array_keys($todas ? $todas[0] : array_fill_keys(['id'], 1));

// Paginación: 20 filas por tabla, para que la lista no se alargue hasta abajo
$paginacion = paginar($todas, 20);
$filas = $paginacion['filas'];

// La pestaña activa la marca el módulo: el nombre de la pestaña puede no
// coincidir con el del recurso (Personal > Trabajadores usa "empleados").
$pestanaActual = $pestanaActual ?? $recurso;

/**
 * Título de cada columna.
 *
 * Se usa la etiqueta real del campo ("Fecha de ingreso", "DNI", "Teléfono")
 * en vez del nombre crudo de la columna, que salía como "Fecha Ingreso" o
 * "Dni". Las columnas que no vienen de un campo caen en un nombre limpio.
 */
$etiquetasColumnas = [];

foreach ($def['campos'] as $campo => $regla) {
    $etiquetasColumnas[$campo] = $regla['etiqueta'] ?? ucfirst(str_replace('_', ' ', $campo));

    /**
     * La columna del encabezado es el dato que hay ahora, no como se escribe.
     *
     * El stock se rotula "Stock inicial" porque asi se pide al crear un
     * producto, pero en la tabla la columna muestra cuanto queda en el
     * almacen. Si no se separaran las dos cosas, el encabezado enganyaria:
     * "Stock inicial" invites a escribir ahi lo que hay, y ahi no se puede.
     */
    if (!empty($regla['soloNuevo'])) {
        $etiquetasColumnas[$campo] = ucfirst(str_replace('_', ' ', $campo));
    }
}

// 'relacion' es el nombre técnico del campo que alimenta una columna de
// relación; se rotula con la etiqueta de ese campo.
$etiquetaRelacion = $etiquetasColumnas['relacion'] ?? 'Relación';

foreach ($def['campos'] as $campo => $regla) {
    if (($regla['tipo'] ?? '') === 'relacion') {
        $etiquetaRelacion = $regla['etiqueta'];
        break;
    }
}
?>

<?php encabezadoPagina($icono ?? 'fa fa-cogs', $titulo, $subtitulo ?? ''); ?>

<!-- Pestañas del módulo -->
<?php if (!empty($recursosExtra)): ?>
    <div class="lg-segmentos mb-3">
        <?php foreach ($recursosExtra as $extra): ?>
            <a href="<?= BASE_URL ?>?page=<?= htmlspecialchars($extra['pagina']) ?>"
            class="lg-segmento<?= ($extra['clave'] ?? $extra['recurso']) === $pestanaActual ? ' is-activo' : '' ?>">
                <i class="fa <?= htmlspecialchars($extra['icono']) ?>"></i> <?= htmlspecialchars($extra['titulo']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="lg-card">

    <!-- Cabecera + búsqueda -->
    <div class="lg-card-head flex-wrap">

        <span class="lg-card-icon"><i class="fa fa-list"></i></span>

        <div class="flex-grow-1">
            <?php
    // En español no basta con añadir una "s": "Trabajador" + "s" salía
    // como "Trabajadors". Cada recurso declara su plural cuando hace falta.
    $tituloPlural = $def['tituloPlural'] ?? ($def['titulo'] . 's');
    ?>
                <h2 class="lg-card-title"><?= htmlspecialchars($tituloPlural) ?></h2>
            <p class="lg-card-subtitle"><?= count($filas) ?> registro(s)</p>
        </div>

        <?php
        /**
         * Buscador con estilo propio y filtrado sin recargar.
         *
         * El formulario lleva la dirección exacta de la página en
         * "data-buscar-url" (con su pestaña cuando la tiene), para que el
         * JavaScript sepa a dónde pedir la lista filtrada. Se usa en los
         * módulos donde la búsqueda es parte del día a día; los demás
         * siguen con el estilo clásico.
         */
        $busquedaViva = in_array($recurso, [
            'empleados',                 // Personal
            'productos', 'categorias',   // Inventario
            'compras', 'ingresos',       // Finanzas
            'servicios',                 // Servicios
            'usuarios', 'configuracion_negocio', // Configuración
        ], true);

        $urlBusqueda = url($_GET['page'] ?? '');

        if (!empty($_GET['tab'])) {
            $urlBusqueda .= '&tab=' . urlencode($_GET['tab']);
        }
        ?>

        <?php if ($busquedaViva): ?>
            <form method="GET" action="<?= BASE_URL ?>" class="input-group"
                data-buscar-url="<?= htmlspecialchars($urlBusqueda) ?>">
                <input type="search" name="buscar" class="lg-input"
                    value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar..." aria-label="Buscar">
                <button type="submit" class="input-group-btn" aria-label="Buscar">
                    <i class="fa fa-search"></i>
                </button>
            </form>
        <?php else: ?>
            <form method="GET" action="<?= BASE_URL ?>" class="d-flex gap-2 flex-wrap">
                <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? '') ?>">
                <input type="search" name="buscar" class="lg-input" style="min-width:200px;"
                    value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar...">
                <button type="submit" class="lg-btn lg-btn--ghost"><i class="fa fa-search"></i></button>
            </form>
        <?php endif; ?>

        <?php if (Gestion::puede($recurso, 'editar')): ?>
            <button type="button" class="lg-btn lg-btn--primary js-nuevo"
                    data-recurso="<?= htmlspecialchars($recurso) ?>"
                    data-campos="<?= htmlspecialchars(json_encode($def['campos'] + (isset($def['calculoTotal']) ? ['_calculo' => $def['calculoTotal']] : []), JSON_UNESCAPED_UNICODE)) ?>">
                <i class="fa fa-plus"></i> Nuevo
            </button>
        <?php endif; ?>

    </div>

    <?php if (!$filas): ?>

        <div class="lg-empty">
            <i class="fa fa-inbox"></i>
            <p class="mb-0">No hay registros todavía.</p>
        </div>

    <?php else: ?>

        <?php $puedeEditar = Gestion::puede($recurso, 'editar'); ?>

        <div class="table-responsive">
            <table class="lg-table">
                <thead>
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <?php if ($col === 'id') { continue; } ?>
                            <th><?= htmlspecialchars(
                                $col === 'relacion'
                                    ? $etiquetaRelacion
                                    : ($etiquetasColumnas[$col] ?? ucfirst(str_replace('_', ' ', $col)))
                            ) ?></th>
                        <?php endforeach; ?>
                        <?php if ($puedeEditar): ?>
                            <th style="text-align:right;">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <?php foreach ($columnas as $col): ?>
                                <?php if ($col === 'id') { continue; } ?>
                                <td><?= GestionControllerHelper::texto($col, $fila[$col] ?? '', $fila, $def['campos']) ?></td>
                            <?php endforeach; ?>

                            <?php if (!$puedeEditar) { continue; } ?>

                            <td style="text-align:right;white-space:nowrap;">
                                <?php if ($recurso === 'productos'): ?>
                                    <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-ajustar-stock"
                                            data-id="<?= (int) ($fila['id'] ?? 0) ?>"
                                            data-producto="<?= htmlspecialchars($fila['nombre'] ?? '') ?>"
                                            data-stock="<?= (float) ($fila['stock'] ?? 0) ?>">
                                        <i class="fa fa-plus-circle"></i> Agregar
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-editar"
                                        data-recurso="<?= htmlspecialchars($recurso) ?>"
                                        data-fila="<?= htmlspecialchars(json_encode($fila, JSON_UNESCAPED_UNICODE)) ?>"
                                        data-campos="<?= htmlspecialchars(json_encode($def['campos'] + (isset($def['calculoTotal']) ? ['_calculo' => $def['calculoTotal']] : []), JSON_UNESCAPED_UNICODE)) ?>">
                                    <i class="fa fa-pencil"></i>
                                </button>

                                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-borrar"
                                        data-recurso="<?= htmlspecialchars($recurso) ?>"
                                        data-id="<?= (int) ($fila['id'] ?? 0) ?>"
                                        data-aviso="<?= htmlspecialchars($avisoBorrado ?? '¿Eliminar este registro?') ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php barraPaginacion($paginacion); ?>

    <?php endif; ?>

</div>

<?php require APP_ROOT . '/views/partials/modal_formulario.php'; ?>

<?php if ($recurso === 'productos'): ?>
    <?php require APP_ROOT . '/views/partials/modal_stock.php'; ?>
<?php endif; ?>

<?php
/**
 * Formatea un valor de la tabla según su nombre de columna.
 */
class GestionControllerHelper
{
    /** Tipos de campo que se muestran como texto normal, sin formato */
    private const COLUMNAS_PLAINAS = ['dni', 'telefono', 'usuario', 'documento', 'direccion'];

    /**
     * @param array  $fila   la fila completa, necesaria para comparar el stock
     *                       con su mínimo y decidir el color
     * @param array  $reglas definición de los campos, para saber si un valor
     *                       viene de un enum y debe verse en mayúscula
     */
    public static function texto(string $columna, $valor, array $fila = [], array $reglas = []): string
    {
        if ($valor === null || $valor === '') {
            return '<span class="lg-muted">&mdash;</span>';
        }

        if ($columna === 'estado') {
            $texto = ((int) $valor === 1) ? 'Activo' : 'Inactivo';

            return '<span class="lg-pill ' . (((int) $valor === 1) ? 'lg-pill--verde' : 'lg-pill--pizarra') . '">'
                . $texto . '</span>';
        }

        // Un enum se guarda en minúsculas pero se lee en mayúscula:
        // "mesero" -> "Mesero"
        if (($reglas[$columna]['tipo'] ?? '') === 'enum') {
            return htmlspecialchars(ucfirst((string) $valor));
        }

        if (in_array($columna, ['precio', 'precio_unitario', 'monto', 'total', 'subtotal'], true)) {
            return '<span class="num">' . soles((float) $valor) . '</span>';
        }

        // El stock se pinta de verde si hay de sobra, amarillo si va justo
        // y rojo si está por acabarse. Las unidades son enteras.
        if ($columna === 'stock') {
            return '<span class="num lg-stock-tag '
                . claseStock((int) $valor) . '">'
                . unidades($valor) . '</span>';
        }

        if (in_array($columna, ['stock_minimo', 'cantidad'], true)) {

            /**
             * Las cantidades pueden ser enteras o con decimales.
             *
             * El stock de platos es siempre entero, pero las compras de
             * insumos no: 2.5 kilos de arroz o 1.5 litros de aceite son
             * normales. Si se usara el formato de enteros, "2.50" se veria
             * como "3" y el admin creeria que compro tres kilos. Se quitan
             * los ceros de mas, pero el decimal se respeta.
             */
            $numero = (float) $valor;

            $texto = fmod($numero, 1.0) === 0.0
                ? number_format($numero, 0, '.', ',')
                : rtrim(rtrim(number_format($numero, 2, '.', ','), '0'), '.');

            return '<span class="num">' . $texto . '</span>';
        }

        if (str_contains($columna, 'fecha')) {
            $ts = strtotime((string) $valor);

            return $ts ? date('d/m/Y', $ts) : htmlspecialchars((string) $valor);
        }

        if (in_array($columna, ['tipo', 'estado_pago'], true)) {
            return htmlspecialchars(ucfirst((string) $valor));
        }

        // DNI, teléfono y documento: solo dígitos, sin nada de formato
        if (in_array($columna, self::COLUMNAS_PLAINAS, true)) {
            return htmlspecialchars((string) $valor);
        }

        return htmlspecialchars((string) $valor);
    }
}
