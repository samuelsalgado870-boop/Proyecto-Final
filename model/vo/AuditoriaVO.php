<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class AuditoriaVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private ?int $usuario_id,
        private string $entidad,
        private ?int $entidad_id,
        private string $accion,
        private ?string $motivo,
        private ?string $antes,
        private ?string $despues,
        private \DateTimeImmutable $fecha_hora
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsuarioId(): ?int
    {
        return $this->usuario_id;
    }

    public function getEntidad(): string
    {
        return $this->entidad;
    }

    public function getEntidadId(): ?int
    {
        return $this->entidad_id;
    }

    public function getAccion(): string
    {
        return $this->accion;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function getAntes(): ?string
    {
        return $this->antes;
    }

    public function getDespues(): ?string
    {
        return $this->despues;
    }

    public function getFechaHora(): \DateTimeImmutable
    {
        return $this->fecha_hora;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'accion' => $this->accion,
            'motivo' => $this->motivo,
            'antes' => $this->antes,
            'despues' => $this->despues,
            'fecha_hora' => $this->fecha_hora,
        ];
    }
}
