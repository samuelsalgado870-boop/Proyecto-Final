<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\InventarioDAO;

final class InventarioDetalleController extends ApiController
{
    public function crear(array $params): never
    {
        $inventoryId = $this->routeId($params);
        $inventory = (new InventarioDAO($this->pdo))->buscarPorId($inventoryId);
        if ($inventory === null) $this->notFound('No existe el inventario.');
        $this->assertInventoryScope($inventory);
        if (!in_array($inventory->getEstado(), ['EN_CURSO', 'EN_CONCILIACION'], true)) $this->conflict('El inventario no está abierto para registrar detalles.');
        $body = $this->body();
        $assetId = $this->positiveInt($body, 'activoId', true);
        if ($assetId !== null) $this->assertAssetScope($assetId);
        try {
            $id = (new InventarioDAO($this->pdo))->registrarDetalle(
                $inventoryId, $assetId, $this->currentUserId(),
                $this->requiredString($body, 'resultado', 60), $this->optionalString($body, 'placaObservada', 60),
                $this->optionalString($body, 'ubicacionObservada', 255), $this->optionalString($body, 'condicionFisica', 80),
                $this->optionalString($body, 'observacion', 2000)
            );
        } catch (\PDOException $e) { if ($e->getCode() === '23000') $this->conflict('El activo ya está registrado en este inventario o la referencia no existe.'); throw $e; }
        $this->respond(['id' => $id], 201);
    }
}
