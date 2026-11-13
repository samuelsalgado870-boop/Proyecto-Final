<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\NovedadVO;

final class NovedadDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'novedad';
    protected const VO = NovedadVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'reportante_id', 'tipo', 'descripcion', 'fecha', 'estado'];

    public function reportar(int $activoId, int $reportanteId, string $tipo, string $descripcion): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO novedad (activo_id,reportante_id,tipo,descripcion,estado) VALUES (:activo,:reportante,:tipo,:descripcion,'ABIERTA')"
        );
        $stmt->execute(['activo' => $activoId, 'reportante' => $reportanteId, 'tipo' => $tipo, 'descripcion' => $descripcion]);
        return (int) $this->pdo->lastInsertId();
    }
}
