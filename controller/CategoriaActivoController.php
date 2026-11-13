<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\CategoriaActivoDAO;

final class CategoriaActivoController extends ApiController
{
    public function listar(array $params = []): never
    {
        $estado = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : 'ACTIVA';
        $this->respond((new CategoriaActivoDAO($this->pdo))->buscarPorCampo('estado', $estado));
    }
}
