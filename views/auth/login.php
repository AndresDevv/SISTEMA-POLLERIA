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

    <!-- La version va en la direccion para que el navegador no se quede con
         una copia vieja del archivo en el cache del navegador. -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/login.css?v=<?= APP_VERSION ?>">

</head>

<body>

    <div class="login-container">

        <!-- Panel izquierdo -->
        <div class="login-brand">
            <?php /* El logo va sobre un cuadro blanco.

                     El dibujo es negro con fondo transparente y este panel es
                     rojo: puestos tal cual no se leeria nada. El cuadro
                     blanco es lo que lo hace visible, y de paso lo separa
                     del fondo como la marca del local. */ ?>
            <div class="brand-inner">

                <div class="logo-container">
                    <img src="<?= IMAGENES_URL ?>logo.png"
                         alt="<?= htmlspecialchars(APP_NOMBRE) ?>"
                         class="logo-image">
                </div>

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
                            <button
                                type="button"
                                class="toggle-password"
                                aria-label="Mostrar u ocultar contraseña"
                                aria-pressed="false"
                            >
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </button>
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

    <script>
        // Mostrar/ocultar contraseña
        (function () {
            var btn = document.querySelector('.toggle-password');
            var input = document.getElementById('password');

            if (!btn || !input) { return; }

            btn.addEventListener('click', function () {
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                btn.setAttribute('aria-pressed', !visible);
            });
        })();
    </script>
</body>
</html>
