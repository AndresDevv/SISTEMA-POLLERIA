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
                    m.estado,
                    m.reservado_por,
                    m.reservado_hora,
                    m.reservado_fecha
                FROM mesas m
                ORDER BY m.numero";

        $stmt = $this->conexion->query($sql);
        $mesas = $stmt->fetchAll();

        foreach ($mesas as &$mesa) {
            // El estado se calcula una sola vez y se reutiliza, para no
            // repetir la consulta de pedidos ni la corrección del enum
            $mesa['estado_visual'] = $this->estadoVisual($mesa);
            $mesa['tiempo']        = $this->tiempoOcupacion($mesa);
            $mesa['detalle']       = $this->detalle($mesa);
        }

        return $mesas;
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id, numero, capacidad, estado, reservado_por, reservado_hora, reservado_fecha
             FROM mesas WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $mesa = $stmt->fetch();

        return $mesa ?: null;
    }

    /**
     * ¿La mesa tiene algún pedido sin cobrar?
     *
     * Esta es la única regla de ocupación del sistema, y la comparten la
     * pantalla y la liberación automática.
     *
     * Lo que marca el final de una mesa es el COBRO, no el estado del
     * pedido: un plato que se sirve sigue sin pagarse, así que la mesa
     * tiene que seguir ocupada para que el mesero pueda cobrarla.
     *
     * @return array ['tiene' => bool, 'ultimo' => ?string fecha del último]
     */
    private function pendientesDeCobro(int $mesaId): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) AS n, MAX(p.fecha_creacion) AS ultimo
             FROM pedidos p
             LEFT JOIN ventas v
                    ON v.pedido_id = p.id AND v.estado = 'pagada'
             WHERE p.mesa_id = :mesa
               AND v.id IS NULL
               AND p.estado <> 'anulado'"
        );
        $stmt->execute([':mesa' => $mesaId]);

        $fila = $stmt->fetch() ?: ['n' => 0, 'ultimo' => null];

        return [
            'tiene'  => (int) $fila['n'] > 0,
            'ultimo' => $fila['ultimo']
        ];
    }

    /**
     * Traduce el estado de la base al que muestra la interfaz.
     *
     * Los pedidos son la fuente de verdad, no el enum. El enum se guarda
     * 'ocupada' al tomar un pedido, pero puede quedarse desfasado si el
     * pedido se cierra por otra vía. Por eso se comprueba en los dos
     * sentidos:
     *
     *   - hay pedidos sin cobrar -> ocupada, aunque el enum diga 'libre'
     *   - no hay pedidos sin cobrar -> libre, aunque diga 'ocupada'
     *
     * Solo la reserva manda sobre el enum, porque no depende de pedidos.
     */
    private function estadoVisual(array $mesa): string
    {
        if ($mesa['estado'] === 'reservada') {
            return 'reservada';
        }

        $estado = $this->pendientesDeCobro((int) $mesa['id'])['tiene']
            ? 'ocupada'
            : 'libre';

        // El enum quedó desfasado: se corrige para que la base tampoco mienta
        if ($mesa['estado'] !== $estado) {
            $this->corregirEstado($mesa['id'], $estado);
            $mesa['estado'] = $estado;
        }

        return $estado;
    }

    /**
     * Deja el enum de la mesa igual al estado real.
     */
    private function corregirEstado(int $mesaId, string $estado): void
    {
        try {
            $stmt = $this->conexion->prepare(
                "UPDATE mesas
                 SET estado = :estado,
                     reservado_por = NULL,
                     reservado_hora = NULL,
                     reservado_fecha = NULL
                 WHERE id = :id AND estado <> 'reservada'"
            );

            $stmt->execute([':estado' => $estado, ':id' => $mesaId]);

        } catch (PDOException $e) {
            // Si no se puede corregir, al menos la pantalla ya muestra bien
        }
    }

    /**
     * Minutos transcurridos desde el último pedido sin cobrar.
     *
     * Se cuentan también los ya servidos, porque mientras no estén pagados
     * la mesa sigue ocupada y el mesero necesita ver cuánto lleva.
     */
    private function tiempoOcupacion(array $mesa): ?int
    {
        $fecha = $this->pendientesDeCobro((int) $mesa['id'])['ultimo'];

        if (!$fecha) {
            return null;
        }

        return (int) ((time() - strtotime($fecha)) / 60);
    }

    /**
     * Texto secundario de la tarjeta de mesa.
     *
     * Usa el estado ya calculado en listar(), no lo vuelve a pedir.
     */
    private function detalle(array $mesa): string
    {
        $estado = $mesa['estado_visual'] ?? $this->estadoVisual($mesa);

        return match ($estado) {
            // En una reserva se muestra a nombre de quién quedó
            'reservada' => $this->textoReserva($mesa),
            'ocupada'   => $this->tiempoOcupacion($mesa) . ' min',
            default     => ''
        };
    }

    /**
     * Texto de la reserva: nombre de la persona y hora estimada.
     */
    private function textoReserva(array $mesa): string
    {
        $nombre = trim((string) ($mesa['reservado_por'] ?? ''));
        $hora   = $mesa['reservado_hora'] ?? null;

        if ($nombre === '' && !$hora) {
            return 'Reservada';
        }

        $texto = $nombre !== '' ? $nombre : 'Reservada';

        if ($hora) {
            $texto .= ' · ' . date('H:i', strtotime($hora));
        }

        return $texto;
    }

    /**
     * Cambia el estado de una mesa (libre, ocupada o reservada).
     *
     * Al dejar de estar reservada se borran los datos de la reserva, para
     * que no quede el nombre de alguien pegado a una mesa ya libre.
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $permitidos = ['libre', 'ocupada', 'reservada'];

        if (!in_array($estado, $permitidos, true)) {
            return false;
        }

        $sql = "UPDATE mesas SET estado = :estado";

        if ($estado !== 'reservada') {
            $sql .= ', reservado_por = NULL, reservado_hora = NULL, reservado_fecha = NULL';
        }

        $sql .= ' WHERE id = :id';

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * Registra una reserva: la mesa queda en amarillo y guarda a nombre de
     * quién quedó, para que el salón muestre quién la reservó.
     *
     * @param string $nombre  a nombre de quién
     * @param string $hora    hora estimada, formato H:i
     */
    public function reservar(int $id, string $nombre = '', string $hora = ''): array
    {
        $nombre = mb_substr(trim($nombre), 0, 100);
        $hora   = preg_match('/^\d{1,2}:\d{2}$/', $hora) ? $hora : null;

        try {
            $stmt = $this->conexion->prepare(
                "UPDATE mesas
                 SET estado = 'reservada',
                     reservado_por = :nombre,
                     reservado_hora = :hora,
                     reservado_fecha = CURDATE()
                 WHERE id = :id"
            );

            $stmt->execute([
                ':nombre' => $nombre !== '' ? $nombre : null,
                ':hora'   => $hora,
                ':id'     => $id
            ]);

            return ['success' => true, 'message' => 'Mesa reservada.'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'No se pudo reservar: ' . $e->getMessage()];
        }
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
