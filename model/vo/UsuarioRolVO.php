<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class UsuarioRolVO implements JsonSerializable
{
    public function __construct(
        private int $usuario_id,
        private int $rol_id,
        private \DateTimeImmutable $fecha_asignacion,
        private ?int $asignado_por
    ) {

    }

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getRolId(): int
    {
        return $this->rol_id;
    }

    public function getFechaAsignacion(): \DateTimeImmutable
    {
        return $this->fecha_asignacion;
    }

    public function getAsignadoPor(): ?int
    {
        return $this->asignado_por;
    }

    public function jsonSerialize(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'rol_id' => $this->rol_id,
            'fecha_asignacion' => $this->fecha_asignacion,
            'asignado_por' => $this->asignado_por,
        ];
    }
}
