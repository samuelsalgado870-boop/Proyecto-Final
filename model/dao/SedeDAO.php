<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\SedeVO;

final class SedeDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'sede';
    protected const VO = SedeVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'codigo', 'nombre', 'direccion', 'estado'];

    public function crear(string $codigo, string $nombre, ?string $direccion): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO sede (codigo,nombre,direccion,estado) VALUES (:codigo,:nombre,:direccion,'ACTIVA')");
        $stmt->execute(['codigo' => $codigo, 'nombre' => $nombre, 'direccion' => $direccion]);
        return (int) $this->pdo->lastInsertId();
    }
}
