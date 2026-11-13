<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class AsignacionVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private ?int $custodio_id,
        private int $dependencia_id,
        private int $ubicacion_id,
        private \DateTimeImmutable $fecha_inicio,
        private ?\DateTimeImmutable $fecha_fin,
        private string $estado,
        private ?int $entregado_por,
        private ?\DateTimeImmutable $fecha_aceptacion,
        private ?string $observacion
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

    public function getCustodioId(): ?int
    {
        return $this->custodio_id;
    }

    public function getDependenciaId(): int
    {
        return $this->dependencia_id;
    }

    public function getUbicacionId(): int
    {
        return $this->ubicacion_id;
    }

    public function getFechaInicio(): \DateTimeImmutable
    {
        return $this->fecha_inicio;
    }

    public function getFechaFin(): ?\DateTimeImmutable
    {
        return $this->fecha_fin;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getEntregadoPor(): ?int
    {
        return $this->entregado_por;
    }

    public function getFechaAceptacion(): ?\DateTimeImmutable
    {
        return $this->fecha_aceptacion;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'custodio_id' => $this->custodio_id,
            'dependencia_id' => $this->dependencia_id,
            'ubicacion_id' => $this->ubicacion_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado,
            'entregado_por' => $this->entregado_por,
            'fecha_aceptacion' => $this->fecha_aceptacion,
            'observacion' => $this->observacion,
        ];
    }
}
