<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class RolPermisoVO implements JsonSerializable
{
    public function __construct(
        private int $rol_id,
        private int $permiso_id
    ) {

    }

    public function getRolId(): int
    {
        return $this->rol_id;
    }

    public function getPermisoId(): int
    {
        return $this->permiso_id;
    }

    public function jsonSerialize(): array
    {
        return [
            'rol_id' => $this->rol_id,
            'permiso_id' => $this->permiso_id,
        ];
    }
}
