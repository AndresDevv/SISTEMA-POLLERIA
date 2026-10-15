<?php

/**
 * Partial: los botones del catálogo de productos.
 *
 * Se pinta en dos momentos: al abrir el modal de pedidos, y por la API
 * mientras el modal está abierto, para que un plato marcado como agotado se
 * vea al instante sin cerrar el pedido que se está armando.
 *
 * Que esté en un solo archivo es lo que hace que las dos pintadas sean
 * exactamente iguales.
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

    /* Agotado y sin stock son dos cosas distintas y se muestran distinto.

       Agotado es una decisión del turno: alguien del servicio lo marcó en el
       Menú del día porque se terminó, y sale en rojo.

       Sin stock es un hecho del inventario: no hay unidades y no se puede
       pedir, pero no está marcado como agotado y no se anuncia en rojo.

       Las dos impiden pedirlo, pero solo la primera se anuncia. */
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
