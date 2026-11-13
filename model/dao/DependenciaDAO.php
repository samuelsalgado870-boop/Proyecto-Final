<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\DependenciaVO;

final class DependenciaDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'dependencia';
    protected const VO = DependenciaVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'sede_id', 'codigo', 'nombre', 'tipo', 'dependencia_padre_id', 'estado'];

    public function crear(?int $sedeId, string $codigo, string $nombre, ?string $tipo, ?int $padreId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO dependencia (sede_id,codigo,nombre,tipo,dependencia_padre_id,estado) VALUES (:sede,:codigo,:nombre,:tipo,:padre,'ACTIVA')"
        );
        $stmt->execute(['sede' => $sedeId, 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'padre' => $padreId]);
        return (int) $this->pdo->lastInsertId();
    }
}
