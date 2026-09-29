<?php
/**
 * Layout: menú lateral.
 */

$menuItems = menuSistema();
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="<?= url('dashboard') ?>" class="brand-link">
        <span class="brand-text"><?= htmlspecialchars(APP_SIGLA) ?></span>
    </a>

    <div class="sidebar">

        <nav class="mt-0">
            <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

                <?php foreach ($menuItems as $item): ?>
                    <?php if (!empty($item['oculto']) || !menuVisible($item)) { continue; } ?>

                    <li class="nav-item">
                        <a href="<?= url($item['pagina']) ?>" class="nav-link<?= menuActivo($item['pagina']) ?>">
                            <i class="nav-icon <?= htmlspecialchars($item['icono']) ?>"></i>
                            <p><?= htmlspecialchars($item['titulo']) ?></p>
                        </a>
                    </li>

                <?php endforeach; ?>

                <li class="nav-item">
                    <a href="<?= url('logout') ?>" class="nav-link">
                        <i class="nav-icon fa fa-sign-out"></i>
                        <p>Cerrar sesión</p>
                    </a>
                </li>

            </ul>
        </nav>

    </div>

</aside>
