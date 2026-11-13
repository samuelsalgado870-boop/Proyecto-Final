<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\InventarioDetalleVO;
use App\Model\VO\InventarioVO;
use DateTimeImmutable;

final class InventarioDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'inventario';
    protected const VO = InventarioVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'nombre', 'alcance', 'sede_id', 'dependencia_id', 'ubicacion_id', 'categoria_id', 'responsable_id', 'fecha_inicio', 'fecha_fin', 'estado'];

    public function buscarPorId(int $id): ?InventarioVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventario WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratarInventario($row);
    }

    public function crear(
        string $nombre,
        string $alcance,
        ?int $sedeId,
        ?int $dependenciaId,
        ?int $ubicacionId,
        ?int $categoriaId,
        int $responsableId,
        DateTimeImmutable $fechaInicio
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO inventario (nombre,alcance,sede_id,dependencia_id,ubicacion_id,categoria_id,responsable_id,fecha_inicio,estado) "
            . "VALUES (:nombre,:alcance,:sede,:dependencia,:ubicacion,:categoria,:responsable,:inicio,'PLANIFICADO')"
        );
        $stmt->execute([
            'nombre' => $nombre, 'alcance' => $alcance, 'sede' => $sedeId, 'dependencia' => $dependenciaId,
            'ubicacion' => $ubicacionId, 'categoria' => $categoriaId, 'responsable' => $responsableId,
            'inicio' => $fechaInicio->format('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function iniciar(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE inventario SET estado = 'EN_CURSO' WHERE id = :id AND estado = 'PLANIFICADO'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function registrarDetalle(
        int $inventarioId,
        ?int $activoId,
        ?int $verificadorId,
        string $resultado,
        ?string $placaObservada,
        ?string $ubicacionObservada,
        ?string $condicionFisica,
        ?string $observacion
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO inventario_detalle (inventario_id,activo_id,verificador_id,resultado,placa_observada,ubicacion_observada,condicion_fisica,observacion) "
            . 'VALUES (:inventario,:activo,:verificador,:resultado,:placa,:ubicacion,:condicion,:observacion)'
        );
        $stmt->execute([
            'inventario' => $inventarioId, 'activo' => $activoId, 'verificador' => $verificadorId,
            'resultado' => $resultado, 'placa' => $placaObservada, 'ubicacion' => $ubicacionObservada,
            'condicion' => $condicionFisica, 'observacion' => $observacion,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<InventarioDetalleVO> */
    public function listarDetalles(int $inventarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventario_detalle WHERE inventario_id = :id ORDER BY id');
        $stmt->execute(['id' => $inventarioId]);
        return array_map(fn (array $row): InventarioDetalleVO => $this->hidratarDetalle($row), $stmt->fetchAll());
    }

    public function cerrar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE inventario SET estado = 'CERRADO', fecha_fin = CURRENT_TIMESTAMP WHERE id = :id AND estado IN ('EN_CURSO','EN_CONCILIACION')"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function hidratarInventario(array $row): InventarioVO
    {
        return new InventarioVO(
            (int) $row['id'], $row['nombre'], $row['alcance'], $this->intOrNull($row['sede_id']),
            $this->intOrNull($row['dependencia_id']), $this->intOrNull($row['ubicacion_id']),
            $this->intOrNull($row['categoria_id']), (int) $row['responsable_id'],
            new DateTimeImmutable($row['fecha_inicio']), $this->date($row['fecha_fin']), $row['estado']
        );
    }

    private function hidratarDetalle(array $row): InventarioDetalleVO
    {
        return new InventarioDetalleVO(
            (int) $row['id'], (int) $row['inventario_id'], $this->intOrNull($row['activo_id']),
            $this->intOrNull($row['verificador_id']), $row['resultado'], $row['placa_observada'],
            $row['ubicacion_observada'], $row['condicion_fisica'], new DateTimeImmutable($row['fecha_verificacion']),
            $row['observacion']
        );
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
