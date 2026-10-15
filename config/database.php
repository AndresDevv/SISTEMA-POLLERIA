<?php

/**
 * Conexión a la base de datos.
 *
 * Estos son los datos de una instalación normal de XAMPP: MySQL en el
 * mismo equipo, usuario root y sin clave.
 *
 * Si al llevarlo a otra maquina el MySQL de esa maquina tiene usuario
 * o clave distintos, no hay que tocar este archivo. Se crea en la raíz
 * del proyecto un database.local.php con los datos correctos y el
 * sistema usa esos en vez de estos:
 *
 *     <?php
 *     return ['servidor' => 'localhost', 'base' => 'polleria',
 *             'usuario' => 'root', 'clave' => 'miclave'];
 *
 * Ese archivo no se comparte con el proyecto, para no filtrar la clave.
 */

function conexionDB(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    // Valores por defecto: los de una instalación normal de XAMPP.
    $servidor = 'localhost';
    $baseDatos = 'polleria';
    $usuarioDB = 'root';
    $claveDB   = '';

    // Si existe database.local.php, manda lo que diga ese archivo.
    $propio = APP_ROOT . '/database.local.php';

    if (is_file($propio)) {
        $datos = require $propio;

        if (is_array($datos)) {
            $servidor = (string) ($datos['servidor'] ?? $servidor);
            $baseDatos = (string) ($datos['base'] ?? $baseDatos);
            $usuarioDB = (string) ($datos['usuario'] ?? $usuarioDB);
            $claveDB   = (string) ($datos['clave'] ?? '');
        }
    }

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
