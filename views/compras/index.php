<?php
/**
 * Vista: compras.
 */

$compras = [
    ['codigo' => 'C-0012', 'proveedor' => 'Granja Avícola del Norte', 'fecha' => '29/09/2026', 'items' => 12, 'total' => 0.0, 'estado' => 'pagado'],
    ['codigo' => 'C-0011', 'proveedor' => 'Distribuidora Andina',     'fecha' => '28/09/2026', 'items' => 8,  'total' => 0.0, 'estado' => 'pendiente'],
    ['codigo' => 'C-0010', 'proveedor' => 'Granja Avícola del Norte', 'fecha' => '26/09/2026', 'items' => 20, 'total' => 0.0, 'estado' => 'pagado'],
    ['codigo' => 'C-0009', 'proveedor' => 'Lácteos La Pradera',       'fecha' => '24/09/2026', 'items' => 6,  'total' => 0.0, 'estado' => 'anulado']
];
?>

<?php
encabezadoPagina(
    'fa fa-shopping-cart',
    'Compras',
    'Registra y revisa las compras a proveedores',
    '<button type="button" class="lg-btn lg-btn--primary"><i class="fa fa-plus"></i> Nueva compra</button>'
);
?>

<div class="lg-card">
    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-list"></i></span>
        <div>
            <h2 class="lg-card-title">Historial de compras</h2>
            <p class="lg-card-subtitle">&Uacute;ltimas compras registradas</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="lg-table">
            <thead>
                <tr>
                    <th>C&oacute;digo</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compras as $c): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($c['codigo']) ?></td>
                        <td><?= htmlspecialchars($c['proveedor']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($c['fecha']) ?></td>
                        <td class="num"><?= (int) $c['items'] ?></td>
                        <td class="num"><?= soles((float) $c['total']) ?></td>
                        <td>
                            <span class="lg-pill <?= pildoraPago($c['estado']) ?>">
                                <?= htmlspecialchars(estadosPago()[$c['estado']]['etiqueta']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
