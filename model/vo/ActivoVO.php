<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class ActivoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $placa,
        private ?\DateTimeImmutable $fecha_asignacion_placa,
        private ?string $serial,
        private string $descripcion,
        private int $categoria_id,
        private ?string $marca,
        private ?string $modelo,
        private string $estado,
        private int $ubicacion_id,
        private int $dependencia_id,
        private \DateTimeImmutable $creado_en,
        private string $estado_validacion = 'PENDIENTE'
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlaca(): string
    {
        return $this->placa;
    }

    public function getFechaAsignacionPlaca(): ?\DateTimeImmutable
    {
        return $this->fecha_asignacion_placa;
    }

    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getCategoriaId(): int
    {
        return $this->categoria_id;
    }

    public function getMarca(): ?string
    {
        return $this->marca;
    }

    public function getModelo(): ?string
    {
        return $this->modelo;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getUbicacionId(): int
    {
        return $this->ubicacion_id;
    }

    public function getDependenciaId(): int
    {
        return $this->dependencia_id;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creado_en;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'placa' => $this->placa,
            'fecha_asignacion_placa' => $this->fecha_asignacion_placa,
            'serial' => $this->serial,
            'descripcion' => $this->descripcion,
            'categoria_id' => $this->categoria_id,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'estado' => $this->estado,
            'ubicacion_id' => $this->ubicacion_id,
            'dependencia_id' => $this->dependencia_id,
            'creado_en' => $this->creado_en,
            'estado_validacion' => $this->estado_validacion,
        ];
    }
}
