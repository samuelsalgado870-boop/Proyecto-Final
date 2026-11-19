<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\SolicitudTrasladoDAO;
use DateTimeImmutable;

final class SolicitudTrasladoController extends ApiController
{
    public function crear(array $params = []): never
    {
        $body = $this->body();
        $assetId = $this->positiveInt($body, 'activoId');
        $this->assertAssetScope($assetId);
        $this->assertLocationScope($this->positiveInt($body, 'origenUbicacionId'));
        $this->assertLocationScope($this->positiveInt($body, 'destinoUbicacionId'));
        $fecha = $this->optionalString($body, 'fechaPropuesta', 30);
        $propuesta = null;
        if ($fecha !== null) {
            try { $propuesta = new DateTimeImmutable($fecha); } catch (\Throwable) { throw new ApiException(400, 'DATE_INVALID', 'fechaPropuesta no es válida.'); }
        }
        try {
            $id = (new SolicitudTrasladoDAO($this->pdo))->crear(
                $assetId, $this->positiveInt($body, 'origenUbicacionId'),
                $this->positiveInt($body, 'destinoUbicacionId'), $this->positiveInt($body, 'origenDependenciaId', true),
                $this->positiveInt($body, 'destinoDependenciaId', true), $this->requiredString($body, 'motivo', 5000),
                $this->currentUserId(), $propuesta
            );
        } catch (\DomainException $e) { $this->conflict($e->getMessage()); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('El activo, ubicación o dependencia no existe.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }

    public function mostrar(array $params): never
    {
        $item = (new SolicitudTrasladoDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($item === null) $this->notFound('No existe la solicitud de traslado.');
        $this->assertAssetScope($item->getActivoId());
        $this->respond($item);
    }

    public function resolver(array $params): never
    {
        $id = $this->routeId($params);
        $item = (new SolicitudTrasladoDAO($this->pdo))->buscarPorId($id);
        if ($item === null) $this->notFound('No existe la solicitud de traslado.');
        $this->assertAssetScope($item->getActivoId());
        $body = $this->body();
        $decision = $this->requiredString($body, 'decision', 20);
        if (!in_array($decision, ['APROBADA','RECHAZADA'], true)) throw new ApiException(400, 'DECISION_INVALID', 'La decisión debe ser APROBADA o RECHAZADA.');
        try { $ok = (new SolicitudTrasladoDAO($this->pdo))->resolver($id, $this->currentUserId(), $decision, $this->optionalString($body, 'observacion', 2000)); }
        catch (\DomainException $error) { throw new ApiException(403, 'SELF_APPROVAL_FORBIDDEN', $error->getMessage()); }
        if (!$ok) $this->conflict('La solicitud no existe o ya fue resuelta.');
        $this->respond((new SolicitudTrasladoDAO($this->pdo))->buscarPorId($id));
    }
}
