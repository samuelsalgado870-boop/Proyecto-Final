<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\DiferenciaInventarioVO;

final class DiferenciaInventarioDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'diferencia_inventario';
    protected const VO = DiferenciaInventarioVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'detalle_id', 'descripcion', 'decision', 'responsable_id', 'fecha', 'estado'];

    public function crear(int $detalleId, string $descripcion, ?string $decision, ?int $responsableId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO diferencia_inventario (detalle_id,descripcion,decision,responsable_id,estado) VALUES (:detalle,:descripcion,:decision,:responsable,'PENDIENTE')"
        );
        $stmt->execute(['detalle' => $detalleId, 'descripcion' => $descripcion, 'decision' => $decision, 'responsable' => $responsableId]);
        return (int) $this->pdo->lastInsertId();
    }
}
