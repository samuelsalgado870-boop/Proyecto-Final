<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\PrestamoDAO;
use DateTimeImmutable;

final class PrestamoController extends ApiController
{
    public function crear(array $params = []): never
    {
        $body = $this->body();
        $assetId = $this->positiveInt($body, 'activoId');
        $this->assertAssetScope($assetId);
        try {
            $salida = new DateTimeImmutable($this->requiredString($body, 'fechaSalida', 40));
            $limite = new DateTimeImmutable($this->requiredString($body, 'fechaLimite', 40));
        } catch (\Throwable) { throw new ApiException(400, 'DATE_INVALID', 'fechaSalida o fechaLimite no es válida.'); }
        try {
            $id = (new PrestamoDAO($this->pdo))->crear(
                $assetId, $this->positiveInt($body, 'responsableId'),
                $this->optionalString($body, 'destino', 255), $salida, $limite,
                $this->optionalString($body, 'condicionesRetorno', 5000)
            );
        } catch (\InvalidArgumentException $e) { throw new ApiException(400, 'DATE_ORDER_INVALID', $e->getMessage()); }
        catch (\DomainException $e) { $this->conflict($e->getMessage()); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('El activo o responsable no existe.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }

    public function mostrar(array $params): never
    {
        $loan = (new PrestamoDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($loan === null) $this->notFound('No existe el préstamo.');
        $this->assertAssetScope($loan->getActivoId());
        $this->respond($loan);
    }
}
