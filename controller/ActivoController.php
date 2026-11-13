<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ActivoDAO;
use DateTimeImmutable;

final class ActivoController extends ApiController
{
    public function listar(array $params = []): never
    {
        $estado = isset($_GET['estado']) ? (string) $_GET['estado'] : null;
        $categoria = $this->queryInt('categoriaId');
        $dependencia = $this->queryInt('dependenciaId');
        $limit = filter_var($_GET['limite'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            throw new ApiException(400, 'LIMIT_INVALID', 'El límite debe estar entre 1 y 500.');
        }
        $userId = $this->currentUserId();
        $rows = (new ActivoDAO($this->pdo))->buscar($estado, $categoria, $dependencia, $limit);
        $auth = new \App\Model\DAO\AutorizacionDAO($this->pdo);
        $this->respond(array_values(array_filter($rows, fn ($asset): bool => $auth->dentroDeActivo($userId, $asset->getId()))));
    }

    public function crear(array $params = []): never
    {
        $body = $this->body();
        $this->assertLocationScope($this->positiveInt($body, 'ubicacionId'));
        $date = $body['fechaAsignacionPlaca'] ?? null;
        if ($date !== null && (!is_string($date) || !$this->validDate($date, 'Y-m-d'))) {
            throw new ApiException(400, 'DATE_INVALID', 'fechaAsignacionPlaca debe usar YYYY-MM-DD.');
        }
        $dao = new ActivoDAO($this->pdo);
        try {
            $id = $dao->crear(
                $this->requiredString($body, 'placa', 50),
                $date === null ? null : new DateTimeImmutable($date),
                $this->optionalString($body, 'serial', 100),
                $this->requiredString($body, 'descripcion', 5000),
                $this->positiveInt($body, 'categoriaId'),
                $this->optionalString($body, 'marca', 100),
                $this->optionalString($body, 'modelo', 100),
                $this->requiredString($body, 'estado', 30),
                $this->positiveInt($body, 'ubicacionId'),
                $this->positiveInt($body, 'dependenciaId')
            );
        } catch (\PDOException $error) {
            if ($error->getCode() === '23000') {
                $this->conflict('La placa está duplicada o una referencia no existe.');
            }
            throw $error;
        }
        $this->respond(['id' => $id], 201);
    }

    public function mostrar(array $params): never
    {
        $id = $this->routeId($params);
        $this->assertAssetScope($id);
        $this->show($id);
    }

    public function buscarPorPlaca(array $params): never
    {
        $asset = (new ActivoDAO($this->pdo))->buscarPorPlaca($params['placa']);
        if ($asset === null) {
            $this->notFound('No existe un activo con esa placa.');
        }
        $this->assertAssetScope($asset->getId());
        $this->respond($asset);
    }

    public function cambiarEstado(array $params): never
    {
        $id = $this->routeId($params);
        $this->assertAssetScope($id);
        $body = $this->body();
        $estado = $this->requiredString($body, 'estado', 30);
        $valid = ['REGISTRADO','DISPONIBLE','ASIGNADO','TRASLADO_SOLICITADO','EN_TRASLADO','EN_MANTENIMIENTO','EN_PRESTAMO','DEVUELTO','EN_REVISION','DADO_DE_BAJA','INACTIVO'];
        if (!in_array($estado, $valid, true)) {
            throw new ApiException(400, 'STATE_INVALID', 'Estado de activo no reconocido.');
        }
        $dao = new ActivoDAO($this->pdo);
        if (!$dao->cambiarEstado($id, $estado) && $dao->buscarPorId($id) === null) {
            $this->notFound('No existe el activo.');
        }
        $this->show($id);
    }

    private function show(int $id): never
    {
        $asset = (new ActivoDAO($this->pdo))->buscarPorId($id);
        if ($asset === null) {
            $this->notFound('No existe el activo.');
        }
        $this->respond($asset);
    }

    private function queryInt(string $name): ?int
    {
        if (!isset($_GET[$name])) return null;
        $value = filter_var($_GET[$name], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) throw new ApiException(400, 'QUERY_INVALID', "{$name} debe ser entero positivo.");
        return $value;
    }

    private function validDate(string $value, string $format): bool
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        return $date !== false && $date->format($format) === $value;
    }
}
