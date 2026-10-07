<?php

require_once __DIR__ . '/../config/database.php';

class Inventario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Ajusta el stock de un producto y deja el movimiento.
     *
     * @param string $tipo   'entrada' | 'salida' | 'ajuste'
     * @param int    $cantidad  unidades a sumar (entrada), restar (salida)
     *                          o valor final (ajuste). Siempre entero.
     */
    public function ajustar(int $productoId, string $tipo, float $cantidad, string $motivo = ''): array
    {
        $permitidos = ['entrada', 'salida', 'ajuste'];

        if (!in_array($tipo, $permitidos, true)) {
            return ['success' => false, 'message' => 'Tipo de movimiento no válido.'];
        }

        // El stock se maneja en unidades enteras
        $cantidad = (int) $cantidad;

        if ($cantidad < 0) {
            return ['success' => false, 'message' => 'La cantidad no puede ser negativa.'];
        }

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "SELECT nombre, stock FROM productos WHERE id = :id AND estado = 1 FOR UPDATE"
            );
            $stmt->execute([':id' => $productoId]);
            $producto = $stmt->fetch();

            if (!$producto) {
                $this->conexion->rollBack();

                return ['success' => false, 'message' => 'El producto no existe.'];
            }

            $anterior = (int) $producto['stock'];

            $nuevo = match ($tipo) {
                'entrada' => $anterior + $cantidad,
                'salida'  => $anterior - $cantidad,
                default   => $cantidad
            };

            if ($nuevo < 0) {
                $this->conexion->rollBack();

                return [
                    'success' => false,
                    'message' => 'No hay stock suficiente (disponible: ' . $anterior . ').'
                ];
            }

            $stmt = $this->conexion->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
            $stmt->execute([':stock' => $nuevo, ':id' => $productoId]);

            $stmt = $this->conexion->prepare(
                "INSERT INTO movimientos_inventario
                    (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                 VALUES (:producto_id, :usuario_id, :tipo, :cantidad, :anterior, :nuevo, :motivo)"
            );
            $stmt->execute([
                ':producto_id' => $productoId,
                ':usuario_id'  => $usuarioId > 0 ? $usuarioId : null,
                ':tipo'        => $tipo,
                ':cantidad'    => abs($nuevo - $anterior),
                ':anterior'    => $anterior,
                ':nuevo'       => $nuevo,
                ':motivo'      => $motivo !== '' ? $motivo : 'Ajuste manual'
            ]);

            $this->conexion->commit();

            return [
                'success' => true,
                'message' => 'Stock de ' . $producto['nombre'] . ' actualizado a ' . $nuevo . '.'
            ];

        } catch (PDOException $e) {
            $this->conexion->rollBack();

            return ['success' => false, 'message' => 'No se pudo ajustar el stock: ' . $e->getMessage()];
        }
    }

    /**
     * Historial de movimientos.
     */
    public function movimientos(int $limite = 150): array
    {
        $sql = "SELECT
                    m.id, m.tipo, m.cantidad, m.stock_anterior, m.stock_nuevo,
                    m.motivo, m.fecha,
                    p.nombre AS producto,
                    u.nombre AS usuario
                FROM movimientos_inventario m
                INNER JOIN productos p ON p.id = m.producto_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                ORDER BY m.fecha DESC, m.id DESC
                LIMIT $limite";

        return $this->conexion->query($sql)->fetchAll();
    }
}