<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\MantenimientoVO;

final class MantenimientoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'mantenimiento';
    protected const VO = MantenimientoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'diagnostico', 'acciones', 'tecnico_proveedor', 'fecha_inicio', 'fecha_fin', 'resultado', 'estado'];

    public function crear(int $activoId, string $diagnostico, ?string $acciones, ?string $tecnico, string $fechaInicio): int
    {
        return $this->transaction(function () use ($activoId, $diagnostico, $acciones, $tecnico, $fechaInicio): int {
            $lock = $this->pdo->prepare('SELECT estado FROM activo WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $activoId]);
            if ($lock->fetch() === false) throw new \DomainException('No existe el activo.');
            $stmt = $this->pdo->prepare(
                "INSERT INTO mantenimiento (activo_id,diagnostico,acciones,tecnico_proveedor,fecha_inicio,estado) VALUES (:activo,:diagnostico,:acciones,:tecnico,:inicio,'EN_MANTENIMIENTO')"
            );
            $stmt->execute(['activo' => $activoId, 'diagnostico' => $diagnostico, 'acciones' => $acciones, 'tecnico' => $tecnico, 'inicio' => $fechaInicio]);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare("UPDATE activo SET estado = 'EN_MANTENIMIENTO' WHERE id = :id")->execute(['id' => $activoId]);
            return $id;
        });
    }
}
