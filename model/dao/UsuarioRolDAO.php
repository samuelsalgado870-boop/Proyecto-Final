<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\UsuarioRolVO;

final class UsuarioRolDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'usuario_rol';
    protected const VO = UsuarioRolVO::class;
    protected const PRIMARY_KEY = ['usuario_id', 'rol_id'];
    protected const COLUMNS = ['usuario_id', 'rol_id', 'fecha_asignacion', 'asignado_por'];

    public function asignar(int $usuarioId, int $rolId, ?int $asignadoPor): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuario_rol (usuario_id, rol_id, asignado_por) VALUES (:usuario_id, :rol_id, :asignado_por)'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'rol_id' => $rolId, 'asignado_por' => $asignadoPor]);
    }

    public function revocar(int $usuarioId, int $rolId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuario_rol WHERE usuario_id = :usuario_id AND rol_id = :rol_id');
        $stmt->execute(['usuario_id' => $usuarioId, 'rol_id' => $rolId]);
        return $stmt->rowCount() > 0;
    }

    public function reemplazar(int $usuarioId, array $roles, ?int $asignadoPor): void
    {
        $this->transaction(function () use ($usuarioId, $roles, $asignadoPor): void {
            $this->pdo->prepare('DELETE FROM usuario_rol WHERE usuario_id = :usuario')->execute(['usuario' => $usuarioId]);
            $insert = $this->pdo->prepare('INSERT INTO usuario_rol (usuario_id,rol_id,asignado_por) VALUES (:usuario,:rol,:asignador)');
            foreach (array_unique($roles) as $rolId) {
                $insert->execute(['usuario' => $usuarioId, 'rol' => $rolId, 'asignador' => $asignadoPor]);
            }
        });
    }
}
