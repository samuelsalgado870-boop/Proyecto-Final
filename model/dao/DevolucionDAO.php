<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\DevolucionVO;

final class DevolucionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'devolucion';
    protected const VO = DevolucionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'asignacion_id', 'prestamo_id', 'fecha', 'recibido_por', 'estado_fisico', 'destino_posterior', 'excepcion_justificada'];

    public function registrarDevolucionAsignacion(int $asignacionId, int $recibidoPor, string $estadoFisico, ?string $destinoPosterior = null, ?string $excepcion = null): int
    {
        return $this->transaction(function () use ($asignacionId, $recibidoPor, $estadoFisico, $destinoPosterior, $excepcion): int {
            $find = $this->pdo->prepare('SELECT activo_id FROM asignacion WHERE id = :id AND fecha_fin IS NULL FOR UPDATE');
            $find->execute(['id' => $asignacionId]);
            $assignment = $find->fetch();
            if ($assignment === false) {
                throw new \DomainException('La asignación no existe o ya está cerrada.');
            }
            $insert = $this->pdo->prepare(
                'INSERT INTO devolucion (asignacion_id,recibido_por,estado_fisico,destino_posterior,excepcion_justificada) '
                . 'VALUES (:asignacion,:recibido,:estado,:destino,:excepcion)'
            );
            $insert->execute([
                'asignacion' => $asignacionId, 'recibido' => $recibidoPor, 'estado' => $estadoFisico,
                'destino' => $destinoPosterior, 'excepcion' => $excepcion,
            ]);
            $this->pdo->prepare("UPDATE asignacion SET fecha_fin = CURRENT_TIMESTAMP, estado = 'FINALIZADA' WHERE id = :id")
                ->execute(['id' => $asignacionId]);
            $this->pdo->prepare("UPDATE activo SET estado = 'DISPONIBLE' WHERE id = :id AND estado = 'ASIGNADO'")
                ->execute(['id' => (int) $assignment['activo_id']]);
            return (int) $this->pdo->lastInsertId();
        });
    }
}
