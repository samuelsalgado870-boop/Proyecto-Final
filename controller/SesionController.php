<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\AutorizacionDAO;
use App\Model\DAO\UsuarioDAO;

final class SesionController extends ApiController
{
    public function crear(array $params = []): never
    {
        $isBrowserPost = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) === 'application/x-www-form-urlencoded';
        $body = $isBrowserPost ? $_POST : $this->body();
        if ($isBrowserPost) {
            if (!is_string($body['correo'] ?? null) || trim($body['correo']) === ''
                || !is_string($body['contrasena'] ?? null) || $body['contrasena'] === '') {
                $this->redirectBrowserLogin('Ingresa tu correo y contraseña.');
            }
        }
        $correo = $this->requiredString($body, 'correo', 150);
        $contrasena = $this->requiredString($body, 'contrasena', 255);
        $usuario = (new UsuarioDAO($this->pdo))->autenticar($correo, $contrasena);
        if ($usuario === null) {
            if ($isBrowserPost) $this->redirectBrowserLogin('Correo o contraseña incorrectos.');
            throw new ApiException(401, 'CREDENTIALS_INVALID', 'Correo o contraseña incorrectos.');
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $usuario->getId();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        if ($isBrowserPost) {
            $basePath = $this->basePath();
            header('Location: ' . $basePath . '/app', true, 303);
            exit;
        }
        $permissions = (new AutorizacionDAO($this->pdo))->listarPermisos($usuario->getId());
        $this->respond(['usuario' => $usuario, 'permisos' => $permissions, 'csrfToken' => $_SESSION['csrf_token']]);
    }

    private function redirectBrowserLogin(string $message): never
    {
        $_SESSION['login_error'] = $message;
        header('Location: ' . $this->basePath() . '/login', true, 303);
        exit;
    }

    private function basePath(): string
    {
        $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        return $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
    }

    public function mostrar(array $params = []): never
    {
        $id = $this->currentUserId();
        $usuario = (new UsuarioDAO($this->pdo))->buscarPorId($id);
        if ($usuario === null || $usuario->getEstado() !== 'ACTIVO') {
            $_SESSION = [];
            session_destroy();
            throw new ApiException(401, 'SESSION_INVALID', 'La sesión ya no es válida.');
        }
        $this->respond([
            'usuario' => $usuario,
            'permisos' => (new AutorizacionDAO($this->pdo))->listarPermisos($id),
            'csrfToken' => $_SESSION['csrf_token'] ?? null,
        ]);
    }

    public function cerrar(array $params = []): never
    {
        $this->currentUserId();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
        }
        session_destroy();
        $this->respond(null, 204);
    }
}
