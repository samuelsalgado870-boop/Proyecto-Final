<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\AsignacionDAO;

final class AsignacionController extends ApiController
{
    public function vigente(array $params): never
    {
        $assetId = $this->routeId($params);
        $asset = (new \App\Model\DAO\ActivoDAO($this->pdo))->buscarPorId($assetId);
        if ($asset === null) $this->notFound('No existe el activo.');
        $this->assertAssetScope($assetId);
        $this->respond((new AsignacionDAO($this->pdo))->buscarVigentePorActivo($assetId));
    }

    public function crear(array $params): never
    {
        $assetId = $this->routeId($params);
        $actor = $this->currentUserId();
        $this->assertAssetScope($assetId);
        $body = $this->body();
        $this->assertLocationScope($this->positiveInt($body, 'ubicacionId'));
        try {
            $id = (new AsignacionDAO($this->pdo))->crear(
                $assetId,
                $this->positiveInt($body, 'custodioId', true),
                $this->positiveInt($body, 'dependenciaId'),
                $this->positiveInt($body, 'ubicacionId'),
                $actor,
                $this->optionalString($body, 'observacion', 2000)
            );
        } catch (\DomainException $e) {
            $this->conflict($e->getMessage());
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') $this->conflict('El activo ya tiene una asignación vigente o la referencia no existe.');
            throw $e;
        }
        $this->respond(['id' => $id], 201);
    }
}
