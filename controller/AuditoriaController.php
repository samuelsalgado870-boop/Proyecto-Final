<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\AuditoriaDAO;

final class AuditoriaController extends ApiController
{
    public function listar(array $params = []): never
    {
        $dao = new AuditoriaDAO($this->pdo);
        $limit = filter_var($_GET['limite'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) throw new ApiException(400, 'LIMIT_INVALID', 'El límite debe estar entre 1 y 500.');
        $entityId = null;
        if (isset($_GET['entidadId'])) {
            $id = filter_var($_GET['entidadId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new ApiException(400, 'QUERY_INVALID', 'entidadId inválido.');
            $entityId = $id;
        }
        $entity = isset($_GET['entidad']) && is_string($_GET['entidad']) ? trim($_GET['entidad']) : null;
        $this->respond($dao->listar($entity === '' ? null : $entity, $entityId, $limit));
    }
}
