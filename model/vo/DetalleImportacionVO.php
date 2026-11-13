<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class DetalleImportacionVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private int $importacion_id,
        private int $numero_fila,
        private string $resultado,
        private ?string $error,
        private ?int $posible_duplicado_id
    ) {
        if ($this->numero_fila < 0) {
            throw new \InvalidArgumentException('numero_fila no puede ser negativo.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getImportacionId(): int
    {
        return $this->importacion_id;
    }

    public function getNumeroFila(): int
    {
        return $this->numero_fila;
    }

    public function getResultado(): string
    {
        return $this->resultado;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getPosibleDuplicadoId(): ?int
    {
        return $this->posible_duplicado_id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'importacion_id' => $this->importacion_id,
            'numero_fila' => $this->numero_fila,
            'resultado' => $this->resultado,
            'error' => $this->error,
            'posible_duplicado_id' => $this->posible_duplicado_id,
        ];
    }
}
