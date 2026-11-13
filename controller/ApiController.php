<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\AutorizacionDAO;
use App\Model\DAO\ActivoDAO;
use App\Model\VO\InventarioVO;
use PDO;

abstract class ApiController
{
    public function __construct(protected readonly PDO $pdo) {}

    protected function body(): array
    {
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($contentType !== 'application/json') {
            throw new ApiException(400, 'CONTENT_TYPE_INVALID', 'Envía el cuerpo como application/json.');
        }
        $raw = file_get_contents('php://input');
        try {
            $data = json_decode($raw === false ? '' : $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ApiException(400, 'JSON_INVALID', 'El cuerpo JSON no es válido.');
        }
        if (!is_array($data) || array_is_list($data)) {
            throw new ApiException(400, 'JSON_OBJECT_REQUIRED', 'El cuerpo debe ser un objeto JSON.');
        }
        return $data;
    }

    protected function requiredString(array $data, string $key, int $maxLength = 255): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $maxLength) {
            throw new ApiException(400, 'FIELD_INVALID', "El campo {$key} es obligatorio y debe tener máximo {$maxLength} caracteres.");
        }
        return trim($value);
    }

    protected function optionalString(array $data, string $key, int $maxLength = 255): ?string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }
        if (!is_string($data[$key]) || mb_strlen($data[$key]) > $maxLength) {
            throw new ApiException(400, 'FIELD_INVALID', "El campo {$key} no es válido.");
        }
        return trim($data[$key]);
    }

    protected function positiveInt(array $data, string $key, bool $optional = false): ?int
    {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            if ($optional) {
                return null;
            }
            throw new ApiException(400, 'FIELD_REQUIRED', "Falta el campo {$key}.");
        }
        $value = filter_var($data[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) {
            throw new ApiException(400, 'FIELD_INVALID', "El campo {$key} debe ser un entero positivo.");
        }
        return $value;
    }

    protected function routeId(array $params, string $key = 'id'): int
    {
        $value = filter_var($params[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) {
            throw new ApiException(400, 'ID_INVALID', 'El identificador de la ruta no es válido.');
        }
        return $value;
    }

    protected function currentUserId(): int
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) || $id < 1) {
            throw new ApiException(401, 'AUTH_REQUIRED', 'Debes iniciar sesión.');
        }
        return $id;
    }

    protected function requirePermission(string $permission): int
    {
        $userId = $this->currentUserId();
        [$module, $resource, $action] = array_pad(explode('.', $permission, 3), 3, '');
        if (
            $module === '' || $resource === '' || $action === ''
            || !(new AutorizacionDAO($this->pdo))->tienePermiso($userId, $module, $resource, $action)
        ) {
            throw new ApiException(403, 'PERMISSION_DENIED', 'No tienes permiso para esta operación.');
        }
        return $userId;
    }

    protected function assertAssetScope(int $assetId): void
    {
        $asset = (new ActivoDAO($this->pdo))->buscarPorId($assetId);
        if ($asset === null) $this->notFound('No existe el activo.');
        if (!(new AutorizacionDAO($this->pdo))->dentroDeActivo($this->currentUserId(), $assetId)) {
            throw new ApiException(403, 'SCOPE_DENIED', 'El activo está fuera de tu ámbito autorizado.');
        }
    }

    protected function assertLocationScope(int $locationId): void
    {
        if (!(new AutorizacionDAO($this->pdo))->dentroDeUbicacion($this->currentUserId(), $locationId)) {
            throw new ApiException(403, 'SCOPE_DENIED', 'La ubicación está fuera de tu ámbito autorizado.');
        }
    }

    protected function assertInventoryScope(InventarioVO $inventory): void
    {
        $authorized = (new AutorizacionDAO($this->pdo))->dentroDeAmbito(
            $this->currentUserId(),
            $inventory->getSedeId(),
            $inventory->getDependenciaId(),
            $inventory->getUbicacionId()
        );
        if (!$authorized) {
            throw new ApiException(403, 'SCOPE_DENIED', 'El inventario está fuera de tu ámbito autorizado.');
        }
    }

    protected function respond(mixed $data = null, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        if ($status !== 204) {
            echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        exit;
    }

    protected function notFound(string $message = 'No se encontró el recurso.'): never
    {
        throw new ApiException(404, 'NOT_FOUND', $message);
    }

    protected function conflict(string $message): never
    {
        throw new ApiException(409, 'CONFLICT', $message);
    }
}
