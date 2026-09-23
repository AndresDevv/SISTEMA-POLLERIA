<?php

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

echo 'Bienvenido, ' . htmlspecialchars($_SESSION['nombre']);    