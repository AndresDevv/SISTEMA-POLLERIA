<?php

require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/MenuDelDia.php';
require_once __DIR__ . '/../models/Personal.php';

/**
 * API de los módulos operativos: menú del día, inventario y personal.
 */
class OperacionController
{
    private Inventario $inventarioModel;
    private MenuDelDia $menuModel;
    private Personal $personalModel;

    public function __construct()
    {
        $this->inventarioModel = new Inventario();
        $this->menuModel       = new MenuDelDia(conexionDB());
        $this->personalModel   = new Personal();
    }

    private function cuerpo(): array
    {
        $crudo = file_get_contents('php://input') ?: '';

        if ($crudo !== '') {
            $json = json_decode($crudo, true);

            if (is_array($json)) {
                return $json;
            }
        }

        return is_array($_POST) ? $_POST : [];
    }

    /**
     * Catálogo de productos para pintar el modal de pedidos al día.
     *
     * Con un modal abierto la pantalla no se repinta, porque se perdería el
     * pedido que el mesero está armando. Pero el catálogo de platos sí
     * puede cambiar sin tocar eso, y entonces el mesero tiene que verlo al
     * instante.
     *
     * Se devuelve el HTML ya pintado, y no datos, para que el catálogo se ve
     * igual se abra el modal o se repinte en vivo sin repetir el marcado en
     * PHP y en JavaScript.
     *
     * Va con una firma: si no cambió, el navegador no repinta nada y no se
     * nota.
     */
    public function menuCatalogo(): void
    {
        $menu    = new MenuDelDia(conexionDB());
        $productos = [];

        foreach ($menu->carta() as $grupo) {
            foreach ($grupo['productos'] as $plato) {
                $productos[] = [
                    'nombre'    => $plato['nombre'],
                    'categoria' => $grupo['categoria'],
                    'precio'    => $plato['precio'],
                    'stock'     => $plato['stock'],
                    'minimo'    => $plato['minimo'] ?? 0,
                    'agotado'   => $plato['agotado'] ? 1 : 0,
                    'id'        => $plato['id'],
                ];
            }
        }

        // La firma se arma con lo que se ve, no con toda la fila: si solo
        // cambiara un campo que no se muestra, no hace falta repintar.
        $firma = md5((string) json_encode(array_map(
            static function (array $p): array {
                return [$p['id'], $p['stock'], $p['agotado']];
            },
            $productos
        )));

        $this->responder([
            'success'    => true,
            'firma'      => $firma,
            'agregados'  => array_sum(array_column($productos, 'agotado')),
            'html'       => $this->htmlCatalogo($productos),
        ]);
    }

    /**
     * Pinta el catálogo con el partial que usa también el modal.
     */
    private function htmlCatalogo(array $productosModal): string
    {
        ob_start();
        require APP_ROOT . '/views/partials/catalogo_productos.php';

        return (string) ob_get_clean();
    }

    /**
     * Marca un plato como agotado, o vuelve a ofrecerlo.
     *
     * Solo mueve la marca, no el stock: el inventario lleva la cuenta real
     * de lo que queda y esta marca es para avisar que ya no se está dando.
     */
    public function menuAgotado(): void
    {
        $datos = $this->cuerpo();

        $resultado = $this->menuModel->marcarAgotado(
            (int) ($datos['id'] ?? 0),
            !empty($datos['agotado'])
        );

        $this->responder($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Ajusta el stock de un producto.
     */
    public function inventarioAjustar(): void
    {
        $datos = $this->cuerpo();

        $resultado = $this->inventarioModel->ajustar(
            (int) ($datos['producto_id'] ?? 0),
            (string) ($datos['tipo'] ?? 'ajuste'),
            (float) ($datos['cantidad'] ?? 0),
            trim((string) ($datos['motivo'] ?? ''))
        );

        $this->responder($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Registra entrada o salida de un trabajador.
     */
    public function asistencia(): void
    {
        $datos = $this->cuerpo();

        $resultado = $this->personalModel->registrarAsistencia(
            (int) ($datos['empleado_id'] ?? 0),
            (string) ($datos['tipo'] ?? 'entrada')
        );

        $this->responder($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Registra el pago de un trabajador.
     */
    public function pago(): void
    {
        $datos = $this->cuerpo();

        $resultado = $this->personalModel->registrarPago(
            (int) ($datos['empleado_id'] ?? 0),
            trim((string) ($datos['periodo'] ?? '')),
            (float) ($datos['monto'] ?? 0),
            trim((string) ($datos['observacion'] ?? ''))
        );

        $this->responder($resultado, $resultado['success'] ? 201 : 422);
    }

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
