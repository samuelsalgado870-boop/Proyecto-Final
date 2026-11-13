<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class InventarioVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $nombre,
        private string $alcance,
        private ?int $sede_id,
        private ?int $dependencia_id,
        private ?int $ubicacion_id,
        private ?int $categoria_id,
        private int $responsable_id,
        private \DateTimeImmutable $fecha_inicio,
        private ?\DateTimeImmutable $fecha_fin,
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

    public function getAlcance(): string
    {
        return $this->alcance;
    }

    public function getSedeId(): ?int
    {
        return $this->sede_id;
    }

    public function getDependenciaId(): ?int
    {
        return $this->dependencia_id;
    }

    public function getUbicacionId(): ?int
    {
        return $this->ubicacion_id;
    }

    public function getCategoriaId(): ?int
    {
        return $this->categoria_id;
    }

    public function getResponsableId(): int
    {
        return $this->responsable_id;
    }

    public function getFechaInicio(): \DateTimeImmutable
    {
        return $this->fecha_inicio;
    }

    public function getFechaFin(): ?\DateTimeImmutable
    {
        return $this->fecha_fin;
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
            'alcance' => $this->alcance,
            'sede_id' => $this->sede_id,
            'dependencia_id' => $this->dependencia_id,
            'ubicacion_id' => $this->ubicacion_id,
            'categoria_id' => $this->categoria_id,
            'responsable_id' => $this->responsable_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado,
        ];
    }
}
