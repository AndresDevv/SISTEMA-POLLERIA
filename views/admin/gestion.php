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

$gestion = new Gestion();
$def     = Gestion::recurso($recurso);

if (!$def) {
    echo '<div class="lg-card"><p>Recurso no configurado.</p></div>';
    return;
}

$buscar = $_GET['buscar'] ?? '';
$filas  = $gestion->listar($recurso, $buscar);
$columnas = array_keys($filas ? $filas[0] : array_fill_keys(['id'], 1));
?>

<?php encabezadoPagina($icono ?? 'fa fa-cogs', $titulo, $subtitulo ?? ''); ?>

<!-- Pestañas del módulo -->
<?php if (!empty($recursosExtra)): ?>
    <div class="lg-segmentos mb-3">
        <?php foreach ($recursosExtra as $extra): ?>
            <a href="<?= BASE_URL ?>?page=<?= htmlspecialchars($extra['pagina']) ?>"
               class="lg-segmento<?= $extra['recurso'] === $recurso ? ' is-activo' : '' ?>">
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
            <h2 class="lg-card-title"><?= htmlspecialchars($def['titulo']) ?>s</h2>
            <p class="lg-card-subtitle"><?= count($filas) ?> registro(s)</p>
        </div>

        <form method="GET" action="<?= BASE_URL ?>" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="page" value="<?= htmlspecialchars($paginaActual ?? '') ?>">
            <input type="search" name="buscar" class="lg-input" style="min-width:200px;"
                   value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar...">
            <button type="submit" class="lg-btn lg-btn--ghost"><i class="fa fa-search"></i></button>
        </form>

        <button type="button" class="lg-btn lg-btn--primary js-nuevo"
                data-recurso="<?= htmlspecialchars($recurso) ?>"
                data-campos="<?= htmlspecialchars(json_encode($def['campos'], JSON_UNESCAPED_UNICODE)) ?>">
            <i class="fa fa-plus"></i> Nuevo
        </button>

    </div>

    <?php if (!$filas): ?>

        <div class="lg-empty">
            <i class="fa fa-inbox"></i>
            <p class="mb-0">No hay registros todavía.</p>
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="lg-table">
                <thead>
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <?php if ($col === 'id') { continue; } ?>
                            <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $col))) ?></th>
                        <?php endforeach; ?>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <?php foreach ($columnas as $col): ?>
                                <?php if ($col === 'id') { continue; } ?>
                                <td><?= GestionControllerHelper::texto($col, $fila[$col] ?? '') ?></td>
                            <?php endforeach; ?>

                            <td style="text-align:right;white-space:nowrap;">
                                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-editar"
                                        data-recurso="<?= htmlspecialchars($recurso) ?>"
                                        data-fila="<?= htmlspecialchars(json_encode($fila, JSON_UNESCAPED_UNICODE)) ?>"
                                        data-campos="<?= htmlspecialchars(json_encode($def['campos'], JSON_UNESCAPED_UNICODE)) ?>">
                                    <i class="fa fa-pencil"></i>
                                </button>

                                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost js-borrar"
                                        data-recurso="<?= htmlspecialchars($recurso) ?>"
                                        data-id="<?= (int) ($fila['id'] ?? 0) ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

<?php require APP_ROOT . '/views/partials/modal_formulario.php'; ?>

<?php
/**
 * Formatea un valor de la tabla según su nombre de columna.
 */
class GestionControllerHelper
{
    public static function texto(string $columna, $valor): string
    {
        if ($valor === null || $valor === '') {
            return '<span class="lg-muted">&mdash;</span>';
        }

        if ($columna === 'estado') {
            $texto = ((int) $valor === 1) ? 'Activo' : 'Inactivo';

            return '<span class="lg-pill ' . (((int) $valor === 1) ? 'lg-pill--verde' : 'lg-pill--pizarra') . '">'
                . $texto . '</span>';
        }

        if (in_array($columna, ['precio', 'monto', 'total', 'subtotal'], true)) {
            return '<span class="num">' . soles((float) $valor) . '</span>';
        }

        if (in_array($columna, ['stock', 'stock_minimo', 'cantidad'], true)) {
            return '<span class="num">' . number_format((float) $valor, 2) . '</span>';
        }

        if (str_contains($columna, 'fecha')) {
            $ts = strtotime((string) $valor);

            return $ts ? date('d/m/Y', $ts) : htmlspecialchars((string) $valor);
        }

        if (in_array($columna, ['tipo', 'estado_pago'], true)) {
            return htmlspecialchars(ucfirst((string) $valor));
        }

        return htmlspecialchars((string) $valor);
    }
}
