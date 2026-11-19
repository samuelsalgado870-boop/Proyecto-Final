<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ImportacionDAO;
use App\Model\DAO\AutorizacionDAO;
use App\Model\DAO\BaseInstitucionalDAO;
use PDO;

final class PlanillaImportacionController extends ApiController
{
    private const ASSET_STATES = [
        'REGISTRADO', 'DISPONIBLE', 'ASIGNADO', 'TRASLADO_SOLICITADO', 'EN_TRASLADO',
        'EN_MANTENIMIENTO', 'EN_PRESTAMO', 'DEVUELTO', 'EN_REVISION', 'DADO_DE_BAJA', 'INACTIVO',
    ];

    public function crear(): never
    {
        $migration = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='fila_planilla_activo'");
        $migration->execute();
        if ((int) $migration->fetchColumn() !== 1) {
            throw new ApiException(409, 'MIGRATION_REQUIRED', 'Primero importa database/migraciones/04_planilla_activos_origen.sql en la base configurada.');
        }
        if (!$this->hasFileHashMigration()) {
            throw new ApiException(409, 'MIGRATION_REQUIRED', 'Primero importa database/migraciones/07_planilla_hash.sql para evitar cargar la misma planilla varias veces.');
        }
        $hasCategoryColumn = $this->hasCategoryMigration();
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
        $fileHash = hash_file('sha256', (string) $file['tmp_name']);
        if (!is_string($fileHash)) throw new ApiException(500, 'FILE_HASH_FAILED', 'No se pudo verificar el archivo cargado.');
        $duplicate = $this->pdo->prepare('SELECT id FROM importacion WHERE archivo_sha256=:hash LIMIT 1');
        $duplicate->execute(['hash' => $fileHash]);
        $existingImportId = $duplicate->fetchColumn();
        if ($existingImportId !== false) {
            throw new ApiException(409, 'IMPORT_ALREADY_EXISTS', 'Este archivo ya está guardado como importación ' . (int) $existingImportId . '. Consulta esa importación para ver sus filas.');
        }
        try {
            $planilla = (new PlanillaXlsxReader())->read((string) $file['tmp_name']);
        } catch (\InvalidArgumentException $error) {
            throw new ApiException(400, 'XLSX_INVALID', $error->getMessage());
        } catch (\RuntimeException $error) {
            throw new ApiException(500, 'XLSX_UNAVAILABLE', $error->getMessage());
        }

        $id = (new ImportacionDAO($this->pdo))->crear(basename((string) $file['name']), $this->currentUserId());
        $insertColumns = 'importacion_id,numero_fila,placa,lote,descripcion,ubicacion_fisica,identificacion_responsable,nombre_responsable,centro_costo,nombre_centro_costo,nombre_estado,depreciado';
        $insertValues = ':importacion,:fila,:placa,:lote,:descripcion,:ubicacion,:identificacion,:responsable,:centro,:nombre_centro,:estado,:depreciado';
        if ($hasCategoryColumn) {
            $insertColumns .= ',categoria_origen';
            $insertValues .= ',:categoria';
        }
        $insertColumns .= ',resultado,observacion';
        $insertValues .= ',:resultado,:observacion';
        $insert = $this->pdo->prepare(
            'INSERT INTO fila_planilla_activo (' . $insertColumns . ') VALUES (' . $insertValues . ')'
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
                $parameters = [
                    'importacion' => $id, 'fila' => $source['row'], 'placa' => $plate, 'lote' => $v['Lote'],
                    'descripcion' => $v['Descripcion'], 'ubicacion' => $v['Nom_ubicacion_fisica'],
                    'identificacion' => $v['Identificacion_responsable'], 'responsable' => $v['Nombre_responsable'],
                    'centro' => $v['Centro_costo'], 'nombre_centro' => $v['Nombre_centro_costo'],
                    'estado' => $v['Nombre_estado'], 'depreciado' => $v['Depreciado'],
                    'resultado' => $result, 'observacion' => $observation,
                ];
                if ($hasCategoryColumn) $parameters['categoria'] = $v['Categoria'] ?? null;
                $insert->execute($parameters);
            }
            $saveHash = $this->pdo->prepare('UPDATE importacion SET archivo_sha256=:hash WHERE id=:id');
            $saveHash->execute(['hash' => $fileHash, 'id' => $id]);
            $this->pdo->commit();
        } catch (\PDOException $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            (new ImportacionDAO($this->pdo))->actualizarResultado($id, 0, count($planilla['rows']), 'RECHAZADA');
            if ($error->getCode() === '23000') {
                $duplicate->execute(['hash' => $fileHash]);
                $existingImportId = $duplicate->fetchColumn();
                if ($existingImportId !== false) {
                    throw new ApiException(409, 'IMPORT_ALREADY_EXISTS', 'Este archivo ya está guardado como importación ' . (int) $existingImportId . '. Consulta esa importación para ver sus filas.');
                }
            }
            throw $error;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            (new ImportacionDAO($this->pdo))->actualizarResultado($id, 0, count($planilla['rows']), 'RECHAZADA');
            throw $error;
        }
        $status = $rejected === 0 ? 'PENDIENTE_MAPEO' : ($accepted === 0 ? 'REQUIERE_REVISION' : 'CARGADA_CON_REVISION');
        (new ImportacionDAO($this->pdo))->actualizarResultado($id, $accepted, $rejected, $status);
        $categoryRows = count(array_filter(
            $planilla['rows'],
            static fn (array $source): bool => is_string($source['values']['Categoria'] ?? null)
                && trim($source['values']['Categoria']) !== ''
        ));
        $this->respond([
            'id' => $id,
            'filasLeidas' => count($planilla['rows']),
            'placasUnicas' => $accepted,
            'filasSinPlacaORepetidas' => $rejected,
            'filasConCategoria' => $categoryRows,
            'estado' => $status,
            'mensaje' => 'Las filas fuente quedaron conservadas. La creación de activos operativos requiere mapear los catálogos institucionales.',
        ], 202);
    }

    public function baseInstitucional(): never
    {
        $limit = filter_var($_GET['limite'] ?? 50, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($limit === false || $offset === false) {
            throw new ApiException(400, 'PAGINATION_INVALID', 'limite debe estar entre 1 y 100 y offset debe ser cero o mayor.');
        }
        $rawSearch = $_GET['q'] ?? '';
        if (!is_string($rawSearch)) {
            throw new ApiException(400, 'SEARCH_INVALID', 'q debe ser texto.');
        }
        $search = trim($rawSearch);
        if (mb_strlen($search) > 100) {
            throw new ApiException(400, 'SEARCH_INVALID', 'La búsqueda no puede superar 100 caracteres.');
        }

        try {
            $result = (new BaseInstitucionalDAO($this->pdo))->consultar($search, $limit, $offset);
        } catch (\InvalidArgumentException $error) {
            throw new ApiException(400, 'PAGINATION_INVALID', $error->getMessage());
        }
        $this->respond($result + ['offset' => $offset, 'limite' => $limit]);
    }

    public function baseInstitucionalDetalle(array $params): never
    {
        $numeroFila = $this->routeId($params, 'fila');
        $detalle = (new BaseInstitucionalDAO($this->pdo))->buscarDetalle($numeroFila);
        if ($detalle === null) {
            $this->notFound('No se encontró esa fila en la planilla institucional.');
        }
        $this->respond($detalle);
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
        $conversionColumns = $this->hasConversionMigration()
            ? ',conversion_estado,activo_id,conversion_observacion'
            : '';
        $categoryColumn = $this->hasCategoryMigration() ? ',categoria_origen' : '';
        $stmt = $this->pdo->prepare(
            'SELECT id,numero_fila,placa,lote,descripcion,ubicacion_fisica,identificacion_responsable,nombre_responsable,centro_costo,nombre_centro_costo,nombre_estado,depreciado,resultado,observacion'
            . $conversionColumns . $categoryColumn . ' '
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

    public function opcionesConversion(array $params): never
    {
        $id = $this->routeId($params);
        $userId = $this->requirePermission('activos.ficha.crear');
        $this->assertImportAccess($id, $userId);
        $this->requireConversionMigration();

        $departments = $this->pdo->query("SELECT id,codigo,nombre FROM dependencia WHERE estado='ACTIVA' ORDER BY codigo,nombre")->fetchAll();
        $locations = $this->pdo->query("SELECT id,codigo,nombre,dependencia_id FROM ubicacion WHERE estado='ACTIVA' ORDER BY codigo,nombre")->fetchAll();
        $categories = $this->pdo->query("SELECT id,codigo,nombre FROM categoria_activo WHERE estado='ACTIVA' ORDER BY codigo,nombre")->fetchAll();

        $categoryGroups = $this->sourceGroups('categoria_origen', 'categoria_origen', $id);
        $categoryGroups = array_values(array_filter(
            $categoryGroups,
            static fn (array $group): bool => is_string($group['categoria_origen'] ?? null)
                && trim($group['categoria_origen']) !== ''
        ));
        $dependencyGroups = $this->sourceGroups(
            'centro_costo,nombre_centro_costo',
            'centro_costo,nombre_centro_costo',
            $id
        );
        $locationGroups = $this->sourceGroups('ubicacion_fisica', 'ubicacion_fisica', $id);
        $stateGroups = $this->sourceGroups('nombre_estado', 'nombre_estado', $id);

        foreach ($categoryGroups as &$group) {
            $group['key'] = $this->mappingKey([$group['categoria_origen']]);
            $group['suggestedId'] = $this->suggestId($categories, [
                ['codigo', $group['categoria_origen']],
                ['nombre', $group['categoria_origen']],
            ]);
        }
        unset($group);
        foreach ($dependencyGroups as &$group) {
            $group['key'] = $this->mappingKey([$group['centro_costo'], $group['nombre_centro_costo']]);
            $group['suggestedId'] = $this->suggestId($departments, [
                ['codigo', $group['centro_costo']],
                ['nombre', $group['nombre_centro_costo']],
            ]);
        }
        unset($group);
        foreach ($locationGroups as &$group) {
            $group['key'] = $this->mappingKey([$group['ubicacion_fisica']]);
            $group['suggestedId'] = $this->suggestId($locations, [
                ['codigo', $group['ubicacion_fisica']],
                ['nombre', $group['ubicacion_fisica']],
            ]);
        }
        unset($group);
        foreach ($stateGroups as &$group) {
            $group['key'] = $this->mappingKey([$group['nombre_estado']]);
            $normalized = $this->normalize((string) ($group['nombre_estado'] ?? ''));
            $group['suggestedState'] = in_array($normalized, self::ASSET_STATES, true) ? $normalized : null;
        }
        unset($group);

        $missingCategoryCount = $this->pdo->prepare(
            "SELECT COUNT(*) FROM fila_planilla_activo WHERE importacion_id=:id "
            . "AND resultado='PENDIENTE_MAPEO' AND conversion_estado='PENDIENTE' "
            . "AND (categoria_origen IS NULL OR TRIM(categoria_origen)='')"
        );
        $missingCategoryCount->execute(['id' => $id]);
        $this->respond([
            'importacionId' => $id,
            'categoria' => $categories,
            'dependencias' => $departments,
            'ubicaciones' => $locations,
            'estados' => self::ASSET_STATES,
            'valores' => [
                'categoriasOrigen' => $categoryGroups,
                'centrosCosto' => $dependencyGroups,
                'ubicacionesFisicas' => $locationGroups,
                'estadosOrigen' => $stateGroups,
            ],
            'filasSinCategoria' => (int) $missingCategoryCount->fetchColumn(),
        ]);
    }

    public function previsualizarConversion(array $params): never
    {
        $id = $this->routeId($params);
        $userId = $this->requirePermission('activos.ficha.crear');
        $this->assertImportAccess($id, $userId);
        $this->requireConversionMigration();
        $plan = $this->conversionPlan($id, $userId, $this->body());
        $this->respond([
            'filasPendientes' => count($plan['rows']),
            'creables' => count($plan['ready']),
            'placasYaExistentes' => count($plan['existing']),
            'requierenMapeo' => count($plan['unmapped']),
            'datosInvalidos' => count($plan['invalid']),
            'sinCategoria' => count(array_filter(
                $plan['rows'],
                static fn (array $row): bool => !is_string($row['categoria_origen'] ?? null)
                    || trim($row['categoria_origen']) === ''
            )),
        ]);
    }

    public function convertir(array $params): never
    {
        $id = $this->routeId($params);
        $userId = $this->requirePermission('activos.ficha.crear');
        $this->assertImportAccess($id, $userId);
        $this->requireConversionMigration();
        $plan = $this->conversionPlan($id, $userId, $this->body());
        if ($plan['ready'] === [] && $plan['existing'] === [] && $plan['invalid'] === []) {
            throw new ApiException(409, 'NO_ROWS_READY', 'No hay filas listas para convertirse; revisa los mapeos o las placas existentes.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO activo (placa,descripcion,categoria_id,estado,ubicacion_id,dependencia_id) '
            . 'VALUES (:placa,:descripcion,:categoria,:estado,:ubicacion,:dependencia)'
        );
        $link = $this->pdo->prepare(
            "UPDATE fila_planilla_activo SET resultado='CONVERTIDO',conversion_estado='CONVERTIDO',activo_id=:activo,conversion_observacion=NULL WHERE id=:id AND conversion_estado='PENDIENTE'"
        );
        $markExisting = $this->pdo->prepare(
            "UPDATE fila_planilla_activo SET resultado='PLACA_EXISTENTE',conversion_estado='OMITIDO',conversion_observacion='Ya existe un activo con esta placa.' WHERE id=:id AND conversion_estado='PENDIENTE'"
        );
        $markInvalid = $this->pdo->prepare(
            "UPDATE fila_planilla_activo SET resultado='DATOS_INVALIDOS',conversion_estado='OMITIDO',conversion_observacion=:observacion WHERE id=:id AND conversion_estado='PENDIENTE'"
        );
        $markUnmapped = $this->pdo->prepare(
            'UPDATE fila_planilla_activo SET conversion_observacion=:observacion WHERE id=:id AND conversion_estado=\'PENDIENTE\''
        );

        $created = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($plan['ready'] as $entry) {
                $insert->execute([
                    'placa' => $entry['placa'],
                    'descripcion' => $entry['descripcion'],
                    'categoria' => $entry['categoriaId'],
                    'estado' => $entry['estado'],
                    'ubicacion' => $entry['ubicacionId'],
                    'dependencia' => $entry['dependenciaId'],
                ]);
                $link->execute(['activo' => (int) $this->pdo->lastInsertId(), 'id' => $entry['id']]);
                if ($link->rowCount() !== 1) throw new \RuntimeException('No se pudo vincular una fila de la planilla con su activo.');
                $created++;
            }
            foreach ($plan['existing'] as $row) $markExisting->execute(['id' => $row['id']]);
            foreach ($plan['invalid'] as $row) {
                $markInvalid->execute(['observacion' => $row['reason'], 'id' => $row['id']]);
            }
            foreach ($plan['unmapped'] as $row) {
                $markUnmapped->execute(['observacion' => $row['reason'], 'id' => $row['id']]);
            }
            $this->pdo->commit();
        } catch (\PDOException $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if ($error->getCode() === '23000') {
                throw new ApiException(409, 'CONVERSION_CONFLICT', 'Una placa o referencia cambió durante la conversión. Actualiza la vista previa antes de reintentar.');
            }
            throw $error;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }

        $this->respond([
            'creados' => $created,
            'placasYaExistentes' => count($plan['existing']),
            'requierenMapeo' => count($plan['unmapped']),
            'omitidosPorDatosInvalidos' => count($plan['invalid']),
        ], $created > 0 ? 201 : 200);
    }

    private function conversionPlan(int $importId, int $userId, array $body): array
    {
        $categoryGroups = $this->sourceGroups('categoria_origen', 'categoria_origen', $importId);
        $categoryGroups = array_values(array_filter(
            $categoryGroups,
            static fn (array $group): bool => is_string($group['categoria_origen'] ?? null)
                && trim($group['categoria_origen']) !== ''
        ));
        if ($categoryGroups === []) {
            throw new ApiException(409, 'CATEGORY_SOURCE_REQUIRED', 'Esta importación no contiene categorías por fila. Agrega una columna Categoria al XLSX y vuelve a importarlo.');
        }
        $categoryMappings = $this->readMappings($body['categorias'] ?? null, 'categoriaId');
        $this->validateMappingKeys($categoryMappings, $categoryGroups, ['categoria_origen']);

        $previousCategories = $this->pdo->prepare(
            'SELECT DISTINCT f.categoria_origen,a.categoria_id FROM fila_planilla_activo f '
            . 'JOIN activo a ON a.id=f.activo_id WHERE f.importacion_id=:id'
        );
        $previousCategories->execute(['id' => $importId]);
        foreach ($previousCategories->fetchAll() as $previous) {
            $key = $this->mappingKey([$previous['categoria_origen']]);
            if (array_key_exists($key, $categoryMappings)
                && $categoryMappings[$key] !== (int) $previous['categoria_id']) {
                throw new ApiException(409, 'CATEGORY_LOCKED', 'No cambies la categoría de un valor de origen que ya fue convertido.');
            }
        }
        $dependencyGroups = $this->sourceGroups('centro_costo,nombre_centro_costo', 'centro_costo,nombre_centro_costo', $importId);
        $locationGroups = $this->sourceGroups('ubicacion_fisica', 'ubicacion_fisica', $importId);
        $stateGroups = $this->sourceGroups('nombre_estado', 'nombre_estado', $importId);
        $dependencyMappings = $this->readMappings($body['dependencias'] ?? null, 'dependenciaId');
        $locationMappings = $this->readMappings($body['ubicaciones'] ?? null, 'ubicacionId');
        $stateMappings = $this->readMappings($body['estados'] ?? null, 'estado');
        $this->validateMappingKeys($dependencyMappings, $dependencyGroups, ['centro_costo', 'nombre_centro_costo']);
        $this->validateMappingKeys($locationMappings, $locationGroups, ['ubicacion_fisica']);
        $this->validateMappingKeys($stateMappings, $stateGroups, ['nombre_estado']);

        foreach ($stateMappings as $state) {
            if ($state !== null && !in_array($state, self::ASSET_STATES, true)) {
                throw new ApiException(400, 'STATE_INVALID', 'Uno de los estados asignados no es válido.');
            }
        }

        $departmentIds = array_values(array_unique(array_filter($dependencyMappings, 'is_int')));
        $locationIds = array_values(array_unique(array_filter($locationMappings, 'is_int')));
        $mappedCategoryIds = array_values(array_unique(array_filter($categoryMappings, 'is_int')));
        $categories = $this->activeCatalogByIds('categoria_activo', $mappedCategoryIds);
        $departments = $this->activeCatalogByIds('dependencia', $departmentIds);
        $locations = $this->activeCatalogByIds('ubicacion', $locationIds);
        if (count($categories) !== count($mappedCategoryIds)
            || count($departments) !== count($departmentIds)
            || count($locations) !== count($locationIds)) {
            throw new ApiException(400, 'CATALOG_MAPPING_INVALID', 'Un mapeo apunta a una categoría, dependencia o ubicación inactiva o inexistente.');
        }
        $authorization = new AutorizacionDAO($this->pdo);
        foreach ($locationIds as $locationId) {
            if (!$authorization->dentroDeUbicacion($userId, $locationId)) {
                throw new ApiException(403, 'SCOPE_DENIED', 'Una ubicación seleccionada está fuera de tu ámbito autorizado.');
            }
        }

        $stmt = $this->pdo->prepare(
            "SELECT id,placa,descripcion,categoria_origen,centro_costo,nombre_centro_costo,ubicacion_fisica,nombre_estado "
            . "FROM fila_planilla_activo WHERE importacion_id=:id AND resultado='PENDIENTE_MAPEO' "
            . "AND conversion_estado='PENDIENTE' ORDER BY numero_fila"
        );
        $stmt->execute(['id' => $importId]);
        $rows = $stmt->fetchAll();
        $existingPlates = $this->existingPlateSet($rows);
        $ready = [];
        $existing = [];
        $unmapped = [];
        $invalid = [];

        foreach ($rows as $row) {
            $plate = trim((string) ($row['placa'] ?? ''));
            if ($plate === '' || mb_strlen($plate) > 50) {
                $invalid[] = ['id' => (int) $row['id'], 'reason' => 'La placa está vacía o excede 50 caracteres.'];
                continue;
            }
            if (trim((string) ($row['descripcion'] ?? '')) === '') {
                $invalid[] = ['id' => (int) $row['id'], 'reason' => 'Falta la descripción del activo.'];
                continue;
            }
            $plateKey = mb_strtoupper($plate, 'UTF-8');
            if (isset($existingPlates[$plateKey])) {
                $existing[] = ['id' => (int) $row['id']];
                continue;
            }

            $categoryKey = $this->mappingKey([$row['categoria_origen']]);
            $categoryId = $categoryMappings[$categoryKey] ?? null;
            $departmentKey = $this->mappingKey([$row['centro_costo'], $row['nombre_centro_costo']]);
            $locationKey = $this->mappingKey([$row['ubicacion_fisica']]);
            $stateKey = $this->mappingKey([$row['nombre_estado']]);
            $departmentId = $dependencyMappings[$departmentKey] ?? null;
            $locationId = $locationMappings[$locationKey] ?? null;
            $state = $stateMappings[$stateKey] ?? null;
            if (!is_int($categoryId)) {
                $unmapped[] = ['id' => (int) $row['id'], 'reason' => 'Falta una categoría válida para esta fila.'];
                continue;
            }
            if (!is_int($departmentId) || !is_int($locationId) || !is_string($state)) {
                $unmapped[] = ['id' => (int) $row['id'], 'reason' => 'Falta mapear centro de costo, ubicación o estado.'];
                continue;
            }
            $locationDepartmentId = $locations[$locationId]['dependencia_id'] ?? null;
            if ($locationDepartmentId !== null && (int) $locationDepartmentId !== $departmentId) {
                $unmapped[] = ['id' => (int) $row['id'], 'reason' => 'La dependencia asignada no coincide con la dependencia de la ubicación.'];
                continue;
            }
            $ready[] = [
                'id' => (int) $row['id'],
                'placa' => $plate,
                'descripcion' => trim((string) $row['descripcion']),
                'categoriaId' => $categoryId,
                'estado' => $state,
                'ubicacionId' => $locationId,
                'dependenciaId' => $departmentId,
            ];
        }

        return [
            'rows' => $rows,
            'ready' => $ready,
            'existing' => $existing,
            'unmapped' => $unmapped,
            'invalid' => $invalid,
        ];
    }

    private function assertImportAccess(int $id, int $userId): void
    {
        $item = (new ImportacionDAO($this->pdo))->buscarPorId($id);
        if ($item === null) $this->notFound('No existe la importación.');
        if ($item->getUsuarioId() !== $userId
            && !(new AutorizacionDAO($this->pdo))->tienePermiso($userId, 'usuarios', 'cuenta', 'administrar')) {
            throw new ApiException(403, 'IMPORT_FORBIDDEN', 'No tienes acceso a esta importación.');
        }
    }

    private function hasConversionMigration(): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() "
            . "AND table_name='fila_planilla_activo' AND column_name='activo_id'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn() === 1;
    }

    private function hasCategoryMigration(): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() "
            . "AND table_name='fila_planilla_activo' AND column_name='categoria_origen'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn() === 1;
    }

    private function hasFileHashMigration(): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() "
            . "AND table_name='importacion' AND column_name='archivo_sha256'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn() === 1;
    }

    private function requireConversionMigration(): void
    {
        if (!$this->hasConversionMigration()) {
            throw new ApiException(409, 'MIGRATION_REQUIRED', 'Primero importa database/migraciones/05_planilla_activos_conversion.sql.');
        }
        if (!$this->hasCategoryMigration()) {
            throw new ApiException(409, 'MIGRATION_REQUIRED', 'Primero importa database/migraciones/06_planilla_activos_categoria_origen.sql.');
        }
    }

    private function sourceGroups(string $select, string $group, int $importId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$select},COUNT(*) AS filas FROM fila_planilla_activo "
            . "WHERE importacion_id=:id AND resultado='PENDIENTE_MAPEO' AND conversion_estado='PENDIENTE' "
            . "GROUP BY {$group} ORDER BY {$group}"
        );
        $stmt->execute(['id' => $importId]);
        return $stmt->fetchAll();
    }

    private function readMappings(mixed $entries, string $valueField): array
    {
        if (!is_array($entries) || !array_is_list($entries)) {
            throw new ApiException(400, 'MAPPING_INVALID', 'Los mapeos de catálogo tienen un formato inválido.');
        }
        $mappings = [];
        foreach ($entries as $entry) {
            if (!is_array($entry) || !is_string($entry['key'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $entry['key'])) {
                throw new ApiException(400, 'MAPPING_INVALID', 'Un mapeo de catálogo no es válido.');
            }
            if (array_key_exists($entry['key'], $mappings)) throw new ApiException(400, 'MAPPING_DUPLICATE', 'Hay mapeos repetidos.');
            $value = $entry[$valueField] ?? null;
            if ($value === null || $value === '') {
                $mappings[$entry['key']] = null;
            } elseif ($valueField === 'estado') {
                if (!is_string($value)) throw new ApiException(400, 'MAPPING_INVALID', 'El estado mapeado no es válido.');
                $mappings[$entry['key']] = $value;
            } else {
                $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) throw new ApiException(400, 'MAPPING_INVALID', 'La referencia mapeada debe ser un ID positivo.');
                $mappings[$entry['key']] = $id;
            }
        }
        return $mappings;
    }

    private function validateMappingKeys(array $mappings, array $groups, array $fields): void
    {
        $allowed = [];
        foreach ($groups as $group) {
            $values = [];
            foreach ($fields as $field) $values[] = $group[$field];
            $allowed[$this->mappingKey($values)] = true;
        }
        foreach ($mappings as $key => $_) {
            if (!isset($allowed[$key])) throw new ApiException(400, 'MAPPING_INVALID', 'Un mapeo no corresponde a esta importación.');
        }
    }

    private function activeCatalogByIds(string $table, array $ids): array
    {
        if ($ids === []) return [];
        if (!in_array($table, ['categoria_activo', 'dependencia', 'ubicacion'], true)) throw new \LogicException('Catálogo no permitido.');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $columns = $table === 'ubicacion' ? 'id,dependencia_id' : 'id';
        $stmt = $this->pdo->prepare("SELECT {$columns} FROM {$table} WHERE estado='ACTIVA' AND id IN ({$placeholders})");
        $stmt->execute($ids);
        $catalog = [];
        foreach ($stmt->fetchAll() as $row) $catalog[(int) $row['id']] = $row;
        return $catalog;
    }

    private function existingPlateSet(array $rows): array
    {
        $plates = [];
        foreach ($rows as $row) {
            $plate = trim((string) ($row['placa'] ?? ''));
            if ($plate !== '') $plates[mb_strtoupper($plate, 'UTF-8')] = $plate;
        }
        $existing = [];
        foreach (array_chunk(array_values($plates), 500) as $chunk) {
            $stmt = $this->pdo->prepare('SELECT placa FROM activo WHERE placa IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')');
            $stmt->execute($chunk);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $plate) $existing[mb_strtoupper((string) $plate, 'UTF-8')] = true;
        }
        return $existing;
    }

    private function suggestId(array $catalog, array $fields): ?int
    {
        foreach ($fields as [$field, $source]) {
            if (!is_string($source) || trim($source) === '') continue;
            $matches = array_values(array_filter(
                $catalog,
                fn (array $item): bool => $this->normalize((string) $item[$field]) === $this->normalize($source)
            ));
            if (count($matches) === 1) return (int) $matches[0]['id'];
            if (count($matches) > 1) return null;
        }
        return null;
    }

    private function mappingKey(array $values): string
    {
        return hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function normalize(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }
}
