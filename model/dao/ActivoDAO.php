<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\ActivoVO;
use DateTimeImmutable;
use PDO;

final class ActivoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'activo';
    protected const VO = ActivoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'placa', 'fecha_asignacion_placa', 'serial', 'descripcion', 'categoria_id', 'marca', 'modelo', 'estado', 'ubicacion_id', 'dependencia_id', 'creado_en', 'vigente_activo_id'];

    public function buscarPorId(int $id): ?ActivoVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM activo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    public function buscarPorPlaca(string $placa): ?ActivoVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM activo WHERE placa = :placa');
        $stmt->execute(['placa' => $placa]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    /** @return list<ActivoVO> */
    public function buscar(?string $estado = null, ?int $categoriaId = null, ?int $dependenciaId = null, int $limite = 100): array
    {
        if ($limite < 1 || $limite > 500) {
            throw new \InvalidArgumentException('El límite debe estar entre 1 y 500.');
        }

        $conditions = [];
        $params = [];
        foreach (['estado' => $estado, 'categoria_id' => $categoriaId, 'dependencia_id' => $dependenciaId] as $column => $value) {
            if ($value !== null) {
                $conditions[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
        }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare('SELECT * FROM activo' . $where . ' ORDER BY id LIMIT :limite');
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn (array $row): ActivoVO => $this->hidratar($row), $stmt->fetchAll());
    }

    public function crear(
        string $placa,
        ?DateTimeImmutable $fechaAsignacionPlaca,
        ?string $serial,
        string $descripcion,
        int $categoriaId,
        ?string $marca,
        ?string $modelo,
        string $estado,
        int $ubicacionId,
        int $dependenciaId
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO activo (placa, fecha_asignacion_placa, serial, descripcion, categoria_id, marca, modelo, estado, ubicacion_id, dependencia_id) '
            . 'VALUES (:placa, :fecha, :serial, :descripcion, :categoria, :marca, :modelo, :estado, :ubicacion, :dependencia)'
        );
        $stmt->execute([
            'placa' => $placa,
            'fecha' => $fechaAsignacionPlaca?->format('Y-m-d'),
            'serial' => $serial,
            'descripcion' => $descripcion,
            'categoria' => $categoriaId,
            'marca' => $marca,
            'modelo' => $modelo,
            'estado' => $estado,
            'ubicacion' => $ubicacionId,
            'dependencia' => $dependenciaId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $stmt = $this->pdo->prepare('UPDATE activo SET estado = :estado WHERE id = :id');
        $stmt->execute(['id' => $id, 'estado' => $estado]);
        return $stmt->rowCount() > 0;
    }

    protected function hidratar(array $row): ActivoVO
    {
        return new ActivoVO(
            (int) $row['id'], $row['placa'], $this->date($row['fecha_asignacion_placa']), $row['serial'],
            $row['descripcion'], (int) $row['categoria_id'], $row['marca'], $row['modelo'], $row['estado'],
            (int) $row['ubicacion_id'], (int) $row['dependencia_id'], new DateTimeImmutable($row['creado_en'])
        );
    }
}
