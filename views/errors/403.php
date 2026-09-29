<?php
/**
 * Vista: error 403 (sin permisos).
 * Solo contenido: el layout ya lo incluye index.php.
 */
?>

<div class="lg-card text-center" style="padding:48px 24px;">

    <div class="lg-stat-icon mx-auto" style="background-color:#FEE2E2;color:#EF4444;">
        <i class="fa fa-lock"></i>
    </div>

    <h2 class="mt-3" style="font-size:1.4rem;font-weight:700;">Sin permisos</h2>

    <p class="lg-muted mt-2">
        Tu rol no tiene acceso a esta secci&oacute;n del sistema.
        Si crees que es un error, comun&iacute;cate con el administrador.
    </p>

    <a href="<?= url('dashboard') ?>" class="lg-btn lg-btn--primary mt-3">
        <i class="fa fa-home mr-2"></i> Volver al dashboard
    </a>

</div>
