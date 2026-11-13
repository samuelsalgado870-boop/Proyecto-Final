<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\MantenimientoDAO;
use DateTimeImmutable;

final class MantenimientoController extends ApiController
{
    public function crear(array $params): never
    {
        $assetId = $this->routeId($params);
        $this->assertAssetScope($assetId);
        $b = $this->body();
        try { $fecha = (new DateTimeImmutable($this->requiredString($b, 'fechaInicio', 40)))->format('Y-m-d H:i:s'); }
        catch (\Throwable) { throw new ApiException(400, 'DATE_INVALID', 'fechaInicio no es válida.'); }
        try {
            $id = (new MantenimientoDAO($this->pdo))->crear($assetId, $this->requiredString($b, 'diagnostico', 5000), $this->optionalString($b, 'acciones', 5000), $this->optionalString($b, 'tecnicoProveedor', 150), $fecha);
        } catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('No existe el activo.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
