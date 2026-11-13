<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class SolicitudTrasladoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $activo_id,
        private int $origen_ubicacion_id,
        private int $destino_ubicacion_id,
        private ?int $origen_dependencia_id,
        private ?int $destino_dependencia_id,
        private string $motivo,
        private int $solicitante_id,
        private ?\DateTimeImmutable $fecha_propuesta,
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

    public function getOrigenUbicacionId(): int
    {
        return $this->origen_ubicacion_id;
    }

    public function getDestinoUbicacionId(): int
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

    public function getMotivo(): string
    {
        return $this->motivo;
    }

    public function getSolicitanteId(): int
    {
        return $this->solicitante_id;
    }

    public function getFechaPropuesta(): ?\DateTimeImmutable
    {
        return $this->fecha_propuesta;
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
            'origen_ubicacion_id' => $this->origen_ubicacion_id,
            'destino_ubicacion_id' => $this->destino_ubicacion_id,
            'origen_dependencia_id' => $this->origen_dependencia_id,
            'destino_dependencia_id' => $this->destino_dependencia_id,
            'motivo' => $this->motivo,
            'solicitante_id' => $this->solicitante_id,
            'fecha_propuesta' => $this->fecha_propuesta,
            'estado' => $this->estado,
        ];
    }
}
