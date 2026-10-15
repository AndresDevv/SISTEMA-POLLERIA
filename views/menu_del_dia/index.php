<?php

/**
 * Vista: Menú del día.
 *
 * Es la carta que se ofrece hoy, agrupada por categoría, con lo que queda
 * de cada plato y un botón para marcarlo como agotado.
 *
 * Sale de la misma tabla de productos que el Inventario: no hay una carta
 * aparte que pueda quedar desincronizada del stock real.
 */

require_once __DIR__ . '/../../models/MenuDelDia.php';

$menuModel = new MenuDelDia();
$carta     = $menuModel->carta();

/**
 * Quién puede marcar un plato como agotado.
 *
 * El mesero lo necesita todo el turno (se acaba el pollo a media tarde) y
 * el administrador también. La cocina solo consulta: necesita saber qué hay
 * antes de preparar, pero no decide qué sale de la carta.
 */
$puedeMarcar = esPermitido('menu_dia_editar');

/**
 * Resumen de cabecera.
 *
 * Cuenta los platos agotados y los que ya no se pueden pedir por falta de
 * stock, que no es lo mismo: agotado es una decisión del turno, y stock 0
 * es un hecho del inventario.
 */
$agotados = 0;
$sinStock = 0;
$total    = 0;

foreach ($carta as $grupo) {
    foreach ($grupo['productos'] as $plato) {
        $total++;

        if ($plato['agotado']) {
            $agotados++;
        } elseif ($plato['stock'] <= 0) {
            $sinStock++;
        }
    }
}
?>

<?php encabezadoPagina(
    'fa fa-book',
    'Menú del día',
    'Los platos y bebidas de hoy, con lo que queda de cada uno.'
); ?>

<div class="lg-stats mb-3">

    <div class="lg-stat">
        <div class="lg-stat-icon"><i class="fa fa-book"></i></div>
        <div class="lg-stat-label">En la carta</div>
        <div class="lg-stat-value is-dark"><?= $total ?></div>
    </div>

    <div class="lg-stat">
        <div class="lg-stat-icon"><i class="fa fa-ban"></i></div>
        <div class="lg-stat-label">Agotados</div>
        <div class="lg-stat-value <?= $agotados > 0 ? 'is-red' : 'is-dark' ?>"><?= $agotados ?></div>
    </div>

    <div class="lg-stat">
        <div class="lg-stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
        <div class="lg-stat-label">Sin existencias</div>
        <div class="lg-stat-value <?= $sinStock > 0 ? 'is-red' : 'is-dark' ?>"><?= $sinStock ?></div>
    </div>

    <div class="lg-stat">
        <div class="lg-stat-icon"><i class="fa fa-check-circle"></i></div>
        <div class="lg-stat-label">Disponibles</div>
        <div class="lg-stat-value is-green"><?= $total - $agotados - $sinStock ?></div>
    </div>

</div>

<?php if (!$puedeMarcar): ?>
    <div class="lg-info mb-3">
        <i class="fa fa-eye"></i>
        <span>Estás viendo el menú. Solo el mesero y el administrador pueden marcar un plato como agotado.</span>
    </div>
<?php endif; ?>

<?php if (!$carta): ?>

    <div class="lg-card">
        <div class="lg-empty">
            <i class="fa fa-book"></i>
            <p class="mb-0">Todavía no hay platos en la carta.</p>
        </div>
    </div>

<?php else: ?>

    <?php foreach ($carta as $grupo): ?>

        <div class="lg-card mb-3">

            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="fa fa-tag"></i></span>
                <div>
                    <h2 class="lg-card-title"><?= htmlspecialchars($grupo['categoria']) ?></h2>
                    <p class="lg-card-subtitle"><?= count($grupo['productos']) ?> plato(s)</p>
                </div>
            </div>

            <div class="lg-menu">
                <?php foreach ($grupo['productos'] as $plato): ?>
                    <?php
                    /**
                     * Un plato se puede pedir si NO está agotado y además
                     * queda stock. Las dos cosas cuentan: el mesero puede
                     * haber marcado algo que se terminó, y el inventario
                     * puede haberse quedado en cero sin avisar.
                     */
                    $disponible = !$plato['agotado'] && $plato['stock'] > 0;

                    $estado = match (true) {
                        $plato['agotado']     => ['rojo',     'fa-ban',                  'Agotado'],
                        $plato['stock'] <= 0  => ['pizarra',  'fa-times-circle',         'Sin stock'],
                        $plato['por_acabar']  => ['ambar',    'fa-exclamation-triangle', 'Por acabar'],
                        default               => ['verde',    'fa-check-circle',         'Disponible'],
                    };
                    ?>

                    <div class="lg-menu-item<?= $disponible ? '' : ' lg-menu-item--fuera' ?>">

                        <div class="lg-menu-datos">
                            <strong class="lg-menu-nombre">
                                <?= htmlspecialchars($plato['nombre']) ?>
                            </strong>

                            <?php if ($plato['descripcion'] !== ''): ?>
                                <span class="lg-menu-descripcion">
                                    <?= htmlspecialchars($plato['descripcion']) ?>
                                </span>
                            <?php endif; ?>

                            <span class="lg-menu-meta">
                                <span class="lg-menu-precio"><?= soles($plato['precio']) ?></span>
                                <span class="lg-pill lg-pill--<?= $estado[0] ?>">
                                    <i class="fa <?= $estado[1] ?>"></i>
                                    <?= $estado[2] ?>
                                </span>
                                <?php if (!$plato['agotado']): ?>
                                    <span class="lg-menu-stock">
                                        <?= $plato['stock'] ?> en stock
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($puedeMarcar): ?>
                            <button type="button"
                                    class="lg-btn lg-btn--sm js-marcar-agotado
                                        <?= $plato['agotado']
                                            ? 'lg-btn--ghost'
                                            : 'lg-btn--rojo' ?>"
                                    data-id="<?= (int) $plato['id'] ?>"
                                    data-agotado="<?= $plato['agotado'] ? '1' : '0' ?>"
                                    aria-pressed="<?= $plato['agotado'] ? 'true' : 'false' ?>">
                                <i class="fa <?= $plato['agotado'] ? 'fa-check' : 'fa-ban' ?>"></i>
                                <?= $plato['agotado'] ? 'Volver a ofrecer' : 'Marcar agotado' ?>
                            </button>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<?php if ($puedeMarcar): ?>
    <script>
    /**
     * Marcar un plato como agotado.
     *
     * Se manda a la API y, con eso, el motor de tiempo real de app.js se
     * encarga de refrescar: la tarjeta cambia sola en el equipo del mesero, en
     * el del administrador y en el de la cocina, sin que nadie recargue. Por
     * eso el botón no se repinta aquí, para no tener dos verdades distintas
     * sobre lo que el servidor ya respondió.
     *
     * Solo se manda este script a quien puede marcar algo: en la cocina y en
     * cualquier rol de consulta no hay botones y el manejador no encontraría
     * a nadie al que escuchar.
     */
    window.addEventListener('load', function () {

        $(document).on('click', '.js-marcar-agotado', function () {

            var $boton = $(this);

            if ($boton.prop('disabled')) {
                return;
            }

            // Se manda lo contrario de como está ahora: el botón alterna.
            var agotado = $boton.data('agotado') !== 1;
            var nombre  = $boton.closest('.lg-menu-item').find('.lg-menu-nombre').text().trim();

            $boton.prop('disabled', true);

            api('api/menu/agotado', {
                id: $boton.data('id'),
                agotado: agotado
            })
            .done(function (r) {

                /* Se refresca en el acto, sin esperar al sondeo.

                   El sondeo compara la base cada 5 segundos, así que si se
                   le deja el cambio se tardarían hasta 5 s en verse y parecía
                   que el clic no había hecho nada. Aquí el cambio lo hizo
                   esta misma pantalla, así que se pide la página al servidor
                   y se repinta con lo que él responda: manda el servidor y no
                   un pintado a mano que podría quedar desincronizado.

                   Los otros equipos se enteran por el sondeo, que para eso
                   está. */
                if (window.refrescarContenido) {
                    window.refrescarContenido();
                }

                if (r && r.message) {
                    Alerta.info(r.message);
                }
            })
            .fail(function (xhr) {
                Alerta.error(mensajeDe(xhr, 'No se pudo cambiar el estado de ' + nombre + '.'));
            })
            .always(function () {
                $boton.prop('disabled', false);
            });
        });
    });
    </script>
<?php endif; ?>
