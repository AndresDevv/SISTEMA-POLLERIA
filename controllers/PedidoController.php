<?php

require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../config/database.php';

class PedidoController
{
    private Pedido $pedidoModel;
    private Mesa $mesaModel;
    private Venta $ventaModel;
    private PDO $db;

    public function __construct()
    {
        $this->pedidoModel = new Pedido();
        $this->mesaModel = new Mesa();
        $this->ventaModel = new Venta();
        $this->db = conexionDB();
    }

    /**
     * Lee el cuerpo de la petición.
     * Acepta JSON y también application/x-www-form-urlencoded, que es lo que
     * envía jQuery cuando se usa $.post con un objeto.
     */
    private function cuerpo(): array
    {
        $crudo = file_get_contents('php://input') ?: '';

        if ($crudo !== '') {
            $json = json_decode($crudo, true);

            if (is_array($json)) {
                return $json;
            }
        }

        // Fallback: formulario (también cubre ?items[]=1&items[]=2)
        $datos = $_POST;

        return is_array($datos) ? $datos : [];
    }

    /**
     * Recibe el pedido del modal y lo guarda.
     * Espera: { mesa_id, items: [{id, cantidad}] }
     */
    public function crear(): void
    {
        $datos  = $this->cuerpo();
        $mesaId = (int) ($datos['mesa_id'] ?? 0);
        $items  = $datos['items'] ?? [];

        if ($mesaId <= 0) {
            $this->responder(['success' => false, 'message' => 'No se indicó la mesa.'], 422);
        }

        if (!$this->mesaModel->porId($mesaId)) {
            $this->responder(['success' => false, 'message' => 'La mesa no existe.'], 404);
        }

        $items = $this->normalizarItems($items);

        if (!$items) {
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
        $datos  = $this->cuerpo();
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
     * Reemplaza los productos de un pedido existente.
     */
    public function actualizar(): void
    {
        $datos = $this->cuerpo();
        $id    = (int) ($datos['id'] ?? 0);
        $items = $this->normalizarItems($datos['items'] ?? []);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Pedido no indicado.'], 422);
        }

        if (!$items) {
            $this->responder([
                'success' => false,
                'message' => 'El pedido debe tener al menos un producto.'
            ], 422);
        }

        $pedido = $this->pedidoModel->porId($id);

        if (!$pedido) {
            $this->responder(['success' => false, 'message' => 'El pedido no existe.'], 404);
        }

        if ($pedido['estado'] === 'entregado') {
            $this->responder([
                'success' => false,
                'message' => 'No se puede editar un pedido ya servido.'
            ], 422);
        }

        $ok = $this->pedidoModel->actualizar($id, $items);

        $this->responder(
            $ok
                ? ['success' => true, 'message' => 'Pedido actualizado.']
                : ['success' => false, 'message' => 'No se pudo actualizar el pedido.'],
            $ok ? 200 : 422
        );
    }

    /**
     * Elimina un pedido y sus detalles.
     */
    public function eliminar(): void
    {
        $datos = $this->cuerpo();
        $id    = (int) ($datos['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Pedido no indicado.'], 422);
        }

        $pedido = $this->pedidoModel->porId($id);

        if (!$pedido) {
            $this->responder(['success' => false, 'message' => 'El pedido no existe.'], 404);
        }

        $ok = $this->pedidoModel->eliminar($id);

        $this->responder(
            $ok
                ? ['success' => true, 'message' => 'Pedido eliminado.']
                : ['success' => false, 'message' => 'No se pudo eliminar el pedido.'],
            $ok ? 200 : 422
        );
    }

    /**
     * Cobra un pedido servido: registra la venta y libera la mesa.
     */
    public function cobrar(): void
    {
        $datos = $this->cuerpo();
        $id    = (int) ($datos['id'] ?? 0);
        $pagos = $datos['pagos'] ?? [];

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Pedido no indicado.'], 422);
        }

        $pedido = $this->pedidoModel->porId($id);

        if (!$pedido) {
            $this->responder(['success' => false, 'message' => 'El pedido no existe.'], 404);
        }

        if ($this->ventaModel->yaCobrado($id)) {
            $this->responder(['success' => false, 'message' => 'Este pedido ya fue cobrado.'], 422);
        }

        // Se acepta un solo método o varios (pago partido)
        if (isset($pagos['metodo_pago_id'])) {
            $pagos = [$pagos];
        }

        $ok = $this->pedidoModel->cobrar(
            $id,
            (int) $_SESSION['usuario_id'],
            is_array($pagos) ? $pagos : []
        );

        $this->responder(
            $ok
                ? ['success' => true, 'message' => 'Venta registrada y mesa liberada.']
                : ['success' => false, 'message' => 'Los montos no coinciden con el total del pedido.'],
            $ok ? 200 : 422
        );
    }

    /**
     * Devuelve los pedidos de una mesa, para mostrarlos en el modal.
     */
    /**
     * Huella de los pedidos activos.
     *
     * El mesero no tiene un botón de refrescar, así que compara este resumen
     * con el que tenía al cargar la página. Si cocina cambió un estado, la
     * huella cambia y la pantalla se recarga sola.
     */
public function resumen(): void
    {
        $stmt = $this->db->query(
            "SELECT id, estado, total
             FROM pedidos
             WHERE estado IN ('pendiente','preparando','preparado')
             ORDER BY id"
        );

        $pedidos = array_map(static function (array $fila): array {
            return [
                'id'     => (int) $fila['id'],
                'estado' => $fila['estado'],
                'total'  => (float) $fila['total']
            ];
        }, $stmt->fetchAll());

        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM ventas WHERE estado = 'pagada'"
        );

        $this->responder([
            'success' => true,
            'huella'  => md5(json_encode([$pedidos, (int) $stmt->fetchColumn()])),
            'total'   => count($pedidos)
        ]);
    }

    public function listar(): void
    {
        $mesaId = (int) ($_GET['mesa_id'] ?? 0);

        if ($mesaId <= 0 || !$this->mesaModel->porId($mesaId)) {
            $this->responder(['success' => false, 'pedidos' => []], 200);
        }

        $pedidos = [];

        foreach ($this->pedidoModel->porMesa($mesaId) as $pedido) {

            $detalle = $this->pedidoModel->conDetalle((int) $pedido['id']);
            $estado  = $this->claseEstado((string) $pedido['estado']);

            $pedidos[] = [
                'id'              => (int) $pedido['id'],
                'estado'          => $pedido['estado'],
                'estado_etiqueta' => $estado['etiqueta'],
                'estado_clase'    => $this->claseMesaEstado($pedido['estado']),
                'total'           => (float) $pedido['total'],
                'items'           => $detalle['items']
            ];
        }

        $this->responder(['success' => true, 'pedidos' => $pedidos]);
    }

    /**
     * Etiqueta legible de un estado de pedido.
     */
    private function claseEstado(string $estado): array
    {
        return match ($estado) {
            'preparando' => ['etiqueta' => 'En cocina'],
            'preparado'  => ['etiqueta' => 'Listo'],
            'entregado'  => ['etiqueta' => 'Servido'],
            default      => ['etiqueta' => 'Pendiente']
        };
    }

    /**
     * Estado visual de la tarjeta (reutiliza los colores de mesa).
     */
    private function claseMesaEstado(string $estado): string
    {
        return match ($estado) {
            'preparando' => 'ocupada',
            'preparado'  => 'libre',
            'entregado'  => 'libre',
            default      => 'reservada'
        };
    }

    /**
     * Normaliza los items que llegan del modal.
     */
    private function normalizarItems($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $limpios = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id       = (int) ($item['id'] ?? 0);
            $cantidad = (float) ($item['cantidad'] ?? 0);

            if ($id > 0 && $cantidad > 0) {
                $limpios[] = ['id' => $id, 'cantidad' => $cantidad];
            }
        }

        return $limpios;
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
