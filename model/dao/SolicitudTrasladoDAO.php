<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\SolicitudTrasladoVO;
use DateTimeImmutable;

final class SolicitudTrasladoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'solicitud_traslado';
    protected const VO = SolicitudTrasladoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'origen_ubicacion_id', 'destino_ubicacion_id', 'origen_dependencia_id', 'destino_dependencia_id', 'motivo', 'solicitante_id', 'fecha_propuesta', 'estado'];

    public function buscarPorId(int $id): ?SolicitudTrasladoVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM solicitud_traslado WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    public function crear(
        int $activoId,
        int $origenUbicacionId,
        int $destinoUbicacionId,
        ?int $origenDependenciaId,
        ?int $destinoDependenciaId,
        string $motivo,
        int $solicitanteId,
        ?DateTimeImmutable $fechaPropuesta = null
    ): int {
        return $this->transaction(function () use ($activoId, $origenUbicacionId, $destinoUbicacionId, $origenDependenciaId, $destinoDependenciaId, $motivo, $solicitanteId, $fechaPropuesta): int {
            $lock = $this->pdo->prepare('SELECT estado FROM activo WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $activoId]);
            $asset = $lock->fetch();
            if ($asset === false) {
                throw new \DomainException('No existe el activo indicado.');
            }
            if (in_array($asset['estado'], ['DADO_DE_BAJA', 'EN_PRESTAMO', 'EN_MANTENIMIENTO'], true)) {
                throw new \DomainException('El estado actual del activo no permite solicitar traslado.');
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO solicitud_traslado (activo_id,origen_ubicacion_id,destino_ubicacion_id,origen_dependencia_id,destino_dependencia_id,motivo,solicitante_id,fecha_propuesta,estado) "
                . "VALUES (:activo,:origen,:destino,:origen_dep,:destino_dep,:motivo,:solicitante,:fecha,'SOLICITADO')"
            );
            $stmt->execute([
                'activo' => $activoId, 'origen' => $origenUbicacionId, 'destino' => $destinoUbicacionId,
                'origen_dep' => $origenDependenciaId, 'destino_dep' => $destinoDependenciaId,
                'motivo' => $motivo, 'solicitante' => $solicitanteId,
                'fecha' => $fechaPropuesta?->format('Y-m-d H:i:s'),
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $update = $this->pdo->prepare("UPDATE activo SET estado = 'TRASLADO_SOLICITADO' WHERE id = :id");
            $update->execute(['id' => $activoId]);
            return $id;
        });
    }

    public function resolver(int $id, int $aprobadorId, string $decision, ?string $observacion = null): bool
    {
        $estado = match ($decision) {
            'APROBADA' => 'AUTORIZADO',
            'RECHAZADA' => 'RECHAZADO',
            default => throw new \InvalidArgumentException('Decisión permitida: APROBADA o RECHAZADA.'),
        };

        return $this->transaction(function () use ($id, $aprobadorId, $decision, $estado, $observacion): bool {
            $find = $this->pdo->prepare('SELECT activo_id, solicitante_id, estado FROM solicitud_traslado WHERE id = :id FOR UPDATE');
            $find->execute(['id' => $id]);
            $row = $find->fetch();
            if ($row === false || $row['estado'] !== 'SOLICITADO') {
                return false;
            }
            if ((int) $row['solicitante_id'] === $aprobadorId) {
                throw new \DomainException('La persona solicitante no puede aprobar su propio traslado.');
            }
            $approval = $this->pdo->prepare(
                "INSERT INTO aprobacion (operacion_tipo,operacion_id,aprobador_id,decision,observacion) VALUES ('TRASLADO',:id,:aprobador,:decision,:observacion)"
            );
            $approval->execute(['id' => $id, 'aprobador' => $aprobadorId, 'decision' => $decision, 'observacion' => $observacion]);
            $updateRequest = $this->pdo->prepare('UPDATE solicitud_traslado SET estado = :estado WHERE id = :id');
            $updateRequest->execute(['estado' => $estado, 'id' => $id]);
            if ($decision === 'RECHAZADA') {
                $updateAsset = $this->pdo->prepare("UPDATE activo SET estado = 'DISPONIBLE' WHERE id = :activo AND estado = 'TRASLADO_SOLICITADO'");
                $updateAsset->execute(['activo' => (int) $row['activo_id']]);
            }
            return true;
        });
    }

    protected function hidratar(array $row): SolicitudTrasladoVO
    {
        return new SolicitudTrasladoVO(
            (int) $row['id'], (int) $row['activo_id'], (int) $row['origen_ubicacion_id'], (int) $row['destino_ubicacion_id'],
            $row['origen_dependencia_id'] === null ? null : (int) $row['origen_dependencia_id'],
            $row['destino_dependencia_id'] === null ? null : (int) $row['destino_dependencia_id'],
            $row['motivo'], (int) $row['solicitante_id'], $this->date($row['fecha_propuesta']), $row['estado']
        );
    }
}
