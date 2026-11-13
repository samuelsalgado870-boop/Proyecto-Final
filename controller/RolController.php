<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\RolDAO;

final class RolController extends ApiController
{
    public function listar(array $params = []): never
    {
        $estado = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : 'ACTIVO';
        $this->respond((new RolDAO($this->pdo))->buscarPorCampo('estado', $estado));
    }
}
