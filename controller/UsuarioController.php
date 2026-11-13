<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\UsuarioDAO;

final class UsuarioController extends ApiController
{
    public function listar(array $params = []): never
    {
        $estado = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : null;
        $termino = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : null;
        $limite = filter_var($_GET['limite'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limite === false) {
            throw new ApiException(400, 'LIMIT_INVALID', 'El límite debe estar entre 1 y 500.');
        }
        $this->respond((new UsuarioDAO($this->pdo))->listar($estado, $termino, $limite));
    }

    public function crear(array $params = []): never
    {
        $body = $this->body();
        $identificacion = $this->requiredString($body, 'identificacion', 30);
        $nombre = $this->requiredString($body, 'nombre', 150);
        $correo = $this->requiredString($body, 'correo', 150);
        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new ApiException(400, 'EMAIL_INVALID', 'El correo no es válido.');
        }
        $password = $this->requiredString($body, 'contrasena', 255);
        if (strlen($password) < 12) {
            throw new ApiException(400, 'PASSWORD_WEAK', 'La contraseña debe tener al menos 12 caracteres.');
        }
        try {
            $id = (new UsuarioDAO($this->pdo))->crear($identificacion, $nombre, $correo, $password);
        } catch (\PDOException $error) {
            if ($error->getCode() === '23000') {
                $this->conflict('La identificación o el correo ya están registrados.');
            }
            throw $error;
        }
        $this->respond(['id' => $id], 201);
    }

    public function mostrar(array $params): never
    {
        $usuario = (new UsuarioDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($usuario === null) {
            $this->notFound('No existe el usuario.');
        }
        $this->respond($usuario);
    }

    public function cambiarEstado(array $params): never
    {
        $id = $this->routeId($params);
        $body = $this->body();
        $estado = $this->requiredString($body, 'estado', 20);
        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            throw new ApiException(400, 'STATE_INVALID', 'El estado debe ser ACTIVO o INACTIVO.');
        }
        $dao = new UsuarioDAO($this->pdo);
        if (!$dao->cambiarEstado($id, $estado) && $dao->buscarPorId($id) === null) {
            $this->notFound('No existe el usuario.');
        }
        $this->respond($dao->buscarPorId($id));
    }
}
