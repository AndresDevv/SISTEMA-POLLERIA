<?php
// Crea un usuario por rol para probar los permisos.
// Ejecutar: php database/usuarios_prueba.php
//
// Las contraseñas se guardan con hash. Cambia "123456" antes de usarlo en
// una instalación real.

require_once __DIR__ . '/../config/database.php';

$db = conexionDB();

$usuarios = [
    ['nombre' => 'Mesero de Prueba', 'usuario' => 'mesero', 'rol' => 'Mesero', 'clave' => '123456'],
    ['nombre' => 'Cocina de Prueba', 'usuario' => 'cocina', 'rol' => 'Cocina', 'clave' => '123456']
];

foreach ($usuarios as $u) {

    $stmt = $db->prepare("SELECT id FROM roles WHERE nombre = :nombre");
    $stmt->execute([':nombre' => $u['rol']]);
    $rolId = $stmt->fetchColumn();

    if (!$rolId) {
        echo "Rol no encontrado: {$u['rol']}\n";
        continue;
    }

    $stmt = $db->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
    $stmt->execute([':usuario' => $u['usuario']]);

    if ($stmt->fetchColumn()) {
        echo "Ya existe: {$u['usuario']}\n";
        continue;
    }

    $stmt = $db->prepare(
        "INSERT INTO usuarios (nombre, usuario, password, rol_id, estado)
         VALUES (:nombre, :usuario, :password, :rol, 1)"
    );
    $stmt->execute([
        ':nombre'   => $u['nombre'],
        ':usuario'  => $u['usuario'],
        ':password' => password_hash($u['clave'], PASSWORD_DEFAULT),
        ':rol'      => (int) $rolId
    ]);

    echo "Creado: {$u['usuario']} ({$u['rol']}) / {$u['clave']}\n";
}

echo "\nEl usuario administrador ya existe (admin).\n";