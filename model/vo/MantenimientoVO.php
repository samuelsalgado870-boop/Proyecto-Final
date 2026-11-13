<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class MantenimientoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private string $diagnostico,
        private ?string $acciones,
        private ?string $tecnico_proveedor,
        private \DateTimeImmutable $fecha_inicio,
        private ?\DateTimeImmutable $fecha_fin,
        private ?string $resultado,
        private string $estado
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getActivoId(): int
    {
        return $this->activo_id;
    }

    public function getDiagnostico(): string
    {
        return $this->diagnostico;
    }

    public function getAcciones(): ?string
    {
        return $this->acciones;
    }

    public function getTecnicoProveedor(): ?string
    {
        return $this->tecnico_proveedor;
    }

    public function getFechaInicio(): \DateTimeImmutable
    {
        return $this->fecha_inicio;
    }

    public function getFechaFin(): ?\DateTimeImmutable
    {
        return $this->fecha_fin;
    }

    public function getResultado(): ?string
    {
        return $this->resultado;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'diagnostico' => $this->diagnostico,
            'acciones' => $this->acciones,
            'tecnico_proveedor' => $this->tecnico_proveedor,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'resultado' => $this->resultado,
            'estado' => $this->estado,
        ];
    }
}
