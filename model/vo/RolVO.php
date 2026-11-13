<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class RolVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $nombre,
        private ?string $descripcion,
        private string $estado
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
        ];
    }
}
