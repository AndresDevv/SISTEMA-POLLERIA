<?php

require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function iniciarSesion(string $usuario, string $password): array
    {
        $usuarioEncontrado = $this->usuarioModel->buscarPorUsuario($usuario);

        if (!$usuarioEncontrado) {
            return [
                'success' => false,
                'message' => 'El usuario o la contraseña son incorrectos.'
            ];
        }

        if ((int) $usuarioEncontrado['estado'] !== 1) {
            return [
                'success' => false,
                'message' => 'El usuario se encuentra desactivado.'
            ];
        }

        if (!password_verify($password, $usuarioEncontrado['password'])) {
            return [
                'success' => false,
                'message' => 'El usuario o la contraseña son incorrectos.'
            ];
        }

        return [
            'success' => true,
            'usuario' => $usuarioEncontrado
        ];
    }

    /**
     * Guarda los datos del usuario en la sesión.
     */
    public function crearSesion(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre']     = $usuario['nombre'];
        $_SESSION['usuario']    = $usuario['usuario'];
        $_SESSION['rol_id']     = $usuario['rol_id'];
        $_SESSION['rol']        = $usuario['rol'];

        $_SESSION['login_time'] = time();
    }
}

?>
