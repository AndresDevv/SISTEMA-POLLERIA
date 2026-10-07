<?php
/**
 * Vista: error 404.
 * Solo contenido: el layout ya lo incluye index.php.
 */
?>

<div class="lg-card text-center" style="padding:48px 24px;">

    <div class="lg-stat-icon mx-auto">
        <i class="fa fa-search"></i>
    </div>

    <h2 class="mt-3" style="font-size:1.4rem;font-weight:700;">P&aacute;gina no encontrada</h2>

    <p class="lg-muted mt-2">
        La secci&oacute;n que buscas no existe o a&uacute;n no est&aacute; disponible.
    </p>

    <?php
    $destino = primeraPaginaPermitida();
    ?>
    <a href="<?= url($destino) ?>" class="lg-btn lg-btn--primary mt-3">
        <i class="fa fa-home mr-2"></i> Volver al <?= htmlspecialchars($destino === 'dashboard' ? 'dashboard' : 'inicio') ?>
    </a>

</div>
