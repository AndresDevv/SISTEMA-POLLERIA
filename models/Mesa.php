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
     * Traduce el estado de la base al que muestra la interfaz.
     *
     * "Ocupada" también se deriva de tener pedidos sin terminar, para que una
     * mesa no quede verde aunque el enum siga en 'libre'.
     */
    private function estadoVisual(array $mesa): string
    {
        if ($mesa['estado'] === 'reservada') {
            return 'reservada';
        }

        if ($mesa['estado'] === 'ocupada') {
            return 'ocupada';
        }

        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*)
             FROM pedidos
             WHERE mesa_id = :mesa
               AND estado IN ('pendiente','preparando','preparado')"
        );
        $stmt->execute([':mesa' => $mesa['id']]);

        return (int) $stmt->fetchColumn() > 0 ? 'ocupada' : 'libre';
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
            'ocupada' => $this->tiempoOcupacion($mesa) . ' min',
            default   => ''
        };
    }

    /**
     * Cambia el estado de una mesa (libre, ocupada o reservada).
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

    /**
     * Registra una reserva: la mesa queda en amarillo.
     *
     * @param string $nombre  a nombre de quién
     * @param string $hora    hora estimada, formato H:i
     */
    public function reservar(int $id, string $nombre = '', string $hora = ''): bool
    {
        return $this->cambiarEstado($id, 'reservada');
    }

    /**
     * Crea una mesa nueva.
     * El número se calcula como el mayor actual + 1 para no repetir.
     */
    public function crear(int $capacidad = 4): array
    {
        $stmt = $this->conexion->query("SELECT COALESCE(MAX(numero), 0) + 1 AS siguiente FROM mesas");
        $numero = (int) $stmt->fetchColumn();

        try {
            $stmt = $this->conexion->prepare(
                "INSERT INTO mesas (numero, capacidad, estado) VALUES (:numero, :capacidad, 'libre')"
            );
            $stmt->execute([':numero' => $numero, ':capacidad' => max(1, min(20, $capacidad))]);

            return [
                'success' => true,
                'id'      => (int) $this->conexion->lastInsertId(),
                'numero'  => $numero,
                'message' => 'Mesa ' . $numero . ' agregada.'
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'No se pudo agregar la mesa: ' . $e->getMessage()];
        }
    }

    /**
     * Elimina una mesa.
     * No se permite si tiene pedidos registrados.
     */
    public function eliminar(int $id): array
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE mesa_id = :id");
        $stmt->execute([':id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar: esa mesa tiene pedidos registrados.'
            ];
        }

        try {
            $stmt = $this->conexion->prepare("DELETE FROM mesas WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return ['success' => true, 'message' => 'Mesa eliminada.'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'No se pudo eliminar la mesa: ' . $e->getMessage()];
        }
    }
}
