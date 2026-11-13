<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\MovimientoActivoVO;

final class MovimientoActivoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'movimiento_activo';
    protected const VO = MovimientoActivoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'usuario_id', 'tipo', 'motivo', 'origen_ubicacion_id', 'destino_ubicacion_id', 'origen_dependencia_id', 'destino_dependencia_id', 'fecha_salida', 'fecha_recepcion', 'observaciones'];

    public function completarTraslado(int $solicitudId, int $usuarioId, ?string $observaciones = null): int
    {
        return $this->transaction(function () use ($solicitudId, $usuarioId, $observaciones): int {
            $find = $this->pdo->prepare('SELECT * FROM solicitud_traslado WHERE id = :id FOR UPDATE');
            $find->execute(['id' => $solicitudId]);
            $request = $find->fetch();
            if ($request === false || !in_array($request['estado'], ['AUTORIZADO', 'EN_TRASLADO'], true)) {
                throw new \DomainException('La solicitud no existe o no está autorizada para completar.');
            }

            $movement = $this->pdo->prepare(
                "INSERT INTO movimiento_activo (activo_id,usuario_id,tipo,motivo,origen_ubicacion_id,destino_ubicacion_id,origen_dependencia_id,destino_dependencia_id,fecha_salida,fecha_recepcion,observaciones) "
                . "VALUES (:activo,:usuario,'TRASLADO',:motivo,:origen,:destino,:origen_dep,:destino_dep,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,:observaciones)"
            );
            $movement->execute([
                'activo' => (int) $request['activo_id'], 'usuario' => $usuarioId, 'motivo' => $request['motivo'],
                'origen' => (int) $request['origen_ubicacion_id'], 'destino' => (int) $request['destino_ubicacion_id'],
                'origen_dep' => $request['origen_dependencia_id'], 'destino_dep' => $request['destino_dependencia_id'],
                'observaciones' => $observaciones,
            ]);
            $movementId = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare("UPDATE solicitud_traslado SET estado = 'COMPLETADO' WHERE id = :id")->execute(['id' => $solicitudId]);
            $this->pdo->prepare(
                "UPDATE activo SET ubicacion_id = :ubicacion, dependencia_id = COALESCE(:dependencia, dependencia_id), "
                . "estado = CASE WHEN EXISTS (SELECT 1 FROM asignacion WHERE activo_id = :id_asignacion AND fecha_fin IS NULL) THEN 'ASIGNADO' ELSE 'DISPONIBLE' END WHERE id = :id"
            )->execute([
                'ubicacion' => (int) $request['destino_ubicacion_id'],
                'dependencia' => $request['destino_dependencia_id'],
                'id_asignacion' => (int) $request['activo_id'],
                'id' => (int) $request['activo_id'],
            ]);
            return $movementId;
        });
    }
}
