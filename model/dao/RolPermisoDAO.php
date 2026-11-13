<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\RolPermisoVO;

final class RolPermisoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'rol_permiso';
    protected const VO = RolPermisoVO::class;
    protected const PRIMARY_KEY = ['rol_id', 'permiso_id'];
    protected const COLUMNS = ['rol_id', 'permiso_id'];

    public function asignar(int $rolId, int $permisoId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (:rol_id, :permiso_id)');
        $stmt->execute(['rol_id' => $rolId, 'permiso_id' => $permisoId]);
    }

    public function revocar(int $rolId, int $permisoId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM rol_permiso WHERE rol_id = :rol_id AND permiso_id = :permiso_id');
        $stmt->execute(['rol_id' => $rolId, 'permiso_id' => $permisoId]);
        return $stmt->rowCount() > 0;
    }
}
