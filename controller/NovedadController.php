<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\NovedadDAO;

final class NovedadController extends ApiController
{
    public function crear(array $params = []): never
    {
        $b = $this->body();
        $assetId = $this->positiveInt($b, 'activoId');
        $this->assertAssetScope($assetId);
        try {
            $id = (new NovedadDAO($this->pdo))->reportar($assetId, $this->currentUserId(), $this->requiredString($b, 'tipo', 80), $this->requiredString($b, 'descripcion', 5000));
        } catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('No existe el activo.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
