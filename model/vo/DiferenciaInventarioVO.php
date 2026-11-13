<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class DiferenciaInventarioVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $detalle_id,
        private string $descripcion,
        private ?string $decision,
        private ?int $responsable_id,
        private \DateTimeImmutable $fecha,
        private string $estado
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDetalleId(): int
    {
        return $this->detalle_id;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function getResponsableId(): ?int
    {
        return $this->responsable_id;
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
            'detalle_id' => $this->detalle_id,
            'descripcion' => $this->descripcion,
            'decision' => $this->decision,
            'responsable_id' => $this->responsable_id,
            'fecha' => $this->fecha,
            'estado' => $this->estado,
        ];
    }
}
