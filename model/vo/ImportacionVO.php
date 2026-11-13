<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class ImportacionVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $nombre_archivo,
        private int $usuario_id,
        private \DateTimeImmutable $fecha,
        private int $registros_aceptados,
        private int $registros_rechazados,
        private string $estado
    ) {
        if ($this->registros_aceptados < 0) {
            throw new \InvalidArgumentException('registros_aceptados no puede ser negativo.');
        }
        if ($this->registros_rechazados < 0) {
            throw new \InvalidArgumentException('registros_rechazados no puede ser negativo.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombreArchivo(): string
    {
        return $this->nombre_archivo;
    }

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getRegistrosAceptados(): int
    {
        return $this->registros_aceptados;
    }

    public function getRegistrosRechazados(): int
    {
        return $this->registros_rechazados;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nombre_archivo' => $this->nombre_archivo,
            'usuario_id' => $this->usuario_id,
            'fecha' => $this->fecha,
            'registros_aceptados' => $this->registros_aceptados,
            'registros_rechazados' => $this->registros_rechazados,
            'estado' => $this->estado,
        ];
    }
}
