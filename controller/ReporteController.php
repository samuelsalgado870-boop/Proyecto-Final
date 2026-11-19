<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\BaseInstitucionalDAO;

final class ReporteController extends ApiController
{
    public function activos(array $params = []): never
    {
        $rawSearch = $_GET['q'] ?? '';
        if (!is_string($rawSearch)) {
            throw new ApiException(400, 'SEARCH_INVALID', 'q debe ser texto.');
        }
        $search = trim($rawSearch);
        if (mb_strlen($search) > 100) {
            throw new ApiException(400, 'SEARCH_INVALID', 'La búsqueda no puede superar 100 caracteres.');
        }
        $this->respond((new BaseInstitucionalDAO($this->pdo))->consultar($search, 500, 0));
    }
}
