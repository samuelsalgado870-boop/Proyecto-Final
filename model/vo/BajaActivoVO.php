<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class BajaActivoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private string $causal,
        private string $solicitud,
        private string $estado,
        private \DateTimeImmutable $fecha_solicitud,
        private ?\DateTimeImmutable $fecha_cierre
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

    public function getCausal(): string
    {
        return $this->causal;
    }

    public function getSolicitud(): string
    {
        return $this->solicitud;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getFechaSolicitud(): \DateTimeImmutable
    {
        return $this->fecha_solicitud;
    }

    public function getFechaCierre(): ?\DateTimeImmutable
    {
        return $this->fecha_cierre;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'causal' => $this->causal,
            'solicitud' => $this->solicitud,
            'estado' => $this->estado,
            'fecha_solicitud' => $this->fecha_solicitud,
            'fecha_cierre' => $this->fecha_cierre,
        ];
    }
}
