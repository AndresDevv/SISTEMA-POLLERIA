<?php
/**
 * Vista: configuración.
 */

$ajustes = [
    [
        'titulo' => 'Negocio',
        'icono'  => 'fa fa-shopping-cart',
        'items'  => [
            ['etiqueta' => 'Nombre del negocio', 'valor' => APP_NOMBRE],
            ['etiqueta' => 'RUC / Tax ID',         'valor' => '-'],
            ['etiqueta' => 'Direcci&oacute;n',      'valor' => '-'],
            ['etiqueta' => 'Tel&eacute;fono',      'valor' => '-'],
            ['etiqueta' => 'Moneda',               'valor' => 'Soles (S/)']
        ]
    ],
    [
        'titulo' => 'Mesas',
        'icono'  => 'fa fa-table',
        'items'  => [
            ['etiqueta' => 'N&uacute;mero de mesas', 'valor' => '16'],
            ['etiqueta' => 'Capacidad m&aacute;xima', 'valor' => '6 personas'],
            ['etiqueta' => 'Reservas anticipadas',   'valor' => 'Activado']
        ]
    ],
    [
        'titulo' => 'Ventas',
        'icono'  => 'fa fa-cash-register',
        'items'  => [
            ['etiqueta' => 'Impuesto (IGV)',        'valor' => '18%'],
            ['etiqueta' => 'Propina sugerida',      'valor' => '10%'],
            ['etiqueta' => 'M&eacute;todos de pago', 'valor' => 'Efectivo, Yape, Plin, Tarjeta']
        ]
    ],
    [
        'titulo' => 'Inventario',
        'icono'  => 'fa fa-cube',
        'items'  => [
            ['etiqueta' => 'Alerta de stock bajo', 'valor' => 'Activada'],
            ['etiqueta' => 'Alerta por defecto',   'valor' => '10 unidades']
        ]
    ],
    [
        'titulo' => 'Roles y permisos',
        'icono'  => 'fa fa-user-tag',
        'items'  => [
            ['etiqueta' => 'Administrador', 'valor' => 'Acceso total'],
            ['etiqueta' => 'Supervisor',    'valor' => 'Ventas, inventario y reportes'],
            ['etiqueta' => 'Cajero',        'valor' => 'Ventas y caja'],
            ['etiqueta' => 'Mesero',        'valor' => 'Mesas, pedidos y servicios'],
            ['etiqueta' => 'Cocinero',      'valor' => 'Pedidos e inventario']
        ]
    ]
];
?>

<?php
encabezadoPagina(
    'fa fa-cog',
    'Configuraci&oacute;n',
    'Ajustes generales del sistema'
);
?>

<div class="lg-grid-2">

    <?php foreach ($ajustes as $grupo): ?>
        <div class="lg-card">
            <div class="lg-card-head">
                <span class="lg-card-icon"><i class="<?= htmlspecialchars($grupo['icono']) ?>"></i></span>
                <div>
                    <h2 class="lg-card-title"><?= $grupo['titulo'] ?></h2>
                </div>
            </div>

            <div class="lg-rows">
                <?php foreach ($grupo['items'] as $item): ?>
                    <div class="lg-row">
                        <span class="lg-row-label"><?= $item['etiqueta'] ?></span>
                        <span class="lg-row-value text-muted"><?= $item['valor'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="lg-btn lg-btn--sm lg-btn--ghost mt-3">
                <i class="fa fa-pencil"></i> Editar
            </button>
        </div>
    <?php endforeach; ?>

</div>
