<?php

require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Mesa.php';

class PedidoController
{
    private Pedido $pedidoModel;
    private Mesa $mesaModel;

    public function __construct()
    {
        $this->pedidoModel = new Pedido();
        $this->mesaModel = new Mesa();
    }

    /**
     * Recibe el pedido del modal y lo guarda.
     * Espera un JSON en el cuerpo: { mesa_id, items: [{id, cantidad}] }
     */
    public function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input') ?: '[]', true);

        $mesaId = (int) ($datos['mesa_id'] ?? 0);
        $items  = $datos['items'] ?? [];

        if ($mesaId <= 0) {
            $this->responder(['success' => false, 'message' => 'No se indicó la mesa.'], 422);
        }

        if (!$this->mesaModel->porId($mesaId)) {
            $this->responder(['success' => false, 'message' => 'La mesa no existe.'], 404);
        }

        if (!is_array($items) || !$items) {
            $this->responder(['success' => false, 'message' => 'Agrega al menos un producto.'], 422);
        }

        $resultado = $this->pedidoModel->crear(
            $mesaId,
            (int) $_SESSION['usuario_id'],
            $items
        );

        $this->responder($resultado, $resultado['success'] ? 201 : 422);
    }

    /**
     * Cambia el estado de un pedido.
     */
    public function cambiarEstado(): void
    {
        $datos = json_decode(file_get_contents('php://input') ?: '[]', true);

        $id     = (int) ($datos['id'] ?? 0);
        $estado = (string) ($datos['estado'] ?? '');

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Pedido no indicado.'], 422);
        }

        $ok = $this->pedidoModel->cambiarEstado($id, $estado);

        $this->responder(
            $ok
                ? ['success' => true]
                : ['success' => false, 'message' => 'Estado no permitido.'],
            $ok ? 200 : 422
        );
    }

    /**
     * Devuelve una respuesta JSON y termina.
     */
    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
