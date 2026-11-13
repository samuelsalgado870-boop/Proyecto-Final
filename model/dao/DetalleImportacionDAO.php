<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\DetalleImportacionVO;

final class DetalleImportacionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'detalle_importacion';
    protected const VO = DetalleImportacionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'importacion_id', 'numero_fila', 'resultado', 'error', 'posible_duplicado_id'];

    public function registrar(int $importacionId, int $fila, string $resultado, ?string $error, ?int $duplicadoId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO detalle_importacion (importacion_id,numero_fila,resultado,error,posible_duplicado_id) VALUES (:importacion,:fila,:resultado,:error,:duplicado)'
        );
        $stmt->execute(['importacion' => $importacionId, 'fila' => $fila, 'resultado' => $resultado, 'error' => $error, 'duplicado' => $duplicadoId]);
        return (int) $this->pdo->lastInsertId();
    }
}
