<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class NotificacionVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $usuario_id,
        private string $tipo,
        private \DateTimeImmutable $fecha,
        private string $estado,
        private ?string $referencia_tipo,
        private ?int $referencia_id
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getReferenciaTipo(): ?string
    {
        return $this->referencia_tipo;
    }

    public function getReferenciaId(): ?int
    {
        return $this->referencia_id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'tipo' => $this->tipo,
            'fecha' => $this->fecha,
            'estado' => $this->estado,
            'referencia_tipo' => $this->referencia_tipo,
            'referencia_id' => $this->referencia_id,
        ];
    }
}
