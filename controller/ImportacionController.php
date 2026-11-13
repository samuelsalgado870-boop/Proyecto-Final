<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ActivoDAO;
use App\Model\DAO\AutorizacionDAO;
use App\Model\DAO\DetalleImportacionDAO;
use App\Model\DAO\ImportacionDAO;

final class ImportacionController extends ApiController
{
    public function crear(array $params = []): never
    {
        $file = $_FILES['archivo'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new ApiException(400, 'UPLOAD_INVALID', 'Falta un CSV válido.');
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > 20 * 1024 * 1024) throw new ApiException(413, 'FILE_TOO_LARGE', 'El CSV debe pesar hasta 20 MB.');
        if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'csv') throw new ApiException(415, 'FILE_TYPE_UNSUPPORTED', 'La importación acepta archivos CSV.');
        $stream = fopen($file['tmp_name'], 'rb');
        if ($stream === false) throw new ApiException(400, 'CSV_INVALID', 'No se pudo leer el CSV.');
        $header = fgetcsv($stream);
        $required = ['placa','descripcion','categoria_id','ubicacion_id','dependencia_id'];
        if (!is_array($header) || array_diff($required, $header) !== []) {
            fclose($stream);
            throw new ApiException(400, 'CSV_HEADER_INVALID', 'El CSV debe incluir placa, descripcion, categoria_id, ubicacion_id y dependencia_id.');
        }
        $indices = array_flip($header);
        $imports = new ImportacionDAO($this->pdo);
        $details = new DetalleImportacionDAO($this->pdo);
        $assets = new ActivoDAO($this->pdo);
        $auth = new AutorizacionDAO($this->pdo);
        $actorId = $this->currentUserId();
        $id = $imports->crear(basename((string) $file['name']), $actorId);
        $accepted = 0;
        $rejected = 0;
        $line = 1;
        while (($values = fgetcsv($stream)) !== false) {
            $line++;
            $record = [];
            foreach ($indices as $key => $index) $record[$key] = $values[$index] ?? null;
            $duplicateId = null;
            try {
                foreach ($required as $field) if (!is_string($record[$field]) || trim($record[$field]) === '') throw new \InvalidArgumentException('Falta el campo ' . $field . '.');
                $duplicate = $assets->buscarPorPlaca(trim($record['placa']));
                if ($duplicate !== null) { $duplicateId = $duplicate->getId(); throw new \InvalidArgumentException('La placa ya existe.'); }
                $categoryId = filter_var($record['categoria_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $locationId = filter_var($record['ubicacion_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $dependencyId = filter_var($record['dependencia_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($categoryId === false || $locationId === false || $dependencyId === false) throw new \InvalidArgumentException('Las referencias deben ser IDs positivos.');
                if (!$auth->dentroDeUbicacion($actorId, $locationId)) throw new \InvalidArgumentException('La ubicación está fuera del ámbito autorizado.');
                $assets->crear(trim($record['placa']), null, $record['serial'] ?? null, trim($record['descripcion']), $categoryId,
                    $record['marca'] ?? null, $record['modelo'] ?? null, $record['estado'] ?? 'REGISTRADO', $locationId, $dependencyId);
                $details->registrar($id, $line, 'ACEPTADO', null, null);
                $accepted++;
            } catch (\InvalidArgumentException $e) {
                $details->registrar($id, $line, 'RECHAZADO', $e->getMessage(), $duplicateId);
                $rejected++;
            } catch (\PDOException $e) {
                $details->registrar($id, $line, 'RECHAZADO', $e->getCode() === '23000' ? 'La categoría, ubicación o dependencia no existe.' : 'No se pudo guardar la fila.', $duplicateId);
                $rejected++;
            }
        }
        fclose($stream);
        $status = $rejected === 0 ? 'COMPLETADA' : ($accepted === 0 ? 'RECHAZADA' : 'COMPLETADA_CON_ERRORES');
        $imports->actualizarResultado($id, $accepted, $rejected, $status);
        $this->respond(['id' => $id, 'registrosAceptados' => $accepted, 'registrosRechazados' => $rejected, 'estado' => $status], 202);
    }

    public function mostrar(array $params): never
    {
        $id = $this->routeId($params);
        $dao = new ImportacionDAO($this->pdo);
        $item = $dao->buscarPorId($id);
        if ($item === null) $this->notFound('No existe la importación.');
        if ($item->getResponsableId() !== $this->currentUserId()
            && !(new AutorizacionDAO($this->pdo))->tienePermiso($this->currentUserId(), 'usuarios', 'cuenta', 'administrar')) {
            throw new ApiException(403, 'IMPORT_FORBIDDEN', 'No tienes acceso a esta importación.');
        }
        $details = (new DetalleImportacionDAO($this->pdo))->buscarPorCampo('importacion_id', $id, 500);
        $this->respond(['importacion' => $item, 'detalle' => $details]);
    }
}
