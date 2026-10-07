<?php

require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/Personal.php';

/**
 * API de los módulos operativos: compras, inventario y personal.
 */
class OperacionController
{
    private Compra $compraModel;
    private Inventario $inventarioModel;
    private Personal $personalModel;

    public function __construct()
    {
        $this->compraModel     = new Compra();
        $this->inventarioModel = new Inventario();
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
     * Registra una compra con sus productos.
     */
    public function compraRegistrar(): void
    {
        $datos = $this->cuerpo();

        $resultado = $this->compraModel->registrar(
            (int) ($datos['proveedor_id'] ?? 0),
            $datos['items'] ?? []
        );

        $this->responder($resultado, $resultado['success'] ? 201 : 422);
    }

    /**
     * Detalle de una compra: encabezado, ítems y si sigue contabilizada
     * en los gastos del día.
 */
public function compraDetalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Compra no indicada.'], 422);
        }

        $compra = $this->compraModel->conDetalle($id);

        if (!$compra) {
            $this->responder(['success' => false, 'message' => 'La compra no existe.'], 404);
        }

        $this->responder([
            'success' => true,
            'compra'  => $compra,
            'anulada' => $compra['estado'] === 'anulada'
        ]);
    }

    /**
     * Anula una compra: revierte el stock y el gasto del día.
     */
    public function compraAnular(): void
    {
        $id = (int) ($this->cuerpo()['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Compra no indicada.'], 422);
        }

        $resultado = $this->compraModel->anular($id);

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
     * Registra entrada o salida de un colaborador.
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
     * Registra el pago de un colaborador.
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