<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class ActaParticipanteVO implements JsonSerializable
{
    public function __construct(
        private int $acta_id,
        private int $usuario_id,
        private string $papel
    ) {

    }

    public function getActaId(): int
    {
        return $this->acta_id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getPapel(): string
    {
        return $this->papel;
    }

    public function jsonSerialize(): array
    {
        return [
            'acta_id' => $this->acta_id,
            'usuario_id' => $this->usuario_id,
            'papel' => $this->papel,
        ];
    }
}
