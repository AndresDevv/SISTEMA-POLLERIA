<?php

session_start();

require_once __DIR__ . '/../../controllers/AuthController.php';

$authController = new AuthController();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {

        $error = 'Completa todos los campos.';

    } else {

        $resultado = $authController->iniciarSesion(
            $usuario,
            $password
        );

        if ($resultado['success']) {

            $datosUsuario = $resultado['usuario'];

            $_SESSION['usuario_id'] = $datosUsuario['id'];
            $_SESSION['nombre'] = $datosUsuario['nombre'];
            $_SESSION['usuario'] = $datosUsuario['usuario'];
            $_SESSION['rol_id'] = $datosUsuario['rol_id'];
            $_SESSION['rol'] = $datosUsuario['rol'];

            header('Location: /POLLERIA/views/admin/dashboard.php');
            exit;

        } else {

            $error = $resultado['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar Sesión | LOS GOMEZ</title>

    <link rel="stylesheet" href="views/assets/css/login.css">
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
                <?php if ($error !== ''): ?>

                <div class="login-error">
                    <?= htmlspecialchars($error) ?>
                </div>

                <?php endif; ?>


                <form action="" method="POST">

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