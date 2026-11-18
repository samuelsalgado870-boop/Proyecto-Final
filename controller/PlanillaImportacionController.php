<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ImportacionDAO;
use PDO;

final class PlanillaImportacionController extends ApiController
{
    public function crear(): never
    {
        $migration = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='fila_planilla_activo'");
        $migration->execute();
        if ((int) $migration->fetchColumn() !== 1) {
            throw new ApiException(409, 'MIGRATION_REQUIRED', 'Primero importa database/04_planilla_activos_origen.sql en la base configurada.');
        }
        $file = $_FILES['archivo'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new ApiException(400, 'UPLOAD_INVALID', 'Selecciona un archivo XLSX válido.');
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > 100 * 1024 * 1024) {
            throw new ApiException(413, 'FILE_TOO_LARGE', 'El archivo debe pesar hasta 100 MB.');
        }
        if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new ApiException(415, 'FILE_TYPE_UNSUPPORTED', 'Esta carga acepta archivos XLSX.');
        }
        try {
            $planilla = (new PlanillaXlsxReader())->read((string) $file['tmp_name']);
        } catch (\InvalidArgumentException $error) {
            throw new ApiException(400, 'XLSX_INVALID', $error->getMessage());
        } catch (\RuntimeException $error) {
            throw new ApiException(500, 'XLSX_UNAVAILABLE', $error->getMessage());
        }

        $id = (new ImportacionDAO($this->pdo))->crear(basename((string) $file['name']), $this->currentUserId());
        $insert = $this->pdo->prepare(
            'INSERT INTO fila_planilla_activo '
            . '(importacion_id,numero_fila,placa,lote,descripcion,ubicacion_fisica,identificacion_responsable,nombre_responsable,centro_costo,nombre_centro_costo,nombre_estado,depreciado,resultado,observacion) '
            . 'VALUES (:importacion,:fila,:placa,:lote,:descripcion,:ubicacion,:identificacion,:responsable,:centro,:nombre_centro,:estado,:depreciado,:resultado,:observacion)'
        );
        $plates = [];
        $accepted = 0;
        $rejected = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($planilla['rows'] as $source) {
                $v = $source['values'];
                $plate = $v['Placa'];
                $key = $plate === null ? null : mb_strtoupper($plate, 'UTF-8');
                if ($key === null) {
                    $result = 'SIN_PLACA';
                    $observation = 'La fila fuente no tiene placa; se conserva para revisión.';
                    $rejected++;
                } elseif (isset($plates[$key])) {
                    $result = 'PLACA_REPETIDA';
                    $observation = 'La placa también aparece en la fila ' . $plates[$key] . '.';
                    $rejected++;
                } else {
                    $plates[$key] = $source['row'];
                    $result = 'PENDIENTE_MAPEO';
                    $observation = null;
                    $accepted++;
                }
                $insert->execute([
                    'importacion' => $id, 'fila' => $source['row'], 'placa' => $plate, 'lote' => $v['Lote'],
                    'descripcion' => $v['Descripcion'], 'ubicacion' => $v['Nom_ubicacion_fisica'],
                    'identificacion' => $v['Identificacion_responsable'], 'responsable' => $v['Nombre_responsable'],
                    'centro' => $v['Centro_costo'], 'nombre_centro' => $v['Nombre_centro_costo'],
                    'estado' => $v['Nombre_estado'], 'depreciado' => $v['Depreciado'],
                    'resultado' => $result, 'observacion' => $observation,
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            (new ImportacionDAO($this->pdo))->actualizarResultado($id, 0, count($planilla['rows']), 'RECHAZADA');
            throw $error;
        }
        $status = $rejected === 0 ? 'PENDIENTE_MAPEO' : ($accepted === 0 ? 'REQUIERE_REVISION' : 'CARGADA_CON_REVISION');
        (new ImportacionDAO($this->pdo))->actualizarResultado($id, $accepted, $rejected, $status);
        $this->respond([
            'id' => $id,
            'filasLeidas' => count($planilla['rows']),
            'placasUnicas' => $accepted,
            'filasSinPlacaORepetidas' => $rejected,
            'estado' => $status,
            'mensaje' => 'Las filas fuente quedaron conservadas. La creación de activos operativos requiere mapear los catálogos institucionales.',
        ], 202);
    }

    public function mostrar(array $params): never
    {
        $id = $this->routeId($params);
        $imports = new ImportacionDAO($this->pdo);
        $item = $imports->buscarPorId($id);
        if ($item === null) $this->notFound('No existe la importación.');
        if ($item->getUsuarioId() !== $this->currentUserId()
            && !(new \App\Model\DAO\AutorizacionDAO($this->pdo))->tienePermiso($this->currentUserId(), 'usuarios', 'cuenta', 'administrar')) {
            throw new ApiException(403, 'IMPORT_FORBIDDEN', 'No tienes acceso a esta importación.');
        }
        $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($offset === false) throw new ApiException(400, 'OFFSET_INVALID', 'offset debe ser cero o un entero positivo.');
        $stmt = $this->pdo->prepare(
            'SELECT numero_fila,placa,lote,descripcion,ubicacion_fisica,identificacion_responsable,nombre_responsable,centro_costo,nombre_centro_costo,nombre_estado,depreciado,resultado,observacion '
            . 'FROM fila_planilla_activo WHERE importacion_id=:id ORDER BY numero_fila LIMIT 20 OFFSET :offset'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $total = $this->pdo->prepare('SELECT COUNT(*) FROM fila_planilla_activo WHERE importacion_id=:id');
        $total->execute(['id' => $id]);
        $this->respond(['importacion' => $item, 'filas' => $rows, 'totalFilasFuente' => (int) $total->fetchColumn(), 'offset' => $offset]);
    }
}
