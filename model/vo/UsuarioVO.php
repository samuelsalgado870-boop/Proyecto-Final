<?php

declare(strict_types=1);

namespace App\Model\VO;

use JsonSerializable;

final class UsuarioVO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $identificacion,
        private string $nombre,
        private string $correo,
        private string $password_hash,
        private string $estado,
        private ?\DateTimeImmutable $ultimo_acceso,
        private \DateTimeImmutable $creado_en
    ) {
        if (filter_var($this->correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('El correo no es v?lido.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentificacion(): string
    {
        return $this->identificacion;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getUltimoAcceso(): ?\DateTimeImmutable
    {
        return $this->ultimo_acceso;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creado_en;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'identificacion' => $this->identificacion,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'estado' => $this->estado,
            'ultimo_acceso' => $this->ultimo_acceso,
            'creado_en' => $this->creado_en,
        ];
    }
}
