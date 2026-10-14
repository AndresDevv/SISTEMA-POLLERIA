<?php
/**
 * Partial: los botones del catálogo de productos.
 *
 * Vive suelto, y no dentro de modal_pedido.php, por una razón concreta: se
 * pinta en dos lugares distintos.
 *
 *   1. Al abrir el modal de pedidos, junto con el resto del modal.
 *   2. Por la API, mientras el modal está abierto, para que un plato que
 *      alguien marcó como agotado en el Menú del día se vea al instante
 *      sin tener que cerrar el pedido que se está armando.
 *
 * Que el markup esté en un solo archivo y no duplicado es lo que garantiza
 * que las dos pintadas digan exactamente lo mismo.
 *
 * Espera $productosModal (ver modal_pedido.php).
 *
 * @var array $productosModal
 */

foreach ($productosModal as $p):
    $nombreModal    = $p['nombre'] ?? 'Producto';
    $categoriaModal = $p['categoria'] ?? '';
    $precioModal    = (float) ($p['precio'] ?? 0);
    $stockModal     = (int) ($p['stock'] ?? 0);
    $minimoModal    = (int) ($p['minimo'] ?? 0);

    /**
     * Agotado y sin stock son dos cosas distintas, y se muestran distinto.
     *
     * Agotado es una decisión del turno: alguien del servicio lo marcó en el
     * Menú del día porque se terminó, así que sale en rojo y con su nombre.
     *
     * Sin stock es un hecho del inventario. No se marca en rojo ni dice
     * "Agotado", porque el plato no está marcado como tal: simplemente no hay
     * unidades y no se puede pedir.
     *
     * Las dos cosas impiden pedirlo, pero solo la primera se anuncia.
     */
    $marcadoAgotado = (int) ($p['agotado'] ?? 0) === 1;
    $sinStock       = $stockModal <= 0;
    $noSePuede      = $marcadoAgotado || $sinStock;

    $nivelModal = nivelStock($stockModal);
    $claseModal = claseStock($stockModal);

    $estadoModal = match (true) {
        $marcadoAgotado => ['Agotado', 'lg-pill--rojo'],
        $sinStock       => ['Sin stock', 'lg-pill--pizarra'],
        default         => [
            estadosStock()[$nivelModal]['etiqueta'],
            '',
        ],
    };
    ?>
    <button type="button"
            class="modal-producto <?= $claseModal ?><?= $marcadoAgotado ? ' is-agotado' : '' ?><?= $sinStock && !$marcadoAgotado ? ' is-sin-stock' : '' ?>"
            data-nombre="<?= htmlspecialchars(strtolower($nombreModal)) ?>"
            data-categoria="<?= htmlspecialchars($categoriaModal) ?>"
            data-precio="<?= $precioModal ?>"
            data-producto-id="<?= (int) ($p['id'] ?? 0) ?>"
            data-producto="<?= htmlspecialchars($nombreModal) ?>"
            data-stock="<?= (int) $stockModal ?>"
            <?= $noSePuede ? 'disabled' : '' ?>>

        <span class="modal-producto-nombre">
            <?= htmlspecialchars($nombreModal) ?>
        </span>

        <span class="modal-producto-precio <?= $claseModal ?>">
            <?= soles($precioModal) ?>
            &middot;
            <?php if ($estadoModal[1] !== ''): ?>
                <span class="lg-pill <?= $estadoModal[1] ?>">
                    <?= $estadoModal[0] ?>
                </span>
            <?php else: ?>
                <?= htmlspecialchars($estadoModal[0]) ?>
            <?php endif; ?>
        </span>
    </button>

<?php endforeach; ?>
