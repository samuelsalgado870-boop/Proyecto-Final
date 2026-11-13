<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class AmbitoAccesoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $usuario_id,
        private string $tipo_ambito,
        private ?int $sede_id,
        private ?int $dependencia_id,
        private ?int $ubicacion_id,
        private ?\DateTimeImmutable $vigente_desde,
        private ?\DateTimeImmutable $vigente_hasta
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getTipoAmbito(): string
    {
        return $this->tipo_ambito;
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

    public function getVigenteDesde(): ?\DateTimeImmutable
    {
        return $this->vigente_desde;
    }

    public function getVigenteHasta(): ?\DateTimeImmutable
    {
        return $this->vigente_hasta;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'tipo_ambito' => $this->tipo_ambito,
            'sede_id' => $this->sede_id,
            'dependencia_id' => $this->dependencia_id,
            'ubicacion_id' => $this->ubicacion_id,
            'vigente_desde' => $this->vigente_desde,
            'vigente_hasta' => $this->vigente_hasta,
        ];
    }
}
