<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\ImportacionVO;

final class ImportacionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'importacion';
    protected const VO = ImportacionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'nombre_archivo', 'usuario_id', 'fecha', 'registros_aceptados', 'registros_rechazados', 'estado'];

    public function crear(string $nombreArchivo, int $usuarioId): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO importacion (nombre_archivo,usuario_id,estado) VALUES (:archivo,:usuario,'VALIDANDO')");
        $stmt->execute(['archivo' => $nombreArchivo, 'usuario' => $usuarioId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizarResultado(int $id, int $aceptados, int $rechazados, string $estado): bool
    {
        $stmt = $this->pdo->prepare('UPDATE importacion SET registros_aceptados=:ok,registros_rechazados=:error,estado=:estado WHERE id=:id');
        $stmt->execute(['ok' => $aceptados, 'error' => $rechazados, 'estado' => $estado, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
