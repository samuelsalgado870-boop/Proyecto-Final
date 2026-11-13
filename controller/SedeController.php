<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\SedeDAO;

final class SedeController extends ApiController
{
    public function listar(array $params = []): never
    {
        $estado = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : 'ACTIVA';
        $this->respond((new SedeDAO($this->pdo))->buscarPorCampo('estado', $estado));
    }

    public function crear(array $params = []): never
    {
        $body = $this->body();
        try {
            $id = (new SedeDAO($this->pdo))->crear($this->requiredString($body, 'codigo', 30), $this->requiredString($body, 'nombre', 150), $this->optionalString($body, 'direccion', 255));
        } catch (\PDOException $e) { if ($e->getCode() === '23000') $this->conflict('El código de sede ya existe.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
