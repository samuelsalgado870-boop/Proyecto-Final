<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\AsignacionVO;
use DateTimeImmutable;

final class AsignacionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'asignacion';
    protected const VO = AsignacionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'custodio_id', 'dependencia_id', 'ubicacion_id', 'fecha_inicio', 'fecha_fin', 'estado', 'entregado_por', 'fecha_aceptacion', 'observacion', 'vigente_activo_id'];

    public function buscarVigentePorActivo(int $activoId): ?AsignacionVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM asignacion WHERE activo_id = :activo_id AND fecha_fin IS NULL');
        $stmt->execute(['activo_id' => $activoId]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    public function crear(int $activoId, ?int $custodioId, int $dependenciaId, int $ubicacionId, int $entregadoPor, ?string $observacion = null): int
    {
        return $this->transaction(function () use ($activoId, $custodioId, $dependenciaId, $ubicacionId, $entregadoPor, $observacion): int {
            $lock = $this->pdo->prepare("SELECT estado FROM activo WHERE id = :id FOR UPDATE");
            $lock->execute(['id' => $activoId]);
            $asset = $lock->fetch();
            if ($asset === false) {
                throw new \DomainException('No existe el activo indicado.');
            }
            if (in_array($asset['estado'], ['DADO_DE_BAJA', 'EN_PRESTAMO', 'EN_MANTENIMIENTO'], true)) {
                throw new \DomainException('El activo no está disponible para asignación.');
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO asignacion (activo_id,custodio_id,dependencia_id,ubicacion_id,fecha_inicio,estado,entregado_por,observacion) "
                . "VALUES (:activo,:custodio,:dependencia,:ubicacion,CURRENT_TIMESTAMP,'PENDIENTE',:entregado,:observacion)"
            );
            $stmt->execute([
                'activo' => $activoId, 'custodio' => $custodioId, 'dependencia' => $dependenciaId,
                'ubicacion' => $ubicacionId, 'entregado' => $entregadoPor, 'observacion' => $observacion,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $update = $this->pdo->prepare("UPDATE activo SET estado = 'ASIGNADO', ubicacion_id = :ubicacion, dependencia_id = :dependencia WHERE id = :id");
            $update->execute(['ubicacion' => $ubicacionId, 'dependencia' => $dependenciaId, 'id' => $activoId]);
            return $id;
        });
    }

    public function finalizar(int $id, string $observacion = ''): bool
    {
        return $this->transaction(function () use ($id, $observacion): bool {
            $stmt = $this->pdo->prepare('SELECT activo_id FROM asignacion WHERE id = :id AND fecha_fin IS NULL FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row === false) {
                return false;
            }
            $update = $this->pdo->prepare("UPDATE asignacion SET fecha_fin = CURRENT_TIMESTAMP, estado = 'FINALIZADA', observacion = :observacion WHERE id = :id");
            $update->execute(['id' => $id, 'observacion' => $observacion]);
            $asset = $this->pdo->prepare("UPDATE activo SET estado = 'DISPONIBLE' WHERE id = :activo_id AND estado = 'ASIGNADO'");
            $asset->execute(['activo_id' => (int) $row['activo_id']]);
            return true;
        });
    }

    protected function hidratar(array $row): AsignacionVO
    {
        return new AsignacionVO(
            (int) $row['id'], (int) $row['activo_id'], $row['custodio_id'] === null ? null : (int) $row['custodio_id'],
            (int) $row['dependencia_id'], (int) $row['ubicacion_id'], new DateTimeImmutable($row['fecha_inicio']),
            $this->date($row['fecha_fin']), $row['estado'], $row['entregado_por'] === null ? null : (int) $row['entregado_por'],
            $this->date($row['fecha_aceptacion']), $row['observacion']
        );
    }
}
