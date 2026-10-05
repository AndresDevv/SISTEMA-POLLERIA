<?php

require_once __DIR__ . '/../models/Mesa.php';

/**
 * API de administración de mesas: reservar, agregar y eliminar.
 */
class MesaController
{
    private Mesa $mesaModel;

    public function __construct()
    {
        $this->mesaModel = new Mesa();
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
     * Reserva una mesa (pasa a amarillo).
     */
    public function reservar(): void
    {
        $datos = $this->cuerpo();
        $id    = (int) ($datos['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Mesa no indicada.'], 422);
        }

        if (!$this->mesaModel->porId($id)) {
            $this->responder(['success' => false, 'message' => 'La mesa no existe.'], 404);
        }

        $ok = $this->mesaModel->reservar(
            $id,
            trim((string) ($datos['nombre'] ?? '')),
            trim((string) ($datos['hora'] ?? ''))
        );

        $this->responder(
            $ok
                ? ['success' => true, 'message' => 'Mesa reservada.']
                : ['success' => false, 'message' => 'No se pudo reservar la mesa.'],
            $ok ? 200 : 422
        );
    }

    /**
     * Libera una reserva o una mesa ocupada.
     */
    public function liberar(): void
    {
        $datos = $this->cuerpo();
        $id    = (int) ($datos['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Mesa no indicada.'], 422);
        }

        $ok = $this->mesaModel->cambiarEstado($id, 'libre');

        $this->responder(
            $ok
                ? ['success' => true, 'message' => 'Mesa liberada.']
                : ['success' => false, 'message' => 'No se pudo liberar la mesa.'],
            $ok ? 200 : 422
        );
    }

    /**
     * Agrega una mesa nueva.
     */
    public function crear(): void
    {
        $datos     = $this->cuerpo();
        $capacidad = (int) ($datos['capacidad'] ?? 4);

        $resultado = $this->mesaModel->crear($capacidad);

        $this->responder($resultado, $resultado['success'] ? 201 : 422);
    }

    /**
     * Elimina una mesa.
     */
    public function eliminar(): void
    {
        $datos = $this->cuerpo();
        $id    = (int) ($datos['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Mesa no indicada.'], 422);
        }

        $resultado = $this->mesaModel->eliminar($id);

        $this->responder($resultado, $resultado['success'] ? 200 : 422);
    }

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}