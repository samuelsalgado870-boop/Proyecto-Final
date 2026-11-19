<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\InventarioDAO;
use DateTimeImmutable;

final class InventarioController extends ApiController
{
    public function conciliar(array $params): never
    {
        $id = $this->routeId($params);
        $inventory = (new InventarioDAO($this->pdo))->buscarPorId($id);
        if ($inventory === null) $this->notFound('No existe el inventario.');
        $this->assertInventoryScope($inventory);
        $report = (new \App\Model\DAO\ConciliacionDAO($this->pdo))->porInventario($id);
        $this->respond(['resumen'=>$report,'pendienteCECAR'=>true]);
    }

    public function crear(array $params = []): never
    {
        $body = $this->body();
        try { $inicio = new DateTimeImmutable($this->requiredString($body, 'fechaInicio', 40)); }
        catch (\Throwable) { throw new ApiException(400, 'DATE_INVALID', 'fechaInicio no es válida.'); }
        try {
            $actorId = $this->currentUserId();
            $sedeId = $this->positiveInt($body, 'sedeId', true);
            $dependencyId = $this->positiveInt($body, 'dependenciaId', true);
            $locationId = $this->positiveInt($body, 'ubicacionId', true);
            if ($locationId !== null) $this->assertLocationScope($locationId);
            elseif (!(new \App\Model\DAO\AutorizacionDAO($this->pdo))->dentroDeAmbito($actorId, $sedeId, $dependencyId, null)) {
                throw new ApiException(403, 'SCOPE_DENIED', 'El alcance del inventario no está autorizado.');
            }
            $id = (new InventarioDAO($this->pdo))->crear(
                $this->requiredString($body, 'nombre', 150), $this->requiredString($body, 'alcance', 255),
                $sedeId, $dependencyId, $locationId, $this->positiveInt($body, 'categoriaId', true),
                $actorId, $inicio
            );
        } catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('El responsable o ámbito no existe.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }

    public function mostrar(array $params): never
    {
        $id = $this->routeId($params);
        $dao = new InventarioDAO($this->pdo);
        $inventory = $dao->buscarPorId($id);
        if ($inventory === null) $this->notFound('No existe el inventario.');
        $this->assertInventoryScope($inventory);
        $this->respond(['inventario' => $inventory, 'detalles' => $dao->listarDetalles($id)]);
    }

    public function iniciar(array $params): never
    {
        $id = $this->routeId($params);
        $dao = new InventarioDAO($this->pdo);
        $inventory = $dao->buscarPorId($id);
        if ($inventory === null) $this->notFound('No existe el inventario.');
        $this->assertInventoryScope($inventory);
        if (!$dao->iniciar($id)) {
            $this->conflict('El inventario no está en estado PLANIFICADO.');
        }
        $this->respond($dao->buscarPorId($id));
    }

    public function cerrar(array $params): never
    {
        $id = $this->routeId($params);
        $dao = new InventarioDAO($this->pdo);
        $inventory = $dao->buscarPorId($id);
        if ($inventory === null) $this->notFound('No existe el inventario.');
        $this->assertInventoryScope($inventory);
        if (!$dao->cerrar($id)) {
            $this->conflict('El inventario no se puede cerrar desde su estado actual.');
        }
        $this->respond($dao->buscarPorId($id));
    }
}
