<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\NotificacionVO;

final class NotificacionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'notificacion';
    protected const VO = NotificacionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'usuario_id', 'tipo', 'fecha', 'estado', 'referencia_tipo', 'referencia_id'];

    public function marcarLeida(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notificacion SET estado = 'LEIDA' WHERE id = :id AND estado = 'PENDIENTE'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function marcarLeidaParaUsuario(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notificacion SET estado = 'LEIDA' WHERE id = :id AND usuario_id = :usuario AND estado = 'PENDIENTE'");
        $stmt->execute(['id' => $id, 'usuario' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }
}
