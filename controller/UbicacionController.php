<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\UbicacionDAO;

final class UbicacionController extends ApiController
{
    public function listar(array $params = []): never
    {
        $sedeId = isset($_GET['sedeId']) ? filter_var($_GET['sedeId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
        if (isset($_GET['sedeId']) && $sedeId === false) throw new ApiException(400, 'QUERY_INVALID', 'sedeId inválido.');
        $dao = new UbicacionDAO($this->pdo);
        $this->respond($sedeId === null ? $dao->buscarPorCampo('estado', 'ACTIVA') : $dao->buscarPorCampo('sede_id', $sedeId));
    }

    public function crear(array $params = []): never
    {
        $b = $this->body();
        try { $id = (new UbicacionDAO($this->pdo))->crear($this->positiveInt($b, 'sedeId'), $this->positiveInt($b, 'dependenciaId', true), $this->requiredString($b, 'codigo', 30), $this->requiredString($b, 'nombre', 150), $this->optionalString($b, 'tipo', 80), $this->positiveInt($b, 'ubicacionPadreId', true)); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('La sede, dependencia o ubicación padre no existe, o el código está duplicado.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
