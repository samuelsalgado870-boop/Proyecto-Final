<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class MovimientoActivoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private int $usuario_id,
        private string $tipo,
        private ?string $motivo,
        private ?int $origen_ubicacion_id,
        private ?int $destino_ubicacion_id,
        private ?int $origen_dependencia_id,
        private ?int $destino_dependencia_id,
        private ?\DateTimeImmutable $fecha_salida,
        private ?\DateTimeImmutable $fecha_recepcion,
        private ?string $observaciones
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

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function getOrigenUbicacionId(): ?int
    {
        return $this->origen_ubicacion_id;
    }

    public function getDestinoUbicacionId(): ?int
    {
        return $this->destino_ubicacion_id;
    }

    public function getOrigenDependenciaId(): ?int
    {
        return $this->origen_dependencia_id;
    }

    public function getDestinoDependenciaId(): ?int
    {
        return $this->destino_dependencia_id;
    }

    public function getFechaSalida(): ?\DateTimeImmutable
    {
        return $this->fecha_salida;
    }

    public function getFechaRecepcion(): ?\DateTimeImmutable
    {
        return $this->fecha_recepcion;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'usuario_id' => $this->usuario_id,
            'tipo' => $this->tipo,
            'motivo' => $this->motivo,
            'origen_ubicacion_id' => $this->origen_ubicacion_id,
            'destino_ubicacion_id' => $this->destino_ubicacion_id,
            'origen_dependencia_id' => $this->origen_dependencia_id,
            'destino_dependencia_id' => $this->destino_dependencia_id,
            'fecha_salida' => $this->fecha_salida,
            'fecha_recepcion' => $this->fecha_recepcion,
            'observaciones' => $this->observaciones,
        ];
    }
}
