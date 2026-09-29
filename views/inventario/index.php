<?php
/**
 * Vista: inventario.
 *
 * Prioridad alta: lo usan el cocinero y el personal de barra para
 * consultar stock y avisar de faltantes.
 */

require_once __DIR__ . '/../../models/Producto.php';

$productoModel = new Producto();

// Los filtros llegan por GET para que la URL sea compartible
$productos = $productoModel->listar([
    'nombre'    => $_GET['nombre'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
    'stock'     => $_GET['stock'] ?? ''
]);

$categorias = $productoModel->categorias();

$resumen = [
    'total_productos' => count($productos),
    'stock_bajo'      => $productoModel->stockBajo(),
    'categorias'      => count($categorias)
];
?>

<?php
encabezadoPagina(
    'fa fa-cube',
    'Inventario',
    'Controla el stock de productos e insumos de la pollería',
    '<a href="#" class="lg-btn lg-btn--primary"><i class="fa fa-plus"></i> Nuevo producto</a>'
);
?>

<!-- Resumen -->
<div class="lg-grid-2 mb-3">

    <div class="lg-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="lg-stat-label mb-1">Productos registrados</p>
                <div class="lg-stat-value is-dark"><?= (int) $resumen['total_productos'] ?></div>
            </div>
            <span class="lg-card-icon"><i class="fa fa-cube"></i></span>
        </div>
    </div>

    <div class="lg-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="lg-stat-label mb-1">Necesitan reposición</p>
                <div class="lg-stat-value is-red"><?= (int) $resumen['stock_bajo'] ?></div>
            </div>
            <span class="lg-card-icon"><i class="fa fa-exclamation-triangle"></i></span>
        </div>
    </div>

</div>

<!-- Filtros -->
<form class="lg-card mb-3" method="GET" action="<?= BASE_URL ?>">
    <input type="hidden" name="page" value="inventario">

    <div class="lg-filters">

        <div class="lg-field">
            <label class="lg-label" for="filtroNombre">Buscar</label>
            <input type="search" id="filtroNombre" name="nombre" class="lg-input"
                   value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>"
                   placeholder="Nombre del producto...">
        </div>

        <div class="lg-field">
            <label class="lg-label" for="filtroCategoria">Categor&iacute;a</label>
            <select id="filtroCategoria" name="categoria" class="lg-select">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"
                        <?= ($_GET['categoria'] ?? '') === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lg-field">
            <label class="lg-label" for="filtroStock">Estado de stock</label>
            <select id="filtroStock" name="stock" class="lg-select">
                <option value="">Todos</option>
                <option value="alto"     <?= ($_GET['stock'] ?? '') === 'alto' ? 'selected' : '' ?>>Stock alto</option>
                <option value="bajo"     <?= ($_GET['stock'] ?? '') === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                <option value="critico"  <?= ($_GET['stock'] ?? '') === 'critico' ? 'selected' : '' ?>>Stock cr&iacute;tico</option>
                <option value="agotado"  <?= ($_GET['stock'] ?? '') === 'agotado' ? 'selected' : '' ?>>Agotado</option>
            </select>
        </div>

        <div class="lg-field" style="flex:0 0 auto;">
            <button type="submit" class="lg-btn lg-btn--primary">
                <i class="fa fa-filter"></i> Filtrar
            </button>
        </div>

    </div>
</form>

<!-- Tabla -->
<div class="lg-card">

    <div class="lg-card-head">
        <span class="lg-card-icon"><i class="fa fa-list"></i></span>
        <div>
            <h2 class="lg-card-title">Listado de productos</h2>
            <p class="lg-card-subtitle"><?= (int) $resumen['total_productos'] ?> productos en <?= (int) $resumen['categorias'] ?> categor&iacute;as</p>
        </div>
    </div>

    <?php if (!$productos): ?>

        <div class="lg-empty">
            <i class="fa fa-cube"></i>
            <p class="mb-0">No hay productos que coincidan con el filtro.</p>
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="lg-table" id="tablaInventario">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categor&iacute;a</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>M&iacute;nimo</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <?php $stock = infoStock((int) $p['stock'], (int) $p['stock_minimo']); ?>

                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($p['nombre']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($p['categoria']) ?></td>
                            <td class="num"><?= soles((float) $p['precio']) ?></td>
                            <td class="num"><?= (float) $p['stock'] ?></td>
                            <td class="num text-muted"><?= (float) $p['stock_minimo'] ?></td>
                            <td>
                                <span class="lg-pill <?= $stock['pildora'] ?>">
                                    <?= htmlspecialchars($stock['etiqueta']) ?>
                                </span>
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost" title="Ajustar stock">
                                    <i class="fa fa-plus-circle"></i> Ajustar
                                </button>
                                <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost" title="Editar">
                                    <i class="fa fa-pencil"></i>
                                </button>
                            </td>

                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>
