<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Permisos del usuario en sesión.
 *
 * Los permisos viven en las tablas permisos / rol_permiso, así que se pueden
 * cambiar desde Configuración sin tocar código. Si esas tablas están vacías,
 * se cae a un mapa de respaldo para que el sistema no se quede sin acceso.
 */
class Permiso
{
    private static ?array $cache = null;

    /**
     * Mapa de respaldo usado solo si la base no tiene permisos cargados.
     */
    public static function mapaRespaldo(): array
    {
        return [
            'Administrador' => ['*'],
            'Mesero' => ['mesas', 'mesas_reservar', 'pedidos_ver', 'pedidos_crear', 'cobrar', 'inventario'],
            'Cocina' => ['mesas', 'pedidos_ver', 'pedidos_estado', 'inventario']
        ];
    }

    /**
     * Permisos del rol del usuario en sesión.
     */
    public static function delSesion(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rolId = (int) ($_SESSION['rol_id'] ?? 0);
        $rol   = (string) ($_SESSION['rol'] ?? '');

        if ($rolId <= 0) {
            return self::$cache = [];
        }

        try {
            $db = conexionDB();

            $stmt = $db->prepare(
                "SELECT p.nombre
                 FROM rol_permiso rp
                 INNER JOIN permisos p ON p.id = rp.permiso_id
                 WHERE rp.rol_id = :rol"
            );
            $stmt->execute([':rol' => $rolId]);

            $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            $permisos = [];
        }

        // Base vacía: usamos el mapa de respaldo
        if (!$permisos) {
            $mapa = self::mapaRespaldo();
            $permisos = $mapa[$rol] ?? ['*'];
        }

        return self::$cache = $permisos;
    }

    /**
     * ¿El usuario tiene ese permiso?
     * El asterisco concede todo.
     */
    public static function puede(string $permiso): bool
    {
        $permisos = self::delSesion();

        return in_array('*', $permisos, true) || in_array($permiso, $permisos, true);
    }

    /**
     * ¿Tiene alguno de estos permisos?
     */
    public static function puedeAlguno(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (self::puede($permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vacía la caché (tras cambiar permisos desde Configuración).
     */
    public static function limpiarCache(): void
    {
        self::$cache = null;
    }
}