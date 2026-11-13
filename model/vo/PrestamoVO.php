<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class PrestamoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private int $responsable_id,
        private ?string $destino,
        private \DateTimeImmutable $fecha_salida,
        private \DateTimeImmutable $fecha_limite,
        private ?string $condiciones_retorno,
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

    public function getResponsableId(): int
    {
        return $this->responsable_id;
    }

    public function getDestino(): ?string
    {
        return $this->destino;
    }

    public function getFechaSalida(): \DateTimeImmutable
    {
        return $this->fecha_salida;
    }

    public function getFechaLimite(): \DateTimeImmutable
    {
        return $this->fecha_limite;
    }

    public function getCondicionesRetorno(): ?string
    {
        return $this->condiciones_retorno;
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
            'responsable_id' => $this->responsable_id,
            'destino' => $this->destino,
            'fecha_salida' => $this->fecha_salida,
            'fecha_limite' => $this->fecha_limite,
            'condiciones_retorno' => $this->condiciones_retorno,
            'estado' => $this->estado,
        ];
    }
}
