<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ReporteDAO;

final class ReporteController extends ApiController
{
    public function activos(array $params = []): never
    {
        $filters = [];
        foreach (['sedeId' => 'sede', 'dependenciaId' => 'dependencia', 'ubicacionId' => 'ubicacion', 'categoriaId' => 'categoria'] as $query => $key) {
            $value = $_GET[$query] ?? null;
            if ($value === null || $value === '') { $filters[$key] = null; continue; }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new ApiException(400, 'QUERY_INVALID', "{$query} inválido.");
            $filters[$key] = $id;
        }
        $estado = isset($_GET['estado']) && is_string($_GET['estado']) ? $_GET['estado'] : null;
        $userId = $this->currentUserId();
        $rows = (new ReporteDAO($this->pdo))->activos($filters['sede'], $filters['dependencia'], $filters['ubicacion'], $filters['categoria'], $estado);
        $auth = new \App\Model\DAO\AutorizacionDAO($this->pdo);
        $this->respond(array_values(array_filter($rows, fn (array $row): bool => $auth->dentroDeActivo($userId, (int) $row['id']))));
    }
}
