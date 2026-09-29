<?php
/**
 * Vista: login (standalone, sin layout de AdminLTE).
 * El procesamiento del formulario se hace en index.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar Sesión | <?= htmlspecialchars(APP_NOMBRE) ?></title>

    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/login.css">

</head>

<body>

    <div class="login-container">

        <!-- Panel izquierdo -->
        <div class="login-brand">
            <div class="logo-container">
                <span>logo de polleria</span>
            </div>
        </div>

        <!-- Panel derecho -->
        <div class="login-panel">

            <div class="login-card">
                <h1>Iniciar Sesión</h1>

                <p class="login-description">
                    Ingresa tus credenciales para acceder al sistema
                </p>

                <!-- MOSTRAR ERROR -->
                <?php if (!empty($error)): ?>

                <div class="login-error">
                    <?= htmlspecialchars($error) ?>
                </div>

                <?php endif; ?>

                <form action="<?= url('login') ?>" method="POST">

                    <!-- Usuario -->
                    <div class="form-group">

                        <label for="usuario">
                            Usuario
                        </label>

                        <div class="input-container">
                            <input
                                type="text"
                                id="usuario"
                                name="usuario"
                                placeholder="Ingresa tu usuario"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </div>

                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">

                        <label for="password">
                            Contraseña
                        </label>

                        <div class="input-container">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                    </div>

                    <!-- Botón -->
                    <button type="submit" class="btn-login">

                        <span class="btn-icon"></span>

                        Iniciar Sesión

                    </button>

                </form>

                <p class="login-footer">
                    Acceso exclusivo para el personal autorizado
                </p>
            </div>

        </div>

    </div>

</body>
</html>
