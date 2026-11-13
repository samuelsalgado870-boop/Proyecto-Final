<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class CategoriaCampoVO implements JsonSerializable
{
    public function __construct(
        private int $categoria_id,
        private string $campo_codigo,
        private bool $obligatorio
    ) {

    }

    public function getCategoriaId(): int
    {
        return $this->categoria_id;
    }

    public function getCampoCodigo(): string
    {
        return $this->campo_codigo;
    }

    public function getObligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function jsonSerialize(): array
    {
        return [
            'categoria_id' => $this->categoria_id,
            'campo_codigo' => $this->campo_codigo,
            'obligatorio' => $this->obligatorio,
        ];
    }
}
