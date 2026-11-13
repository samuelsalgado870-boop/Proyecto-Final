<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class InventarioDetalleVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $inventario_id,
        private ?int $activo_id,
        private ?int $verificador_id,
        private string $resultado,
        private ?string $placa_observada,
        private ?string $ubicacion_observada,
        private ?string $condicion_fisica,
        private \DateTimeImmutable $fecha_verificacion,
        private ?string $observacion
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getInventarioId(): int
    {
        return $this->inventario_id;
    }

    public function getActivoId(): ?int
    {
        return $this->activo_id;
    }

    public function getVerificadorId(): ?int
    {
        return $this->verificador_id;
    }

    public function getResultado(): string
    {
        return $this->resultado;
    }

    public function getPlacaObservada(): ?string
    {
        return $this->placa_observada;
    }

    public function getUbicacionObservada(): ?string
    {
        return $this->ubicacion_observada;
    }

    public function getCondicionFisica(): ?string
    {
        return $this->condicion_fisica;
    }

    public function getFechaVerificacion(): \DateTimeImmutable
    {
        return $this->fecha_verificacion;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'inventario_id' => $this->inventario_id,
            'activo_id' => $this->activo_id,
            'verificador_id' => $this->verificador_id,
            'resultado' => $this->resultado,
            'placa_observada' => $this->placa_observada,
            'ubicacion_observada' => $this->ubicacion_observada,
            'condicion_fisica' => $this->condicion_fisica,
            'fecha_verificacion' => $this->fecha_verificacion,
            'observacion' => $this->observacion,
        ];
    }
}
