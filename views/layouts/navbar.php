<?php
/**
 * Layout: navbar superior.
 */

$usuarioActual = usuarioActual();
?>

<nav class="main-header navbar navbar-expand">

    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Abrir menú">
                <i class="fa fa-bars"></i>
            </a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto align-items-center">

        <li class="nav-item dropdown">
            <button class="lg-usuario" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                <span class="avatar">
                    <i class="fa fa-user"></i>
                </span>

                <span class="d-none d-sm-inline"><?= htmlspecialchars($usuarioActual['rol']) ?></span>

                <i class="fa fa-chevron-down caret"></i>

            </button>

            <div class="dropdown-menu dropdown-menu-right mt-2" style="border-radius:10px;border-color:#EFEFEF;">

                <div class="dropdown-header text-center">
                    <strong><?= htmlspecialchars($usuarioActual['nombre']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($usuarioActual['usuario']) ?></small>
                </div>

                <div class="dropdown-divider"></div>

                <?php if (esPermitido('mesas')): ?>
                    <a href="<?= url('mesas') ?>" class="dropdown-item">
                        <i class="fa fa-table mr-2"></i> Mesas y Pedidos
                    </a>
                <?php endif; ?>

                <div class="dropdown-divider"></div>

                <a href="<?= url('logout') ?>" class="dropdown-item text-danger">
                    <i class="fa fa-sign-out mr-2"></i> Cerrar sesión
                </a>

            </div>
        </li>

    </ul>

</nav>
