<?php

require_once __DIR__ . '/../models/Gestion.php';

/**
 * API CRUD genérica de los módulos administrativos.
 */
class GestionController
{
    private Gestion $gestion;

    public function __construct()
    {
        $this->gestion = new Gestion();
    }

    /**
     * Acepta JSON y formulario.
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

        return is_array($_POST) ? $_POST : [];
    }

    private function recurso(): ?string
    {
        $datos = $this->cuerpo();
        $query = $_GET;

        $recurso = $datos['recurso'] ?? $query['recurso'] ?? null;

        return is_string($recurso) && Gestion::recurso($recurso) ? $recurso : null;
    }

    public function listar(): void
    {
        $recurso = $this->recurso();

        if (!$recurso) {
            $this->responder(['success' => false, 'filas' => []], 422);
        }

        $buscar = $_GET['buscar'] ?? '';

        $this->responder([
            'success' => true,
            'filas'   => $this->gestion->listar($recurso, is_string($buscar) ? $buscar : '')
        ]);
    }

    /**
     * Opciones para poblar los campos tipo relación.
     */
    public function opciones(): void
    {
        $tabla = $_GET['tabla'] ?? '';

        if (!is_string($tabla) || !preg_match('/^[a-z_]+$/', $tabla)) {
            $this->responder(['success' => false, 'filas' => []], 422);
        }

        $this->responder([
            'success' => true,
            'filas'   => $this->gestion->opciones($tabla)
        ]);
    }

    public function crear(): void
    {
        $recurso = $this->recurso();

        if (!$recurso) {
            $this->responder(['success' => false, 'message' => 'Recurso desconocido.'], 422);
        }

        $resultado = $this->gestion->crear($recurso, $this->cuerpo());

        $this->responder($resultado, $resultado['success'] ? 201 : 422);
    }

    public function actualizar(): void
    {
        $recurso = $this->recurso();

        if (!$recurso) {
            $this->responder(['success' => false, 'message' => 'Recurso desconocido.'], 422);
        }

        $id = (int) ($this->cuerpo()['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Registro no indicado.'], 422);
        }

        $resultado = $this->gestion->actualizar($recurso, $id, $this->cuerpo());

        $this->responder($resultado, $resultado['success'] ? 200 : 422);
    }

    public function eliminar(): void
    {
        $recurso = $this->recurso();

        if (!$recurso) {
            $this->responder(['success' => false, 'message' => 'Recurso desconocido.'], 422);
        }

        $id = (int) ($this->cuerpo()['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['success' => false, 'message' => 'Registro no indicado.'], 422);
        }

        $resultado = $this->gestion->eliminar($recurso, $id);

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
