<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class NovedadVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private int $reportante_id,
        private string $tipo,
        private string $descripcion,
        private \DateTimeImmutable $fecha,
        private string $estado
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getActivoId(): int
    {
        return $this->activo_id;
    }

    public function getReportanteId(): int
    {
        return $this->reportante_id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'reportante_id' => $this->reportante_id,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha,
            'estado' => $this->estado,
        ];
    }
}
