<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\BajaActivoDAO;

final class BajaActivoController extends ApiController
{
    public function solicitar(array $params): never
    {
        $assetId = $this->routeId($params);
        $this->assertAssetScope($assetId);
        $b = $this->body();
        try { $id = (new BajaActivoDAO($this->pdo))->solicitar($assetId, $this->requiredString($b, 'causal', 5000), $this->requiredString($b, 'solicitud', 5000)); }
        catch (\DomainException $e) { $this->conflict($e->getMessage()); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('No existe el activo.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
