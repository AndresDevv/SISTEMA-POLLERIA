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
}

?>
