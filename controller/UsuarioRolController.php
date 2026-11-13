<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\UsuarioDAO;
use App\Model\DAO\UsuarioRolDAO;

final class UsuarioRolController extends ApiController
{
    public function reemplazar(array $params): never
    {
        $usuarioId = $this->routeId($params);
        if ((new UsuarioDAO($this->pdo))->buscarPorId($usuarioId) === null) $this->notFound('No existe el usuario.');
        $body = $this->body();
        $roles = $body['roles'] ?? null;
        if (!is_array($roles) || !array_is_list($roles)) throw new ApiException(400, 'ROLES_INVALID', 'roles debe ser una lista de IDs.');
        $ids = [];
        foreach ($roles as $role) {
            $id = filter_var($role, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new ApiException(400, 'ROLE_ID_INVALID', 'Cada rol debe ser un ID entero positivo.');
            $ids[] = $id;
        }
        try { (new UsuarioRolDAO($this->pdo))->reemplazar($usuarioId, $ids, $this->currentUserId()); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('Uno de los roles no existe.'); throw $e; }
        $this->respond((new UsuarioRolDAO($this->pdo))->buscarPorCampo('usuario_id', $usuarioId));
    }
}
