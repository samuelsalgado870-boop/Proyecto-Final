<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class PermisoVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $modulo,
        private string $recurso,
        private string $accion
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getModulo(): string
    {
        return $this->modulo;
    }

    public function getRecurso(): string
    {
        return $this->recurso;
    }

    public function getAccion(): string
    {
        return $this->accion;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'modulo' => $this->modulo,
            'recurso' => $this->recurso,
            'accion' => $this->accion,
        ];
    }
}
