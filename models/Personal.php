<?php

require_once __DIR__ . '/../config/database.php';

class Personal
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Colaboradores con su usuario y rol asociado.
     *
     * Ojo: en la tabla empleados no hay columna "nombre"; el nombre real
     * vive en usuarios, así que se toma de ahí. Un empleado sin usuario
     * queda con el cargo como referencia.
     */
    public function colaboradores(): array
    {
        return $this->conexion->query(
            "SELECT e.id, e.cargo, e.dni, e.telefono, e.direccion,
                    e.fecha_ingreso, e.estado,
                    COALESCE(u.nombre, e.cargo, 'Colaborador') AS nombre,
                    u.usuario,
                    r.nombre AS rol
             FROM empleados e
             LEFT JOIN usuarios u ON u.id = e.usuario_id
             LEFT JOIN roles r ON r.id = u.rol_id
             ORDER BY nombre"
        )->fetchAll();
    }

    /**
     * Asistencias de hoy.
     */
    public function asistenciasHoy(): array
    {
        return $this->conexion->query(
            "SELECT a.*, COALESCE(u.nombre, e.cargo, 'Colaborador') AS empleado
             FROM asistencias a
             INNER JOIN empleados e ON e.id = a.empleado_id
             LEFT JOIN usuarios u ON u.id = e.usuario_id
             WHERE a.fecha = CURDATE()
             ORDER BY empleado"
        )->fetchAll();
    }

    /**
     * Marca entrada o salida de un colaborador.
     */
    public function registrarAsistencia(int $empleadoId, string $tipo): array
    {
        $permitidos = ['entrada', 'salida'];

        if (!in_array($tipo, $permitidos, true)) {
            return ['success' => false, 'message' => 'Tipo no válido.'];
        }

        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM empleados WHERE id = :id AND estado = 1");
        $stmt->execute([':id' => $empleadoId]);

        if ((int) $stmt->fetchColumn() === 0) {
            return ['success' => false, 'message' => 'El colaborador no existe.'];
        }

        // Solo una asistencia por día
        $stmt = $this->conexion->prepare(
            "SELECT id FROM asistencias WHERE empleado_id = :emp AND fecha = CURDATE() LIMIT 1"
        );
        $stmt->execute([':emp' => $empleadoId]);
        $existente = $stmt->fetchColumn();

        if ($tipo === 'entrada') {

            if ($existente) {
                return ['success' => false, 'message' => 'Ya registró su entrada hoy.'];
            }

            $stmt = $this->conexion->prepare(
                "INSERT INTO asistencias (empleado_id, fecha, hora_entrada, estado)
                 VALUES (:emp, CURDATE(), CURTIME(), 'presente')"
            );
            $stmt->execute([':emp' => $empleadoId]);

            return ['success' => true, 'message' => 'Entrada registrada.'];

        }

        if (!$existente) {
            return ['success' => false, 'message' => 'Primero debe registrar la entrada.'];
        }

        $stmt = $this->conexion->prepare(
            "UPDATE asistencias SET hora_salida = CURTIME() WHERE id = :id"
        );
        $stmt->execute([':id' => $existente]);

        return ['success' => true, 'message' => 'Salida registrada.'];
    }

    /**
     * Pagos de personal.
     */
    public function pagos(int $limite = 100): array
    {
        $sql = "SELECT p.*, COALESCE(u.nombre, e.cargo, 'Colaborador') AS empleado, e.cargo
                FROM pagos_personal p
                INNER JOIN empleados e ON e.id = p.empleado_id
                LEFT JOIN usuarios u ON u.id = e.usuario_id
                ORDER BY p.fecha_pago DESC, p.id DESC
                LIMIT $limite";

        return $this->conexion->query($sql)->fetchAll();
    }

    /**
     * Registra el pago de un colaborador.
     */
    public function registrarPago(int $empleadoId, string $periodo, float $monto, string $observacion = ''): array
    {
        if ($monto <= 0) {
            return ['success' => false, 'message' => 'El monto debe ser mayor a cero.'];
        }

        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM empleados WHERE id = :id AND estado = 1");
        $stmt->execute([':id' => $empleadoId]);

        if ((int) $stmt->fetchColumn() === 0) {
            return ['success' => false, 'message' => 'El colaborador no existe.'];
        }

        $stmt = $this->conexion->prepare(
            "INSERT INTO pagos_personal (empleado_id, periodo, monto, fecha_pago, observacion)
             VALUES (:emp, :periodo, :monto, CURDATE(), :obs)"
        );
        $stmt->execute([
            ':emp'     => $empleadoId,
            ':periodo' => $periodo !== '' ? $periodo : date('Y-m'),
            ':monto'   => $monto,
            ':obs'     => $observacion !== '' ? $observacion : null
        ]);

        return ['success' => true, 'message' => 'Pago registrado.'];
    }
}