<?php

require_once __DIR__ . '/../config/database.php';

class Asistencia
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Obtiene la asistencia de hoy de un empleado.
     */
    public function obtenerDeHoy(int $empleadoId): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM asistencias WHERE empleado_id = :emp AND fecha = CURDATE() LIMIT 1"
        );
        $stmt->execute([':emp' => $empleadoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Marca la entrada de un empleado.
     */
    public function marcarEntrada(int $empleadoId): bool
    {
        // Verificar que el empleado existe y está activo
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM empleados WHERE id = :id AND estado = 1");
        $stmt->execute([':id' => $empleadoId]);
        if ((int) $stmt->fetchColumn() === 0) {
            return false;
        }

        // Verificar que no tenga asistencia hoy
        $stmt = $this->conexion->prepare(
            "SELECT id FROM asistencias WHERE empleado_id = :emp AND fecha = CURDATE() LIMIT 1"
        );
        $stmt->execute([':emp' => $empleadoId]);
        if ($stmt->fetchColumn()) {
            return false; // Ya tiene registro hoy
        }

        $stmt = $this->conexion->prepare(
            "INSERT INTO asistencias (empleado_id, fecha, hora_entrada, estado)
             VALUES (:emp, CURDATE(), CURTIME(), 'presente')"
        );
        return $stmt->execute([':emp' => $empleadoId]);
    }

    /**
     * Marca la salida de un empleado.
     */
    public function marcarSalida(int $empleadoId): bool
    {
        $stmt = $this->conexion->prepare(
            "SELECT id FROM asistencias WHERE empleado_id = :emp AND fecha = CURDATE() LIMIT 1"
        );
        $stmt->execute([':emp' => $empleadoId]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            return false; // No hay entrada registrada
        }

        $stmt = $this->conexion->prepare(
            "UPDATE asistencias SET hora_salida = CURTIME() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Registra en el log de auditoría.
     */
    public function registrarLog(int $empleadoId, string $accion, string $ip, string $userAgent): bool
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO asistencias_log (empleado_id, accion, fecha_hora, ip, user_agent)
             VALUES (:emp, :accion, NOW(), :ip, :ua)"
        );
        return $stmt->execute([
            ':emp'     => $empleadoId,
            ':accion'  => $accion,
            ':ip'      => $ip,
            ':ua'      => $userAgent
        ]);
    }

    /**
     * Obtiene el log de auditoría (para reportes).
     */
    public function obtenerLog(int $limite = 100): array
    {
        $sql = "SELECT al.*, e.nombre AS empleado_nombre
                FROM asistencias_log al
                INNER JOIN empleados e ON e.id = al.empleado_id
                ORDER BY al.fecha_hora DESC
                LIMIT $limite";
        return $this->conexion->query($sql)->fetchAll();
    }
}