<?php

require_once __DIR__ . '/../config/database.php';

class Producto
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Catálogo de productos con su categoría y estado de stock.
     *
     * @param array $filtros nombre, categoria, stock ('alto','medio','agotado')
     */
    public function listar(array $filtros = []): array
    {
        $sql = "SELECT
                    p.id,
                    p.nombre,
                    p.descripcion,
                    p.precio,
                    p.stock,
                    p.stock_minimo,
                    c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estado = 1
                  AND c.estado = 1";

        $condiciones = [];
        $parametros  = [];

        if (!empty($filtros['nombre'])) {
            $condiciones[]        = "p.nombre LIKE :nombre";
            $parametros[':nombre'] = '%' . $filtros['nombre'] . '%';
        }

        if (!empty($filtros['categoria'])) {
            $condiciones[]            = "c.nombre = :categoria";
            $parametros[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['stock'])) {
            $condiciones[] = $this->condicionStock($filtros['stock']);
        }

        if ($condiciones) {
            $sql .= ' AND ' . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY c.nombre, p.nombre";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        $productos = $stmt->fetchAll();

        foreach ($productos as &$producto) {
            $producto['nivel'] = nivelStock((int) $producto['stock']);
        }

        return $productos;
    }

    /**
     * Traduce el filtro de stock a una condición SQL.
     */
    private function condicionStock(string $nivel): string
    {
        // Mismos umbrales que el color: rojo < 5, amarillo hasta 30, verde > 30
        return match ($nivel) {
            'agotado' => 'p.stock < ' . STOCK_ROJO,
            'medio'   => 'p.stock >= ' . STOCK_ROJO . ' AND p.stock <= ' . STOCK_VERDE,
            'alto'    => 'p.stock > ' . STOCK_VERDE,
            default   => '1 = 1'
        };
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id, nombre, precio, stock, stock_minimo
             FROM productos
             WHERE id = :id AND estado = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch();

        return $producto ?: null;
    }

    /**
     * Productos que necesitan reposición.
     *
     * Usa el mismo umbral rojo que el color de las tablas, para que el
     * número del tablero coincida con lo que se ve en rojo.
     */
    public function stockBajo(): int
    {
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock < :rojo"
        );
        $stmt->execute([':rojo' => STOCK_ROJO]);

        return (int) $stmt->fetchColumn();
    }
}
