<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\BajaActivoVO;

final class BajaActivoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'baja_activo';
    protected const VO = BajaActivoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'causal', 'solicitud', 'estado', 'fecha_solicitud', 'fecha_cierre'];

    public function solicitar(int $activoId, string $causal, string $solicitud): int
    {
        return $this->transaction(function () use ($activoId, $causal, $solicitud): int {
            $lock = $this->pdo->prepare('SELECT estado FROM activo WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $activoId]);
            $activo = $lock->fetch();
            if ($activo === false || $activo['estado'] === 'DADO_DE_BAJA') {
                throw new \DomainException('El activo no existe o ya está dado de baja.');
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO baja_activo (activo_id,causal,solicitud,estado) VALUES (:activo,:causal,:solicitud,'EN_TRAMITE')"
            );
            $stmt->execute(['activo' => $activoId, 'causal' => $causal, 'solicitud' => $solicitud]);
            $this->pdo->prepare("UPDATE activo SET estado = 'EN_REVISION' WHERE id = :id")->execute(['id' => $activoId]);
            return (int) $this->pdo->lastInsertId();
        });
    }
}
