<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class SedeVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $codigo,
        private string $nombre,
        private ?string $direccion,
        private string $estado
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'estado' => $this->estado,
        ];
    }
}
