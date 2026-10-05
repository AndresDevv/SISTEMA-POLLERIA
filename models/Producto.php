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
     * @param array $filtros nombre, categoria, stock ('alto','bajo','critico','agotado')
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
            $producto['nivel'] = nivelStock(
                (int) $producto['stock'],
                (int) $producto['stock_minimo']
            );
        }

        return $productos;
    }

    /**
     * Traduce el filtro de stock a una condición SQL.
     */
    private function condicionStock(string $nivel): string
    {
        return match ($nivel) {
            'agotado' => 'p.stock <= 0',
            'critico' => 'p.stock > 0 AND p.stock <= p.stock_minimo',
            'bajo'    => 'p.stock > p.stock_minimo AND p.stock <= (p.stock_minimo * 2)',
            default   => 'p.stock > (p.stock_minimo * 2)'
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
     */
    public function stockBajo(): int
    {
        $stmt = $this->conexion->query(
            "SELECT COUNT(*)
             FROM productos
             WHERE estado = 1
               AND stock <= stock_minimo"
        );

        return (int) $stmt->fetchColumn();
    }
}
