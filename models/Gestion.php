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

                    /**
                     * El stock solo se escribe al crear el producto.
                     *
                     * Una vez que existe, la unica forma de moverlo es el boton
                     * Ajustar, que deja el movimiento anotado en el historial.
                     * Si tambien se pudiera escribir aqui, el stock se moveria
                     * sin dejar rastro y habria dos caminos para lo mismo: uno
                     * que deja historial y otro que no.
                     */
                    'stock'         => ['tipo' => 'decimal', 'etiqueta' => 'Stock inicial', 'default' => 0, 'soloNuevo' => true],
                    'stock_minimo'  => ['tipo' => 'decimal', 'etiqueta' => 'Stock mínimo', 'default' => 0],
                    'estado'        => ['tipo' => 'check', 'etiqueta' => 'Activo', 'default' => 1]
                ],
                'listado' => 'p.id, p.nombre, c.nombre AS relacion, p.precio, p.stock, p.stock_minimo, p.estado',
                'join'    => 'INNER JOIN categorias c ON c.id = p.categoria_id'
            ],

            'categorias' => [
                'tabla'  => 'categorias',
                'titulo' => 'Categoría',
                'tituloPlural' => 'Categorías',
                'modulo' => 'inventario',
                'orden'  => 'nombre',
                'campos' => [
                    'nombre'      => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'requerido' => true],
                    'descripcion' => ['tipo' => 'texto', 'etiqueta' => 'Descripción'],
                    'estado'      => ['tipo' => 'check', 'etiqueta' => 'Activa', 'default' => 1]
                ],
                'listado' => '*'
            ],

            'empleados' => [
                'tabla'  => 'empleados',
                'titulo' => 'Trabajador',
                'tituloPlural' => 'Trabajadores',
                'modulo' => 'personal',
                'orden'  => 'e.nombre',
                'campos' => [
                    'nombre'        => ['tipo' => 'texto', 'etiqueta' => 'Nombre y apellidos', 'requerido' => true],
                    'cargo'         => ['tipo' => 'enum', 'etiqueta' => 'Cargo', 'requerido' => true,
                                         'opciones' => ['mesero', 'cocinero', 'cajero', 'administrador'],
                                         'default'  => 'mesero'],
                    'dni'           => ['tipo' => 'digitos', 'etiqueta' => 'DNI', 'digitos' => 8, 'requerido' => true],
                    'telefono'      => ['tipo' => 'digitos', 'etiqueta' => 'Teléfono', 'digitos' => 9],
                    'direccion'     => ['tipo' => 'texto', 'etiqueta' => 'Dirección'],
                    'fecha_ingreso' => ['tipo' => 'fecha', 'etiqueta' => 'Fecha de ingreso'],
                    'usuario_id'    => ['tipo' => 'relacion', 'etiqueta' => 'Usuario de acceso (opcional)',
                                         'tabla' => 'usuarios'],
                    'estado'        => ['tipo' => 'check', 'etiqueta' => 'Activo', 'default' => 1]
                ],
                'listado' => 'e.id, e.nombre, e.cargo, e.dni, e.telefono, e.direccion, e.fecha_ingreso, e.estado',
                'join'    => ''
            ],

            'usuarios' => [
                'tabla'  => 'usuarios',
                'titulo' => 'Usuario',
                'tituloPlural' => 'Usuarios',
                'modulo' => 'configuracion',
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
                'tituloPlural' => 'Servicios',
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

            /**
             * Compras de insumos: arroz, aceite, huevos y similares.
             *
             * No hay catálogo ni control de existencias a propósito. Cada
             * registro es una compra suelta con su producto, su cantidad y su
             * precio; si el insumo se acaba, se vuelve a registrar otro. El
             * total se arma solo con cantidad por precio, para que el número
             * que sale en la tabla sea siempre el mismo que ve el resto del
             * sistema al sumar los egresos del día.
             */
            'compras' => [
                'tabla'  => 'egresos',
                'titulo' => 'Compra',
                'tituloPlural' => 'Compras',
                'modulo' => 'finanzas',
                'orden'  => 'e.fecha DESC, e.id DESC',
                'campos' => [
                    'concepto'        => ['tipo' => 'texto', 'etiqueta' => 'Producto', 'requerido' => true],
                    'cantidad'        => ['tipo' => 'decimal', 'etiqueta' => 'Cantidad', 'requerido' => true, 'default' => 1, 'decimales' => true],
                    'unidad'          => [
                        'tipo'        => 'texto',
                        'etiqueta'    => 'Unidad de medida',
                        'requerido'   => false,
                        'placeholder' => 'Ej: kg, litros, cajas...',
                        'ayuda'       => 'Escribe la palabra de la medida (kg, litros, cajas), no un número. La cantidad ya va en el campo de arriba.'
                    ],
                    'precio_unitario' => ['tipo' => 'decimal', 'etiqueta' => 'Precio unitario', 'requerido' => true, 'default' => 0],
                    'monto'           => ['tipo' => 'calculado', 'etiqueta' => 'Total'],
                    'fecha'           => ['tipo' => 'fecha', 'etiqueta' => 'Fecha']
                ],
                'listado' => 'e.id, e.concepto, e.cantidad, e.unidad, e.precio_unitario, e.monto, e.fecha',
                'fechaDefecto' => 'CURDATE()',
                'calculoTotal' => ['campo' => 'monto', 'cantidad' => 'cantidad', 'precio' => 'precio_unitario']
            ],

            'ingresos' => [
                'tabla'  => 'ingresos',
                'titulo' => 'Ingreso',
                'tituloPlural' => 'Ingresos',
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

            'configuracion_negocio' => [
                'tabla'  => 'configuracion_negocio',
                'titulo' => 'Configuración',
                'tituloPlural' => 'Configuración',
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
     * Permisos que pide cada recurso: 'ver' para listarlo,
     * 'editar' para crear, modificar o eliminar.
     *
     * Las rutas api/gestion/* son las mismas para todos los módulos, así que
     * el permiso se resuelve por recurso y no por URL.
     */
    public static function permisos(string $nombre): array
    {
        return self::mapaPermisos()[$nombre] ?? [];
    }

    /**
     * Nombres de los recursos que tienen permiso asignado.
     */
    public static function recursosConPermiso(): array
    {
        return array_keys(self::mapaPermisos());
    }

    /**
     * Mapa completo de permisos por recurso.
     */
    private static function mapaPermisos(): array
    {
        return [
            'productos'             => ['ver' => 'inventario',    'editar' => 'inventario_editar'],
            'categorias'            => ['ver' => 'inventario',    'editar' => 'inventario_editar'],
            'empleados'             => ['ver' => 'personal',      'editar' => 'personal'],
            'usuarios'              => ['ver' => 'configuracion', 'editar' => 'configuracion'],
            'servicios'             => ['ver' => 'servicios',     'editar' => 'servicios'],
            'ingresos'              => ['ver' => 'finanzas',      'editar' => 'finanzas'],
            'compras'               => ['ver' => 'finanzas',      'editar' => 'finanzas'],
            'configuracion_negocio' => ['ver' => 'configuracion', 'editar' => 'configuracion']
        ];
    }

    /**
     * ¿El usuario en sesión puede ver / modificar este recurso?
     * Un recurso sin permiso declarado queda reservado al administrador.
     */
    public static function puede(string $nombre, string $accion = 'ver'): bool
    {
        $reglas = self::permisos($nombre);

        if (!$reglas) {
            return false;
        }

        return \Permiso::puede($reglas[$accion] ?? $reglas['ver']);
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
     * Rellena los campos que se calculan solos.
     *
     * El total de una compra es cantidad por precio unitario. Se calcula
     * siempre en el servidor y no se confía en el número que manda el
     * navegador: si el total fuera un campo editable, alguien podría
     * guardarlo distinto del producto y las cifras de Caja y del Dashboard
     * dejarían de cuadrar con la tabla.
     *
     * @param array $def    definición del recurso
     * @param array $datos  datos que llegan del formulario
     *
     * @return array los datos de entrada con los calculados ya puestos
     */
    private function resolverCalculados(array $def, array $datos): array
    {
        if (($def['calculoTotal'] ?? '') === '') {
            return $datos;
        }

        $regla = $def['calculoTotal'];

        if (!isset($def['campos'][$regla['campo']])) {
            return $datos;
        }

        $cantidad = (float) ($datos[$regla['cantidad']] ?? 0);
        $precio   = (float) ($datos[$regla['precio']] ?? 0);

        $datos[$regla['campo']] = round($cantidad * $precio, 2);

        return $datos;
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

        $datos = $this->resolverCalculados($def, $datos);

        $campos = [];
        $valores = [];

        foreach ($def['campos'] as $campo => $regla) {
            $valor = $this->valor($regla, $datos[$campo] ?? null);

            if ($valor === null && !empty($regla['requerido'])) {
                return ['success' => false, 'message' => 'El campo "' . $regla['etiqueta'] . '" es obligatorio.'];
            }

            $errorDigitos = $this->errorDeDigitos($regla, $datos[$campo] ?? '');

            if ($errorDigitos !== null) {
                return ['success' => false, 'message' => $errorDigitos];
            }

            $errorEnum = $this->errorDeEnum($regla, $datos[$campo] ?? '');

            if ($errorEnum !== null) {
                return ['success' => false, 'message' => $errorEnum];
            }

            $campos[] = $campo;
            $valores[] = $valor;
        }

        // Fecha por defecto.
        // Solo si el recurso no la trae ya entre sus campos: si se declaro
        // como campo, el bucle de arriba ya la guardo y meterla otra vez
        // rompe el INSERT con "Column 'fecha' specified twice".
        /**
         * Fecha por defecto.
         *
         * El campo fecha puede venir declarado entre los campos del recurso y
         * aun asi quedar en blanco en el formulario. Si ya esta en la lista,
         * se rellona su valor en el sitio en vez de agregarla otra vez, que
         * romperia el INSERT con "Column 'fecha' specified twice".
         *
         * El valor por defecto se resuelve aqui y no se manda a MySQL porque
         * va como un parametro normal: si se enviara el texto "CURDATE()",
         * la base lo guardaria tal cual y la fecha saldria en cero.
         */
        if (!empty($def['fechaDefecto']) && empty($datos['fecha'])) {

            $defecto = $this->fechaPorDefecto($def['fechaDefecto']);

            if (in_array('fecha', $campos, true)) {
                $valores[array_search('fecha', $campos, true)] = $defecto;

            } else {
                $campos[] = 'fecha';
                $valores[] = $defecto;
            }
        }

        // Usuario que registra, salvo que el recurso ya lo declare como campo
        // (en empleados, usuario_id es justamente el trabajador que se elige)
        if (!in_array('usuario_id', $campos, true)
            && in_array('usuario_id', $this->columnas($def['tabla']), true)) {
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

        $datos = $this->resolverCalculados($def, $datos);

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

            $errorDigitos = $this->errorDeDigitos($regla, $datos[$campo]);

            if ($errorDigitos !== null) {
                return ['success' => false, 'message' => $errorDigitos];
            }

            $errorEnum = $this->errorDeEnum($regla, $datos[$campo]);

            if ($errorEnum !== null) {
                return ['success' => false, 'message' => $errorEnum];
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

            /* Un campo obligatorio nunca cae al valor de arranque.
               El "default" sirve para que el formulario abra con un
               número puesto (cantidad 1, precio 0), no para colar un
               registro incompleto: si el campo es obligatorio y llega
               vacío, se devuelve null y quien valida lo rechaza. */
            if (!empty($regla['requerido'])) {
                return null;
            }

            return $regla['default'] ?? null;
        }

        return match ($regla['tipo']) {
            'numero', 'relacion' => (int) $valor,
            'decimal', 'calculado' => (float) $valor,
            'check'              => (int) (bool) $valor,
            'enum'               => in_array($valor, $regla['opciones'] ?? [], true) ? $valor : ($regla['default'] ?? null),
            'fecha'              => preg_replace('/[^0-9\-]/', '', (string) $valor) ?: null,
            // Se queda solo con los dígitos: ni letras ni símbolos sobreviven
            'digitos'            => preg_replace('/\D/', '', (string) $valor),
            default              => (string) $valor
        };
    }

    /**
     * Revisa que un campo de solo dígitos tenga el largo exacto.
     *
     * @return string|null mensaje de error, o null si está bien
     */
    private function errorDeDigitos(array $regla, $valor): ?string
    {
        if (empty($regla['digitos'])) {
            return null;
        }

        $solo = preg_replace('/\D/', '', (string) $valor);

        // Vacío solo es problema si el campo es obligatorio
        if ($solo === '') {
            return null;
        }

        $largo = strlen($solo);

        if ($largo !== (int) $regla['digitos']) {
            return $regla['etiqueta'] . ' debe tener ' . $regla['digitos']
                . ' dígitos (escribiste ' . $largo . ').';
        }

        return null;
    }

    /**
     * Revisa que un enum venga de la lista de opciones.
     *
     * Antes un valor desconocido caía al valor por defecto en silencio, así
     * que un cargo "gerente" se guardaba como "mesero" sin avisar nada. Es
     * mejor rechazar y decir qué opciones hay.
     *
     * @return string|null mensaje de error, o null si está bien
     */
    private function errorDeEnum(array $regla, $valor): ?string
    {
        if (($regla['tipo'] ?? '') !== 'enum' || $valor === null || $valor === '') {
            return null;
        }

        $opciones = $regla['opciones'] ?? [];

        if (in_array($valor, $opciones, true)) {
            return null;
        }

        return '"' . $regla['etiqueta'] . '" no es válido. '
            . 'Elige entre: ' . implode(', ', array_map('ucfirst', $opciones)) . '.';
    }

    /**
     * Convierte la fecha por defecto de un recurso en una fecha de verdad.
     *
     * Los recursos la declaran como texto de MySQL ("CURDATE()") porque asi
     * se lee mejor en la definición, pero como el INSERT manda los valores
     * como parámetros sueltos, hay que traducirla aquí: si se mandara tal
     * cual, la base guardaría el texto y la fecha quedaría en cero.
     */
    private function fechaPorDefecto(string $expresion): string
    {
        return trim(str_ireplace(
            ['CURDATE()', 'CURRENT_DATE', 'NOW()'],
            date('Y-m-d H:i:s'),
            $expresion
        ));
    }

    /**
     * Traduce errores de MySQL a algo legible.
     */
    private function errorLegible(PDOException $e): string
    {
        $codigo = $e->getCode();

        /**
         * 23000 agrupa varios fallos distintos de integridad.
         *
         * Casi todos son datos repetidos, pero no todos: una columna que se
         * manda dos veces, o una que no existe, tambien caen aqui. Si el
         * mensaje de MySQL no habla de una clave duplicada, se muestra el
         * detalle, porque si no el error real queda escondido detras de un
         * "ya existe" que no es cierto.
         */
        if ($codigo === '23000' && stripos($e->getMessage(), 'duplicate') === false) {
            return 'Error al guardar: ' . $e->getMessage();
        }

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
