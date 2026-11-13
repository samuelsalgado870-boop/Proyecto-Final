<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class ActaVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $tipo,
        private string $numero,
        private \DateTimeImmutable $fecha,
        private int $version,
        private ?int $documento_id,
        private string $operacion_tipo,
        private int $operacion_id
    ) {
        if ($this->version < 0) {
            throw new \InvalidArgumentException('version no puede ser negativo.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getDocumentoId(): ?int
    {
        return $this->documento_id;
    }

    public function getOperacionTipo(): string
    {
        return $this->operacion_tipo;
    }

    public function getOperacionId(): int
    {
        return $this->operacion_id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'numero' => $this->numero,
            'fecha' => $this->fecha,
            'version' => $this->version,
            'documento_id' => $this->documento_id,
            'operacion_tipo' => $this->operacion_tipo,
            'operacion_id' => $this->operacion_id,
        ];
    }
}
