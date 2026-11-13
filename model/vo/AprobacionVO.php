<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class AprobacionVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $operacion_tipo,
        private int $operacion_id,
        private int $aprobador_id,
        private string $decision,
        private \DateTimeImmutable $fecha,
        private ?string $observacion
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperacionTipo(): string
    {
        return $this->operacion_tipo;
    }

    public function getOperacionId(): int
    {
        return $this->operacion_id;
    }

    public function getAprobadorId(): int
    {
        return $this->aprobador_id;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'operacion_tipo' => $this->operacion_tipo,
            'operacion_id' => $this->operacion_id,
            'aprobador_id' => $this->aprobador_id,
            'decision' => $this->decision,
            'fecha' => $this->fecha,
            'observacion' => $this->observacion,
        ];
    }
}
