<?php

/**
 * Manejo de sesión y helpers de autenticación.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Indica si hay un usuario con sesión activa.
 */
function sesionActiva(): bool
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Devuelve los datos del usuario en sesión.
 */
function usuarioActual(): array
{
    return [
        'id'      => $_SESSION['usuario_id'] ?? null,
        'nombre'  => $_SESSION['nombre'] ?? '',
        'usuario' => $_SESSION['usuario'] ?? '',
        'rol_id'  => $_SESSION['rol_id'] ?? null,
        'rol'     => $_SESSION['rol'] ?? ''
    ];
}

/**
 * Indica si el usuario en sesión es administrador.
 */
function esAdministrador(): bool
{
    $rol = strtolower((string) ($_SESSION['rol'] ?? ''));

    return $rol !== '' && (str_contains($rol, 'admin') || (int) ($_SESSION['rol_id'] ?? 0) === 1);
}

/**
 * Redirige a una ruta interna y corta la ejecución.
 */
function redirigir(string $ruta): void
{
    header('Location: ' . BASE_URL . $ruta);
    exit;
}
