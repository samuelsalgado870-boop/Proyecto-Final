<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\NotificacionDAO;

final class NotificacionController extends ApiController
{
    public function listar(array $params = []): never
    {
        $userId = $this->currentUserId();
        $limit = filter_var($_GET['limite'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) throw new ApiException(400, 'LIMIT_INVALID', 'El límite debe estar entre 1 y 500.');
        $rows = (new NotificacionDAO($this->pdo))->buscarPorCampo('usuario_id', $userId, $limit);
        $state = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : null;
        if ($state !== null) $rows = array_values(array_filter($rows, static fn ($row): bool => $row->getEstado() === $state));
        $this->respond($rows);
    }

    public function marcarLeida(array $params): never
    {
        $id = $this->routeId($params);
        $userId = $this->currentUserId();
        $dao = new NotificacionDAO($this->pdo);
        if (!$dao->marcarLeidaParaUsuario($id, $userId)) {
            if ($dao->buscarPorId($id) === null) $this->notFound('No existe la notificación.');
            throw new ApiException(403, 'NOTIFICATION_FORBIDDEN', 'La notificación no pertenece a tu cuenta o ya fue leída.');
        }
        $this->respond($dao->buscarPorId($id));
    }
}
