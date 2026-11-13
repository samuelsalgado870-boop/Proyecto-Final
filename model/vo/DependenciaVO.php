<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class DependenciaVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private ?int $sede_id,
        private string $codigo,
        private string $nombre,
        private ?string $tipo,
        private ?int $dependencia_padre_id,
        private string $estado
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSedeId(): ?int
    {
        return $this->sede_id;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function getDependenciaPadreId(): ?int
    {
        return $this->dependencia_padre_id;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'sede_id' => $this->sede_id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'dependencia_padre_id' => $this->dependencia_padre_id,
            'estado' => $this->estado,
        ];
    }
}
