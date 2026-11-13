<?php

declare(strict_types=1);

namespace App\Model\DAO;

final class ReporteDAO extends AbstractDAO
{
    public function activos(?int $sedeId, ?int $dependenciaId, ?int $ubicacionId, ?int $categoriaId, ?string $estado, int $limite = 500): array
    {
        if ($limite < 1 || $limite > 1000) throw new \InvalidArgumentException('Límite inválido.');
        $filters = [];
        $params = [];
        foreach ([
            'u.sede_id' => $sedeId, 'a.dependencia_id' => $dependenciaId, 'a.ubicacion_id' => $ubicacionId,
            'a.categoria_id' => $categoriaId, 'a.estado' => $estado,
        ] as $column => $value) {
            if ($value !== null) {
                $placeholder = ':f' . count($params);
                $filters[] = $column . ' = ' . $placeholder;
                $params[$placeholder] = $value;
            }
        }
        $sql = 'SELECT a.id,a.placa,a.descripcion,a.estado,a.serial,a.marca,a.modelo,c.nombre AS categoria,'
            . 's.id AS sede_id,s.nombre AS sede,d.id AS dependencia_id,d.nombre AS dependencia,u.id AS ubicacion_id,u.nombre AS ubicacion '
            . 'FROM activo a JOIN categoria_activo c ON c.id=a.categoria_id '
            . 'JOIN ubicacion u ON u.id=a.ubicacion_id JOIN sede s ON s.id=u.sede_id '
            . 'JOIN dependencia d ON d.id=a.dependencia_id'
            . ($filters === [] ? '' : ' WHERE ' . implode(' AND ', $filters))
            . ' ORDER BY a.id LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) $stmt->bindValue($key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
