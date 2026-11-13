<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\DocumentoVO;

final class DocumentoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'documento';
    protected const VO = DocumentoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'nombre_original', 'tipo', 'tamano_bytes', 'version', 'fecha', 'responsable_id', 'ruta_privada'];

    public function registrar(string $nombre, string $tipo, int $tamano, ?int $responsable, string $ruta): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO documento (nombre_original,tipo,tamano_bytes,version,responsable_id,ruta_privada) '
            . 'VALUES (:nombre,:tipo,:tamano,1,:responsable,:ruta)'
        );
        $stmt->execute(['nombre' => $nombre, 'tipo' => $tipo, 'tamano' => $tamano, 'responsable' => $responsable, 'ruta' => $ruta]);
        return (int) $this->pdo->lastInsertId();
    }
}
