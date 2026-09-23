<?php

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        global $conexion;

        $this->conexion = $conexion;
    }

    public function buscarPorUsuario(string $usuario): ?array
    {
        $sql = "SELECT
                    u.id,
                    u.rol_id,
                    u.nombre,
                    u.usuario,
                    u.password,
                    u.estado,
                    r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.usuario = :usuario
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }
}
?>
