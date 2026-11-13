<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\DevolucionDAO;
use App\Model\DAO\PrestamoDAO;

final class DevolucionController extends ApiController
{
    public function desdeAsignacion(array $params): never
    {
        $assignment = (new \App\Model\DAO\AsignacionDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($assignment === null) $this->notFound('No existe la asignación.');
        $this->assertAssetScope($assignment->getActivoId());
        $body = $this->body();
        try {
            $id = (new DevolucionDAO($this->pdo))->registrarDevolucionAsignacion(
                $this->routeId($params), $this->currentUserId(), $this->requiredString($body, 'estadoFisico', 80),
                $this->optionalString($body, 'destinoPosterior', 255), $this->optionalString($body, 'excepcion', 2000)
            );
        } catch (\DomainException $e) { $this->conflict($e->getMessage()); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('No existe la asignación.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }

    public function desdePrestamo(array $params): never
    {
        $loan = (new \App\Model\DAO\PrestamoDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($loan === null) $this->notFound('No existe el préstamo.');
        $this->assertAssetScope($loan->getActivoId());
        $body = $this->body();
        try {
            $id = (new PrestamoDAO($this->pdo))->registrarDevolucion(
                $this->routeId($params), $this->currentUserId(), $this->requiredString($body, 'estadoFisico', 80),
                $this->optionalString($body, 'destinoPosterior', 255), $this->optionalString($body, 'excepcion', 2000)
            );
        } catch (\DomainException $e) { $this->conflict($e->getMessage()); }
        catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('No existe el préstamo o el usuario receptor.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
