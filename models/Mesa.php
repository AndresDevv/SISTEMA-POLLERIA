<?php

require_once __DIR__ . '/../config/database.php';

class Mesa
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Listado de mesas con el estado visual derivado de pedidos y ventas.
     */
    public function listar(): array
    {
        $sql = "SELECT
                    m.id,
                    m.numero,
                    m.capacidad,
                    m.estado
                FROM mesas m
                ORDER BY m.numero";

        $stmt = $this->conexion->query($sql);
        $mesas = $stmt->fetchAll();

        foreach ($mesas as &$mesa) {
            $mesa['estado_visual'] = $this->estadoVisual($mesa);
            $mesa['tiempo']        = $this->tiempoOcupacion($mesa);
            $mesa['detalle']       = $this->detalle($mesa);
        }

        return $mesas;
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id, numero, capacidad, estado FROM mesas WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $mesa = $stmt->fetch();

        return $mesa ?: null;
    }

    /**
     * Traduce el estado de la base a los cinco estados que usa la interfaz.
     */
    private function estadoVisual(array $mesa): string
    {
        if ($mesa['estado'] === 'reservada') {
            return 'reservada';
        }

        // Tiene un pedido sin terminar
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*)
             FROM pedidos
             WHERE mesa_id = :mesa
               AND estado IN ('pendiente','preparando','preparado')"
        );
        $stmt->execute([':mesa' => $mesa['id']]);

        if ((int) $stmt->fetchColumn() > 0) {
            return 'ocupada';
        }

        if ($mesa['estado'] === 'ocupada') {
            return 'ocupada';
        }

        // Sin pedido activo: el último pedido quedó sin cobrar
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*)
             FROM pedidos p
             LEFT JOIN ventas v ON v.pedido_id = p.id AND v.estado = 'pagada'
             WHERE p.mesa_id = :mesa
               AND v.id IS NULL
               AND p.estado = 'entregado'"
        );
        $stmt->execute([':mesa' => $mesa['id']]);

        if ((int) $stmt->fetchColumn() > 0) {
            return 'porpagar';
        }

        return 'libre';
    }

    /**
     * Minutos transcurridos desde el último pedido entregado.
     */
    private function tiempoOcupacion(array $mesa): ?int
    {
        $stmt = $this->conexion->prepare(
            "SELECT fecha_creacion
             FROM pedidos
             WHERE mesa_id = :mesa
               AND estado IN ('pendiente','preparando','preparado','entregado')
             ORDER BY fecha_creacion DESC
             LIMIT 1"
        );
        $stmt->execute([':mesa' => $mesa['id']]);
        $fecha = $stmt->fetchColumn();

        if (!$fecha) {
            return null;
        }

        return (int) ((time() - strtotime($fecha)) / 60);
    }

    /**
     * Texto secundario de la tarjeta de mesa.
     */
    private function detalle(array $mesa): string
    {
        $estado = $this->estadoVisual($mesa);

        return match ($estado) {
            'ocupada'  => $this->tiempoOcupacion($mesa) . ' min',
            'porpagar' => $this->tiempoOcupacion($mesa) . ' min',
            'pagada'   => 'Pagado',
            default    => ''
        };
    }

    /**
     * Cambia el estado de una mesa.
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $permitidos = ['libre', 'ocupada', 'reservada'];

        if (!in_array($estado, $permitidos, true)) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            "UPDATE mesas SET estado = :estado WHERE id = :id"
        );

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }
}
