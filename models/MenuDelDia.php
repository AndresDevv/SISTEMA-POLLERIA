<?php

/**
 * Menú del día.
 *
 * La carta que se ofrece hoy: los platos y las bebidas con lo que queda
 * de cada uno y si están disponibles o agotados.
 *
 * Sale de la tabla de productos, la misma que maneja Inventario. No hay
 * una carta aparte que se pueda desincronizar del stock real; lo único que
 * se añade aquí es la marca de "agotado".
 *
 * Esa marca va aparte del stock a propósito: un plato puede acabarse en la
 * cocina aunque el inventario todavía tenga unidades contadas. Marcarla
 * aparte le dice al mesero que ya no se pide, sin mentir sobre las
 * existencias.
 *
 * Lo que no se puede pedir es lo que está agotado o lo que tiene stock 0.
 */
class MenuDelDia
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Carta del día, agrupada por categoría.
     *
     * Sale una fila por producto con su categoría ya incluida, en el orden
     * en que aparecen en la carta. Los que no están activos (estado = 0)
     * se omiten: son productos dados de baja, no platos del día.
     *
     * @return array [['id' => int, 'categoria' => string, 'productos' => []], ...]
     */
    public function carta(): array
    {
        $stmt = $this->conexion->query(
            "SELECT p.id,
                    p.nombre,
                    p.descripcion,
                    p.precio,
                    p.stock,
                    p.stock_minimo,
                    p.agotado,
                    c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE p.estado = 1
            AND c.estado = 1
            ORDER BY c.nombre, p.nombre"
        );

        $grupos = [];

        foreach ($stmt->fetchAll() as $fila) {
            $categoria = (string) $fila['categoria'];

            if (!isset($grupos[$categoria])) {
                $grupos[$categoria] = [
                    'categoria' => $categoria,
                    'productos' => []
                ];
            }

            $agotado = (int) $fila['agotado'] === 1;
            $stock   = (int) $fila['stock'];

            $grupos[$categoria]['productos'][] = [
                'id'          => (int) $fila['id'],
                'nombre'      => (string) $fila['nombre'],
                'descripcion' => (string) $fila['descripcion'],
                'precio'      => (float) $fila['precio'],
                'stock'       => $stock,
                'minimo'      => (int) $fila['stock_minimo'],
                'agotado'     => $agotado,

                /**
                 * Por debajo del mínimo ya se está por acabar.
                 *
                 * No es lo mismo que agotado: el plato todavía se puede
                 * pedir, pero conviene avisar al mesero. Por eso va aparte
                 * en vez de mezclarse con el estado de la tarjeta.
                 */
                'por_acabar' => !$agotado
                    && $stock > 0
                    && $stock <= (int) $fila['stock_minimo'],
            ];
        }

        return array_values($grupos);
    }

    /**
     * Marcas un plato como agotado o como disponible otra vez.
     *
     * Solo se mueve la marca: no se toca el stock. El stock se lleva la
     * cuenta real (entradas, salidas y ventas), y esta marca es para
     * decir "ya no lo estamos dando" sin falsear las existencias.
     *
     * @return array resultado con success, para que la API lo conteste
     */
    public function marcarAgotado(int $id, bool $agotado): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'El plato no es válido.'];
        }

        $stmt = $this->conexion->prepare(
            "SELECT nombre FROM productos WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $plato = $stmt->fetch();

        if (!$plato) {
            return ['success' => false, 'message' => 'Ese plato ya no existe.'];
        }

        $marca = $agotado ? 1 : 0;

        $stmt = $this->conexion->prepare(
            "UPDATE productos SET agotado = :agotado WHERE id = :id"
        );
        $stmt->execute([':agotado' => $marca, ':id' => $id]);

        return [
            'success' => true,
            'agotado' => $agotado,
            'message' => $agotado
                ? $plato['nombre'] . ' quedó marcado como agotado.'
                : $plato['nombre'] . ' vuelve a estar disponible.',
        ];
    }
}
