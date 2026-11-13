<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ProcesoSeleccionDAO;

final class ProcesoSeleccionController extends ApiController
{
    public function unidades(array $params = []): never
    {
        $this->respond((new ProcesoSeleccionDAO($this->pdo))->listarDependenciasActivas());
    }

    public function listar(array $params = []): never
    {
        $estado = isset($_GET['estado']) && is_string($_GET['estado']) && $_GET['estado'] !== '' ? strtoupper($_GET['estado']) : null;
        $dependencia = isset($_GET['dependenciaId']) ? filter_var($_GET['dependenciaId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
        if ($dependencia === false) throw new ApiException(400, 'FIELD_INVALID', 'dependenciaId debe ser un entero positivo.');
        $q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : null;
        $this->respond((new ProcesoSeleccionDAO($this->pdo))->listar($estado, $dependencia === null ? null : (int) $dependencia, $q, 100));
    }

    public function crear(array $params = []): never
    {
        $body = $this->body();
        $nombre = $this->requiredString($body, 'nombre', 180);
        $dependenciaId = $this->positiveInt($body, 'dependenciaId');
        $estado = strtoupper($this->optionalString($body, 'estado', 25) ?? 'BORRADOR');
        if (!in_array($estado, ['BORRADOR','ABIERTO'], true)) {
            throw new ApiException(400, 'STATE_INVALID', 'El estado inicial debe ser BORRADOR o ABIERTO.');
        }
        $apertura = $this->optionalDate($body, 'fechaApertura');
        $cierre = $this->optionalDate($body, 'fechaCierre');
        if ($apertura !== null && $cierre !== null && $cierre < $apertura) {
            throw new ApiException(400, 'DATE_RANGE_INVALID', 'La fecha de cierre debe ser igual o posterior a la apertura.');
        }
        try {
            $id = (new ProcesoSeleccionDAO($this->pdo))->crear($nombre, $dependenciaId, $estado, $apertura, $cierre, $this->currentUserId());
        } catch (\PDOException $error) {
            if ($error->getCode() === '23000') throw new ApiException(404, 'DEPENDENCY_NOT_FOUND', 'La dependencia indicada no existe.');
            throw $error;
        }
        $this->respond(['id' => $id], 201);
    }

    public function cambiarEstado(array $params): never
    {
        $body = $this->body();
        $estado = strtoupper($this->requiredString($body, 'estado', 25));
        if (!in_array($estado, ['BORRADOR','ABIERTO','EN_REVISION','CERRADO','FINALIZADO'], true)) {
            throw new ApiException(400, 'STATE_INVALID', 'El estado del proceso no es válido.');
        }
        $dao = new ProcesoSeleccionDAO($this->pdo);
        $id = $this->routeId($params);
        if ($dao->buscarPorId($id) === null) $this->notFound('No existe el proceso de selección.');
        $dao->cambiarEstado($id, $estado, $this->currentUserId());
        $this->respond(['id' => $id, 'estado' => $estado]);
    }

    public function postulaciones(array $params): never
    {
        $id = $this->routeId($params);
        $dao = new ProcesoSeleccionDAO($this->pdo);
        if ($dao->buscarPorId($id) === null) $this->notFound('No existe el proceso de selección.');
        $q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : null;
        $this->respond($dao->listarPostulaciones($id, $q));
    }

    public function crearPostulacion(array $params): never
    {
        $body = $this->body();
        $nombre = $this->requiredString($body, 'nombre', 150);
        $correo = $this->requiredString($body, 'correo', 190);
        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) throw new ApiException(400, 'EMAIL_INVALID', 'El correo no tiene un formato válido.');
        try {
            $id = (new ProcesoSeleccionDAO($this->pdo))->registrarPostulacion($this->routeId($params), $nombre, mb_strtolower($correo), $this->currentUserId());
        } catch (\OutOfBoundsException $error) {
            throw new ApiException(404, 'PROCESS_NOT_FOUND', $error->getMessage());
        } catch (\DomainException $error) {
            throw new ApiException(409, 'PROCESS_CLOSED', $error->getMessage());
        } catch (\PDOException $error) {
            if ($error->getCode() === '23000') throw new ApiException(409, 'APPLICATION_EXISTS', 'El postulante ya está registrado en este proceso.');
            throw $error;
        }
        $this->respond(['id' => $id], 201);
    }

    private function optionalDate(array $body, string $key): ?string
    {
        $value = $this->optionalString($body, $key, 10);
        if ($value === null) return null;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            throw new ApiException(400, 'DATE_INVALID', "{$key} debe usar el formato AAAA-MM-DD.");
        }
        return $value;
    }
}
