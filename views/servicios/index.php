<?php
/**
 * Vista: servicios.
 */

$servicios = [
    ['nombre' => 'Delivery propio',   'icono' => 'fa fa-motorcycle', 'descripcion' => 'Entregas a domicilio dentro del área',   'estado' => 'activo'],
    ['nombre' => 'Delivery externo',  'icono' => 'fa fa-truck',      'descripcion' => 'Envíos tercerizados',                     'estado' => 'activo'],
    ['nombre' => 'Take away',         'icono' => 'fa fa-bag',        'descripcion' => 'Pedidos para retirar en el local',         'estado' => 'activo'],
    ['nombre' => 'Reservas',          'icono' => 'fa fa-calendar',   'descripcion' => 'Gestión de mesas reservadas',              'estado' => 'activo'],
    ['nombre' => 'Catering',          'icono' => 'fa fa-users',      'descripcion' => 'Pedidos grandes para eventos',             'estado' => 'inactivo'],
    ['nombre' => 'Menú del día',      'icono' => 'fa fa-star',       'descripcion' => 'Promociones diarias del restaurante',     'estado' => 'activo']
];
?>

<?php
encabezadoPagina(
    'fa fa-cutlery',
    'Servicios',
    'Configura los servicios adicionales del restaurante'
);
?>

<div class="lg-grid-3">

    <?php foreach ($servicios as $s): ?>
        <div class="lg-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                <span class="lg-card-icon"><i class="<?= htmlspecialchars($s['icono']) ?>"></i></span>

                <?php if ($s['estado'] === 'activo'): ?>
                    <span class="lg-pill lg-pill--verde">Activo</span>
                <?php else: ?>
                    <span class="lg-pill lg-pill--pizarra">Inactivo</span>
                <?php endif; ?>
            </div>

            <h3 style="font-size:1.05rem;font-weight:700;margin:0 0 4px;">
                <?= htmlspecialchars($s['nombre']) ?>
            </h3>

            <p class="text-muted mb-3" style="font-size:0.86rem;">
                <?= htmlspecialchars($s['descripcion']) ?>
            </p>

            <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost">
                <i class="fa fa-cog"></i> Configurar
            </button>
        </div>
    <?php endforeach; ?>

</div>
