<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\UbicacionVO;

final class UbicacionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'ubicacion';
    protected const VO = UbicacionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'sede_id', 'dependencia_id', 'codigo', 'nombre', 'tipo', 'ubicacion_padre_id', 'estado'];

    public function crear(int $sedeId, ?int $dependenciaId, string $codigo, string $nombre, ?string $tipo, ?int $padreId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ubicacion (sede_id,dependencia_id,codigo,nombre,tipo,ubicacion_padre_id,estado) VALUES (:sede,:dependencia,:codigo,:nombre,:tipo,:padre,'ACTIVA')"
        );
        $stmt->execute(['sede' => $sedeId, 'dependencia' => $dependenciaId, 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'padre' => $padreId]);
        return (int) $this->pdo->lastInsertId();
    }
}
