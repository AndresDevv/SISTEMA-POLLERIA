<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Latido del sistema.
 *
 * Todas las pantallas consultan esta ruta cada pocos segundos para saber si
 * algo cambió en la base. Si cambió, la pantalla se actualiza sola y nadie
 * tiene que recargar el navegador.
 *
 * No es un websocket: es una pregunta barata y muy corta. La firma se arma
 * con una sola consulta (conteos y última fecha de cada tabla), así que
 * aunque haya veinte personas conectadas al mismo tiempo el costo es mínimo.
 */
class PulsoController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = conexionDB();
    }

    public function pulso(): void
    {
        $firmaEnviada = (string) ($_GET['firma'] ?? '');

        try {
            $firma = $this->firma();

        } catch (PDOException $e) {
            // Si la base falla un momento, no se rompe la pantalla: se avisa
            // que no se pudo comprobar y el navegador sigue como está.
            $this->responder([
                'success' => true,
                'cambia'  => false,
                'firma'   => $firmaEnviada,
                'error'   => true,
            ]);

            return;
        }

        $this->responder([
            'success' => true,

            // Vacío = el navegador acaba de abrir la página: se le pasa la
            // firma actual para que empiece a comparar.
            'cambia'  => $firmaEnviada === '' ? false : $firmaEnviada !== $firma,
            'firma'   => $firma,
        ]);
    }

    /**
     * Huella rápida del estado del sistema.
     *
     * Se usa el conteo y la última modificación de cada tabla que mueve el
     * negocio. Con que uno solo de esos números cambie, la pantalla ya está
     * desfasada y hay que refrescarla.
     */
    private function firma(): string
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM pedidos)                                AS pedidos,
                    (SELECT COALESCE(MAX(fecha_actualizacion), '0') FROM pedidos) AS pedidos_alta,
                    (SELECT COUNT(*) FROM ventas WHERE estado = 'pagada')        AS ventas,
                    (SELECT COALESCE(MAX(fecha), '0') FROM ventas)               AS ventas_alta,
                    (SELECT COUNT(*) FROM gastos)                               AS gastos,
                    (SELECT COALESCE(MAX(fecha), '0') FROM gastos)              AS gastos_alta,
                    (SELECT COUNT(*) FROM movimientos_inventario)               AS movimientos,
                    (SELECT COALESCE(MAX(fecha), '0') FROM movimientos_inventario) AS movimientos_alta,
                    (SELECT COUNT(*) FROM mesas WHERE estado <> 'libre')       AS mesas,
                    (SELECT COUNT(*) FROM productos)                            AS productos,
                    (SELECT COALESCE(SUM(agotado), 0) FROM productos)           AS productos_agotados,
                    (SELECT COALESCE(MAX(fecha_actualizacion), '0') FROM productos) AS productos_alta,
                    (SELECT COUNT(*) FROM pedido_novedades)                     AS novedades";

        $fila = $this->db->query($sql)->fetch();

        return md5(json_encode($fila ?: []));
    }

    private function responder(array $datos): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($datos, JSON_UNESCAPED_UNICODE);

        // Corta aquí: si se sigue, el front controller acaba pintando una
        // página HTML encima del JSON y el navegador no lo puede leer.
        exit;
    }
}
