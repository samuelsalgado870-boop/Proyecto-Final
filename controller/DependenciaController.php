<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\DependenciaDAO;

final class DependenciaController extends ApiController
{
    public function listar(array $params = []): never
    {
        $id = $this->queryInt('sedeId');
        $this->respond($id === null ? (new DependenciaDAO($this->pdo))->buscarPorCampo('estado', 'ACTIVA') : (new DependenciaDAO($this->pdo))->buscarPorCampo('sede_id', $id));
    }

    public function crear(array $params = []): never
    {
        $b = $this->body();
        try { $id = (new DependenciaDAO($this->pdo))->crear($this->positiveInt($b, 'sedeId', true), $this->requiredString($b, 'codigo', 30), $this->requiredString($b, 'nombre', 150), $this->optionalString($b, 'tipo', 80), $this->positiveInt($b, 'dependenciaPadreId', true)); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('La sede o dependencia padre no existe, o el código está duplicado.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }

    private function queryInt(string $key): ?int
    {
        if (!isset($_GET[$key])) return null;
        $id = filter_var($_GET[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new ApiException(400, 'QUERY_INVALID', "{$key} inválido.");
        return $id;
    }
}
