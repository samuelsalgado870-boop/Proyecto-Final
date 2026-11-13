<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\MovimientoActivoDAO;
use App\Model\DAO\SolicitudTrasladoDAO;

final class MovimientoActivoController extends ApiController
{
    public function completarTraslado(array $params): never
    {
        $id = $this->routeId($params);
        $request = (new SolicitudTrasladoDAO($this->pdo))->buscarPorId($id);
        if ($request === null) $this->notFound('No existe la solicitud de traslado.');
        $this->assertAssetScope($request->getActivoId());
        $body = $this->body();
        try {
            $movementId = (new MovimientoActivoDAO($this->pdo))->completarTraslado(
                $id, $this->currentUserId(), $this->optionalString($body, 'observaciones', 2000)
            );
        } catch (\DomainException $e) { $this->conflict($e->getMessage()); }
        $this->respond(['id' => $movementId], 201);
    }
}
