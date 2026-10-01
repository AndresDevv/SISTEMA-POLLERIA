<?php

require_once __DIR__ . '/../config/database.php';

/**
 * CRUD genérico por definición de recursos.
 *
 * Evita escribir un modelo y un controlador por cada módulo: cada recurso se
 * declara con su tabla, sus campos y sus validaciones, y todo lo demás
 * (listar, crear, editar, eliminar) sale de aquí.
 */
class Gestion
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = conexionDB();
    }

    /**
     * Definición de todos los recursos administrables.
     *
     * campos: tipo => texto | numero | decimal | enum | fecha | check | password
     */
    public static function recursos(): array
    {
        return [

            // -------------------------------------------------
            'productos' => [
                'tabla'   => 'productos',
                'titulo'  => 'Producto',
                'modulo'  => 'inventario',
                'orden'   => 'p.nombre',
                'campos'  => [
                    'nombre'        => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'requerido' => true],
                    'categoria_id'  => ['tipo' => 'relacion', 'etiqueta' => 'Categoría', 'tabla' => 'categorias', 'requerido' => true],
                    'precio'        => ['tipo' => 'decimal', 'etiqueta' => 'Precio', 'requerido' => true, 'default' => 0],
                    'stock'         => ['tipo' => 'decimal', 'etiqueta' => 'Stock', 'default' => 0],
                    'stock_minimo'  => ['tipo' => 'decimal', 'etiqueta' => 'Stock mínimo', 'default' => 0],
                    'descripcion'   => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'estado'        => ['tipo' => 'check', 'etiqueta' => 'Activo', 'default' => 1]
                ],
                'listado' => 'p.id, p.nombre, c.nombre AS relacion, p.precio, p.stock, p.stock_minimo, p.estado',
                'join'    => 'INNER JOIN categorias c ON c.id = p.categoria_id'
            ],

            'categorias' => [
                'tabla'  => 'categorias',
                'titulo' => 'Categoría',
                'modulo' => 'inventario',
                'orden'  => 'nombre',
                'campos' => [
                    'nombre'      => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'requerido' => true],
                    'descripcion' => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'estado'      => ['tipo' => 'check', 'etiqueta' => 'Activa', 'default' => 1]
                ],
                'listado' => '*'
            ],

            'proveedores' => [
                'tabla'  => 'proveedores',
                'titulo' => 'Proveedor',
                'modulo' => 'compras',
                'orden'  => 'nombre',
                'campos' => [
                    'nombre'      => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'requerido' => true],
                    'documento'   => ['tipo' => 'texto', 'etiqueta' => 'RUC / Documento'],
                    'telefono'    => ['tipo' => 'texto', 'etiqueta' => 'Teléfono'],
                    'direccion'   => ['tipo' => 'texto', 'etiqueta' => 'Dirección'],
                    'estado'      => ['tipo' => 'check', 'etiqueta' => 'Activo', 'default' => 1]
                ],
                'listado' => '*'
            ],

            'empleados' => [
                'tabla'  => 'empleados',
                'titulo' => 'Colaborador',
                'modulo' => 'personal',
                'orden'  => 'e.id DESC',
                'campos' => [
                    'nombre'       => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'requerido' => true],
                    'cargo'        => ['tipo' => 'texto', 'etiqueta' => 'Cargo'],
                    'dni'          => ['tipo' => 'texto', 'etiqueta' => 'DNI'],
                    'telefono'     => ['tipo' => 'texto', 'etiqueta' => 'Teléfono'],
                    'direccion'    => ['tipo' => 'texto', 'etiqueta' => 'Dirección'],
                    'fecha_ingreso' => ['tipo' => 'fecha', 'etiqueta' => 'Fecha de ingreso'],
                    'estado'       => ['tipo' => 'check', 'etiqueta' => 'Activo', 'default' => 1]
                ],
                'listado' => '*'
            ],

            'usuarios' => [
                'tabla'  => 'usuarios',
                'titulo' => 'Usuario',
                'modulo' => 'personal',
                'orden'  => 'u.id DESC',
                'campos' => [
                    'nombre'   => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'requerido' => true],
                    'usuario'  => ['tipo' => 'texto', 'etiqueta' => 'Usuario', 'requerido' => true],
                    'password' => ['tipo' => 'password', 'etiqueta' => 'Contraseña'],
                    'rol_id'   => ['tipo' => 'relacion', 'etiqueta' => 'Rol', 'tabla' => 'roles', 'requerido' => true],
                    'estado'   => ['tipo' => 'check', 'etiqueta' => 'Activo', 'default' => 1]
                ],
                'listado' => 'u.id, u.nombre, u.usuario, r.nombre AS relacion, u.estado',
                'join'    => 'INNER JOIN roles r ON r.id = u.rol_id'
            ],

            'servicios' => [
                'tabla'  => 'servicios',
                'titulo' => 'Servicio',
                'modulo' => 'servicios',
                'orden'  => 'fecha_vencimiento DESC',
                'campos' => [
                    'tipo'              => ['tipo' => 'enum', 'etiqueta' => 'Tipo', 'requerido' => true,
                                             'opciones' => ['luz', 'agua', 'internet', 'otro']],
                    'descripcion'       => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'monto'             => ['tipo' => 'decimal', 'etiqueta' => 'Monto', 'requerido' => true, 'default' => 0],
                    'fecha_vencimiento' => ['tipo' => 'fecha', 'etiqueta' => 'Vencimiento'],
                    'fecha_pago'        => ['tipo' => 'fecha', 'etiqueta' => 'Fecha de pago'],
                    'estado'            => ['tipo' => 'enum', 'etiqueta' => 'Estado',
                                             'opciones' => ['pendiente', 'pagado', 'vencido'], 'default' => 'pendiente']
                ],
                'listado' => '*'
            ],

            'egresos' => [
                'tabla'  => 'egresos',
                'titulo' => 'Egreso',
                'modulo' => 'finanzas',
                'orden'  => 'e.fecha DESC',
                'campos' => [
                    'concepto'    => ['tipo' => 'texto', 'etiqueta' => 'Concepto', 'requerido' => true],
                    'descripcion' => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'monto'       => ['tipo' => 'decimal', 'etiqueta' => 'Monto', 'requerido' => true, 'default' => 0],
                    'fecha'       => ['tipo' => 'fecha', 'etiqueta' => 'Fecha']
                ],
                'listado' => 'e.id, e.concepto, e.monto, e.fecha',
                'fechaDefecto' => 'CURDATE()'
            ],

            'ingresos' => [
                'tabla'  => 'ingresos',
                'titulo' => 'Ingreso',
                'modulo' => 'finanzas',
                'orden'  => 'i.fecha DESC',
                'campos' => [
                    'concepto'    => ['tipo' => 'texto', 'etiqueta' => 'Concepto', 'requerido' => true],
                    'descripcion' => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'monto'       => ['tipo' => 'decimal', 'etiqueta' => 'Monto', 'requerido' => true, 'default' => 0],
                    'fecha'       => ['tipo' => 'fecha', 'etiqueta' => 'Fecha']
                ],
                'listado' => 'i.id, i.concepto, i.monto, i.fecha',
                'fechaDefecto' => 'CURDATE()'
            ],

            'gastos' => [
                'tabla'  => 'gastos',
                'titulo' => 'Gasto',
                'modulo' => 'finanzas',
                'orden'  => 'g.fecha DESC',
                'campos' => [
                    'categoria'   => ['tipo' => 'texto', 'etiqueta' => 'Categoría', 'requerido' => true],
                    'descripcion' => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'monto'       => ['tipo' => 'decimal', 'etiqueta' => 'Monto', 'requerido' => true, 'default' => 0],
                    'fecha'       => ['tipo' => 'fecha', 'etiqueta' => 'Fecha']
                ],
                'listado' => 'g.id, g.categoria, g.descripcion, g.monto, g.fecha',
                'fechaDefecto' => 'CURDATE()'
            ],

            'configuracion_negocio' => [
                'tabla'  => 'configuracion_negocio',
                'titulo' => 'Configuración',
                'modulo' => 'configuracion',
                'orden'  => 'id',
                'singleton' => true,
                'campos' => [
                    'nombre'    => ['tipo' => 'texto', 'etiqueta' => 'Nombre del negocio', 'requerido' => true],
                    'ruc'       => ['tipo' => 'texto', 'etiqueta' => 'RUC'],
                    'direccion' => ['tipo' => 'texto', 'etiqueta' => 'Dirección'],
                    'telefono'  => ['tipo' => 'texto', 'etiqueta' => 'Teléfono']
                ],
                'listado' => '*'
            ]
        ];
    }

    public static function recurso(string $nombre): ?array
    {
        $recursos = self::recursos();

        return $recursos[$nombre] ?? null;
    }

    /**
     * Listado con búsqueda.
     *
     * Cada recurso declara sus columnas en 'listado' y, si necesita, un 'join'.
     * El FROM lo arma este método.
     */
    public function listar(string $recurso, string $buscar = ''): array
    {
        $def = self::recurso($recurso);

        if (!$def) {
            return [];
        }

        $tabla = $def['tabla'];
        $alias = $this->alias($tabla);
        $join  = $def['join'] ?? '';

        $columnas = ($def['listado'] ?? '*') === '*' ? "$alias.*" : $def['listado'];

        $sql = "SELECT $columnas FROM $tabla $alias $join";

        $parametros = [];

        if ($buscar !== '') {
            $buscables = ['nombre', 'concepto', 'categoria', 'descripcion', 'usuario', 'documento', 'dni'];
            $partes = [];

            foreach ($buscables as $columna) {
                if (in_array($columna, $this->columnas($tabla), true)) {
                    $partes[] = "$alias.$columna LIKE :b";
                }
            }

            if ($partes) {
                $sql .= ' WHERE (' . implode(' OR ', $partes) . ')';
                $parametros[':b'] = '%' . $buscar . '%';
            }
        }

        $sql .= ' ORDER BY ' . $this->ajustarOrden($def['orden'], $alias) . ' LIMIT 300';

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function porId(string $recurso, int $id): ?array
    {
        $def = self::recurso($recurso);

        if (!$def) {
            return null;
        }

        $stmt = $this->conexion->prepare("SELECT * FROM {$def['tabla']} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Opciones para los campos tipo relación.
     */
    public function opciones(string $tabla): array
    {
        if (!preg_match('/^[a-z_]+$/', $tabla)) {
            return [];
        }

        return $this->conexion
            ->query("SELECT id, nombre FROM $tabla ORDER BY nombre")
            ->fetchAll();
    }

    /**
     * Crea un registro.
     */
    public function crear(string $recurso, array $datos): array
    {
        $def = self::recurso($recurso);

        if (!$def) {
            return ['success' => false, 'message' => 'Recurso desconocido.'];
        }

        $campos = [];
        $valores = [];

        foreach ($def['campos'] as $campo => $regla) {
            $valor = $this->valor($regla, $datos[$campo] ?? null);

            if ($valor === null && !empty($regla['requerido'])) {
                return ['success' => false, 'message' => 'El campo "' . $regla['etiqueta'] . '" es obligatorio.'];
            }

            $campos[] = $campo;
            $valores[] = $valor;
        }

        // Fecha por defecto
        if (!empty($def['fechaDefecto']) && empty($datos['fecha'])) {
            $campos[] = 'fecha';
            $valores[] = $def['fechaDefecto'];
        }

        // Usuario que registra
        if (in_array('usuario_id', $this->columnas($def['tabla']), true)) {
            $campos[] = 'usuario_id';
            $valores[] = (int) ($_SESSION['usuario_id'] ?? 0);
        }

        // Contraseñas siempre en hash
        if (array_key_exists('password', $def['campos']) && !empty($datos['password'])) {
            $i = array_search('password', $campos, true);
            $valores[$i] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }

        $placeholders = implode(', ', array_fill(0, count($campos), '?'));
        $columnas = implode(', ', $campos);

        try {
            $stmt = $this->conexion->prepare(
                "INSERT INTO {$def['tabla']} ($columnas) VALUES ($placeholders)"
            );
            $stmt->execute($valores);

            return ['success' => true, 'id' => (int) $this->conexion->lastInsertId(), 'message' => 'Registro creado.'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => $this->errorLegible($e)];
        }
    }

    /**
     * Actualiza un registro.
     */
    public function actualizar(string $recurso, int $id, array $datos): array
    {
        $def = self::recurso($recurso);

        if (!$def) {
            return ['success' => false, 'message' => 'Recurso desconocido.'];
        }

        $asignaciones = [];
        $valores = [];

        foreach ($def['campos'] as $campo => $regla) {

            // No tocar campos ausentes
            if (!array_key_exists($campo, $datos)) {
                continue;
            }

            $valor = $this->valor($regla, $datos[$campo]);

            if ($valor === null && !empty($regla['requerido'])) {
                return ['success' => false, 'message' => 'El campo "' . $regla['etiqueta'] . '" es obligatorio.'];
            }

            if ($campo === 'password') {
                if (empty($datos['password'])) {
                    continue;
                }
                $valor = password_hash($datos['password'], PASSWORD_DEFAULT);
            }

            $asignaciones[] = "$campo = ?";
            $valores[] = $valor;
        }

        if (!$asignaciones) {
            return ['success' => false, 'message' => 'No hay nada que actualizar.'];
        }

        $valores[] = $id;

        try {
            $stmt = $this->conexion->prepare(
                "UPDATE {$def['tabla']} SET " . implode(', ', $asignaciones) . ' WHERE id = ?'
            );
            $stmt->execute($valores);

            return ['success' => true, 'message' => 'Registro actualizado.'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => $this->errorLegible($e)];
        }
    }

    /**
     * Elimina un registro.
     */
    public function eliminar(string $recurso, int $id): array
    {
        $def = self::recurso($recurso);

        if (!$def) {
            return ['success' => false, 'message' => 'Recurso desconocido.'];
        }

        try {
            $stmt = $this->conexion->prepare("DELETE FROM {$def['tabla']} WHERE id = ?");
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'Registro eliminado.'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => $this->errorLegible($e)];
        }
    }

    /**
     * Normaliza un valor según la definición del campo.
     */
    private function valor(array $regla, $valor)
    {
        if ($valor === null || $valor === '') {
            return $regla['default'] ?? null;
        }

        return match ($regla['tipo']) {
            'numero', 'relacion' => (int) $valor,
            'decimal'            => (float) $valor,
            'check'              => (int) (bool) $valor,
            'enum'               => in_array($valor, $regla['opciones'] ?? [], true) ? $valor : ($regla['default'] ?? null),
            'fecha'              => preg_replace('/[^0-9\-]/', '', (string) $valor) ?: null,
            default              => (string) $valor
        };
    }

    /**
     * Traduce errores de MySQL a algo legible.
     */
    private function errorLegible(PDOException $e): string
    {
        $codigo = $e->getCode();

        return match ($codigo) {
            '23000' => 'Ya existe un registro con esos datos.',
            '23001' => 'No se puede eliminar: hay registros que lo usan.',
            default => 'Error al guardar: ' . $e->getMessage()
        };
    }

    private function columnas(string $tabla): array
    {
        if (!preg_match('/^[a-z_]+$/', $tabla)) {
            return [];
        }

        return $this->conexion
            ->query("SHOW COLUMNS FROM $tabla")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    private function alias(string $tabla): string
    {
        return substr($tabla, 0, 1);
    }

    /**
     * Ajusta el ORDER BY según el alias de la tabla.
     */
    private function ajustarOrden(string $orden, string $alias): string
    {
        $orden = trim($orden);

        // Orden por un campo de la tabla principal
        if (!str_contains($orden, '.')) {
            return $alias . '.' . $orden;
        }

        // Orden por un campo de una tabla unida (p. ej. c.nombre)
        return preg_replace('/^([a-z])\./', '$1.', $orden);
    }
}
