<?php

require_once __DIR__ . '/../models/Gestion.php';
require_once __DIR__ . '/../models/Permiso.php';

/**
 * API CRUD genérica de los módulos administrativos.
 *
 * Cada recurso tiene su propio permiso (ver / editar), porque las rutas
 * api/gestion/* son las mismas para todos los módulos y un solo permiso
 * dejaría pasar escrituras, por ejemplo, de un mesero en finanzas.
 */
class GestionController
{
    private Gestion $gestion;

    private ?array $cuerpo = null;

    public function __construct()
    {
        $this->gestion = new Gestion();
    }

    /**
     * Acepta JSON y formulario.
     *
     * php://input solo se puede leer una vez, así que el cuerpo se guarda:
     * recurso() y crear() lo necesitan los dos.
     */
    private function cuerpo(): array
    {
        if ($this->cuerpo !== null) {
            return $this->cuerpo;
        }

        $crudo = file_get_contents('php://input') ?: '';

        if ($crudo !== '') {
            $json = json_decode($crudo, true);

            if (is_array($json)) {
                return $this->cuerpo = $json;
            }
        }

        return $this->cuerpo = is_array($_POST) ? $_POST : [];
    }

    private function recurso(): ?string
    {
        $datos = $this->cuerpo();

        $recurso = $datos['recurso'] ?? $_GET['recurso'] ?? null;

        return is_string($recurso) && Gestion::recurso($recurso) ? $recurso : null;
    }

    /**
     * ¿El usuario en sesión tiene el permiso de este recurso?
     */
    private function permite(string $recurso, string $accion): bool
    {
        return Gestion::puede($recurso, $accion);
    }

    /**
     * Corta la petición con 403 si falta el permiso.
     */
    private function exigir(string $recurso, string $accion): void
    {
        if ($this->permite($recurso, $accion)) {
            return;
        }

        $this->responder([
            'success' => false,
            'message' => 'No tienes permiso para esta acción.'
        ], 403);
    }

    public function listar(): void
    {
        $recurso = $this->recurso();

        if (!$recurso) {
            $this->responder(['success' => false, 'filas' => []], 422);
        }

        $this->exigir($recurso, 'ver');

        $buscar = $_GET['buscar'] ?? '';

        $this->responder([
            'success' => true,
            'filas'   => $this->gestion->listar($recurso, is_string($buscar) ? $buscar : '')
        ]);
    }

    /**
     * Opciones para poblar los campos tipo relación.
     * Solo expone tablas de recursos que el usuario puede ver.
     */
    public function opciones(): void
    {
        $tabla = $_GET['tabla'] ?? '';

        if (!is_string($tabla) || !preg_match('/^[a-z_]+$/', $tabla)) {
            $this->responder(['success' => false, 'filas' => []], 422);
        }

        $permitida = false;

        foreach (Gestion::recursosConPermiso() as $recurso) {

            $def = Gestion::recurso($recurso);

            if (($def['tabla'] ?? '') === $tabla && $this->permite($recurso, 'ver')) {
                $permitida = true;
                break;
            }
        }

        if (!$permitida) {
            $this->responder([
                'success' => false,
                'message' => 'No tienes permiso para esta acción.'
            ], 403);
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

        $this->exigir($recurso, 'editar');

        $resultado = $this->gestion->crear($recurso, $this->cuerpo());

        $this->responder($resultado, $resultado['success'] ? 201 : 422);
    }

    public function actualizar(): void
    {
        $recurso = $this->recurso();

        if (!$recurso) {
            $this->responder(['success' => false, 'message' => 'Recurso desconocido.'], 422);
        }

        $this->exigir($recurso, 'editar');

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

        $this->exigir($recurso, 'editar');

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