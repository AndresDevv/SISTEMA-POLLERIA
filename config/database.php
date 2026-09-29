<?php

/**
 * Conexión a la base de datos.
 *
 * Se encapsula en una función a propósito: antes estas variables vivían en el
 * ámbito global y `$password` pisaba la contraseña del formulario de login
 * justo antes de validarla, impidiendo iniciar sesión.
 */

function conexionDB(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $servidor = "localhost";
    $baseDatos = "polleria";
    $usuarioDB = "root";
    $claveDB = "";

    try {

        $conexion = new PDO(
            "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4",
            $usuarioDB,
            $claveDB
        );

        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conexion->exec("SET NAMES utf8mb4");

    } catch (PDOException $e) {
        die("Error de conexi&oacute;n: " . $e->getMessage());
    }

    return $conexion;
}
