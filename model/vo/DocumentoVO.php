<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class DocumentoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $nombre_original,
        private string $tipo,
        private ?int $tamano_bytes,
        private int $version,
        private \DateTimeImmutable $fecha,
        private ?int $responsable_id,
        private string $ruta_privada
    ) {
        if ($this->version < 0) {
            throw new \InvalidArgumentException('version no puede ser negativo.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombreOriginal(): string
    {
        return $this->nombre_original;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getTamanoBytes(): ?int
    {
        return $this->tamano_bytes;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getResponsableId(): ?int
    {
        return $this->responsable_id;
    }

    public function getRutaPrivada(): string
    {
        return $this->ruta_privada;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nombre_original' => $this->nombre_original,
            'tipo' => $this->tipo,
            'tamano_bytes' => $this->tamano_bytes,
            'version' => $this->version,
            'fecha' => $this->fecha,
            'responsable_id' => $this->responsable_id,
            'ruta_privada' => $this->ruta_privada,
        ];
    }
}
