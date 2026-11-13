<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\DiferenciaInventarioDAO;
use App\Model\DAO\InventarioDAO;
use App\Model\DAO\InventarioDetalleDAO;

final class DiferenciaInventarioController extends ApiController
{
    public function crear(array $params): never
    {
        $inventoryId = $this->routeId($params, 'id');
        $detailId = $this->routeId($params, 'detalleId');
        $inventory = (new InventarioDAO($this->pdo))->buscarPorId($inventoryId);
        if ($inventory === null) $this->notFound('No existe el inventario.');
        $this->assertInventoryScope($inventory);
        if (!in_array($inventory->getEstado(), ['EN_CURSO','EN_CONCILIACION'], true)) $this->conflict('El inventario está cerrado.');
        $detail = (new InventarioDetalleDAO($this->pdo))->buscarPorId($detailId);
        if ($detail === null || $detail->getInventarioId() !== $inventoryId) $this->notFound('El detalle no pertenece a este inventario.');
        if ($detail->getActivoId() !== null) $this->assertAssetScope($detail->getActivoId());
        $b = $this->body();
        try {
            $id = (new DiferenciaInventarioDAO($this->pdo))->crear($detailId, $this->requiredString($b, 'descripcion', 5000), $this->optionalString($b, 'decision', 5000), $this->positiveInt($b, 'responsableId', true));
        } catch (\PDOException $e) { if ($e->getCode() === '23000') $this->notFound('No existe el detalle de inventario.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
