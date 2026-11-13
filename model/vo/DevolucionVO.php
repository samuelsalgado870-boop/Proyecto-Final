<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class DevolucionVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private ?int $asignacion_id,
        private ?int $prestamo_id,
        private \DateTimeImmutable $fecha,
        private int $recibido_por,
        private string $estado_fisico,
        private ?string $destino_posterior,
        private ?string $excepcion_justificada
    ) {
        if (($this->asignacion_id === null) === ($this->prestamo_id === null)) {
            throw new \InvalidArgumentException(
                'La devolución debe referenciar exactamente una asignación o un préstamo.'
            );
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAsignacionId(): ?int
    {
        return $this->asignacion_id;
    }

    public function getPrestamoId(): ?int
    {
        return $this->prestamo_id;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getRecibidoPor(): int
    {
        return $this->recibido_por;
    }

    public function getEstadoFisico(): string
    {
        return $this->estado_fisico;
    }

    public function getDestinoPosterior(): ?string
    {
        return $this->destino_posterior;
    }

    public function getExcepcionJustificada(): ?string
    {
        return $this->excepcion_justificada;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'asignacion_id' => $this->asignacion_id,
            'prestamo_id' => $this->prestamo_id,
            'fecha' => $this->fecha,
            'recibido_por' => $this->recibido_por,
            'estado_fisico' => $this->estado_fisico,
            'destino_posterior' => $this->destino_posterior,
            'excepcion_justificada' => $this->excepcion_justificada,
        ];
    }
}
