<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\DocumentoDAO;
use App\Model\DAO\AutorizacionDAO;
use App\Model\DAO\ActaDAO;

final class DocumentoController extends ApiController
{
    public function subir(array $params = []): never
    {
        $file = $_FILES['archivo'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new ApiException(400, 'UPLOAD_INVALID', 'Falta un archivo válido.');
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > 10 * 1024 * 1024) {
            throw new ApiException(413, 'FILE_TOO_LARGE', 'El archivo debe pesar hasta 10 MB.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg'];
        if (!isset($allowed[$mime])) throw new ApiException(415, 'FILE_TYPE_UNSUPPORTED', 'Solo se aceptan PDF, PNG o JPEG.');
        $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'documents';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo preparar el almacenamiento privado.');
        }
        $storedName = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
        $absolute = $directory . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $absolute)) throw new \RuntimeException('No se pudo guardar el archivo.');
        $relative = 'storage/private/documents/' . $storedName;
        try {
            $type = strtoupper(trim((string) ($_POST['operacionTipo'] ?? '')));
            $operationId = filter_var($_POST['operacionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (($type === '') !== ($operationId === false || $operationId === null)) throw new ApiException(400, 'OPERATION_LINK_INVALID', 'Indica juntos el tipo y el ID de la operación.');
            $this->pdo->beginTransaction();
            $dao = new DocumentoDAO($this->pdo);
            $id = $dao->registrar(mb_substr(basename((string) $file['name']), 0, 255), $mime, (int) $file['size'], $this->currentUserId(), $relative);
            if ($type !== '') {
                $operation = $this->operationColumn($type);
                $this->validarOperacion($operation, (int) $operationId);
                $insert = $this->pdo->prepare('INSERT INTO documento_operacion (documento_id,' . $operation . ',creado_por) VALUES (:documento,:operacion,:usuario)');
                $insert->execute(['documento'=>$id,'operacion'=>(int)$operationId,'usuario'=>$this->currentUserId()]);
            }
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            @unlink($absolute);
            throw $error;
        }
        $this->respond(['id' => $id, 'nombre' => basename((string) $file['name']), 'tipo' => $mime], 201);
    }

    public function listar(array $params): never
    {
        $type = strtoupper(trim((string) ($_GET['operacionTipo'] ?? '')));
        $id = filter_var($_GET['operacionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) throw new ApiException(400, 'OPERATION_LINK_INVALID', 'Indica operacionId.');
        $operation = $this->operationColumn($type);
        $this->validarOperacion($operation, (int) $id);
        $stmt = $this->pdo->prepare('SELECT d.id,d.nombre_original,d.tipo,d.tamano_bytes,d.fecha,doper.creado_por FROM documento_operacion doper JOIN documento d ON d.id=doper.documento_id WHERE doper.' . $operation . '=:id ORDER BY d.fecha DESC,d.id DESC');
        $stmt->execute(['id'=>$id]);
        $this->respond($stmt->fetchAll());
    }

    private function operationColumn(string $type): string
    {
        $map = ['ASIGNACION'=>'asignacion_id','DEVOLUCION'=>'devolucion_id','TRASLADO'=>'solicitud_traslado_id','MOVIMIENTO'=>'movimiento_activo_id','PRESTAMO'=>'prestamo_id','NOVEDAD'=>'novedad_id','MANTENIMIENTO'=>'mantenimiento_id','BAJA'=>'baja_activo_id','INVENTARIO'=>'inventario_id','VERIFICACION'=>'inventario_detalle_id','DIFERENCIA'=>'diferencia_inventario_id'];
        if (!isset($map[$type])) throw new ApiException(400, 'OPERATION_TYPE_INVALID', 'Tipo de operación no permitido.');
        return $map[$type];
    }

    public function validarOperacion(string $column, int $id): void
    {
        if (in_array($column,['inventario_detalle_id','diferencia_inventario_id'],true)) {
            $from=$column==='inventario_detalle_id'?'inventario_detalle d JOIN inventario i ON i.id=d.inventario_id':'diferencia_inventario f JOIN inventario_detalle d ON d.id=f.detalle_id JOIN inventario i ON i.id=d.inventario_id';
            $key=$column==='inventario_detalle_id'?'d.id':'f.id';
            $stmt=$this->pdo->prepare('SELECT i.sede_id,i.dependencia_id,i.ubicacion_id FROM '.$from.' WHERE '.$key.'=:id');
            $stmt->execute(['id'=>$id]);$scope=$stmt->fetch();
            if($scope===false)$this->notFound('No existe la operación indicada.');
            if(!(new AutorizacionDAO($this->pdo))->dentroDeAmbito($this->currentUserId(),$scope['sede_id']===null?null:(int)$scope['sede_id'],$scope['dependencia_id']===null?null:(int)$scope['dependencia_id'],$scope['ubicacion_id']===null?null:(int)$scope['ubicacion_id']))throw new ApiException(403,'SCOPE_DENIED','Operación fuera del ámbito autorizado.');
            return;
        }
        $asset = match ($column) {
            'asignacion_id' => '(SELECT activo_id FROM asignacion WHERE id=:id)',
            'devolucion_id' => '(SELECT COALESCE(a.activo_id,p.activo_id) FROM devolucion d LEFT JOIN asignacion a ON a.id=d.asignacion_id LEFT JOIN prestamo p ON p.id=d.prestamo_id WHERE d.id=:id)',
            'solicitud_traslado_id' => '(SELECT activo_id FROM solicitud_traslado WHERE id=:id)',
            'movimiento_activo_id' => '(SELECT activo_id FROM movimiento_activo WHERE id=:id)',
            'prestamo_id' => '(SELECT activo_id FROM prestamo WHERE id=:id)',
            'novedad_id' => '(SELECT activo_id FROM novedad WHERE id=:id)',
            'mantenimiento_id' => '(SELECT activo_id FROM mantenimiento WHERE id=:id)',
            'baja_activo_id' => '(SELECT activo_id FROM baja_activo WHERE id=:id)',
            'inventario_id' => null,
            'inventario_detalle_id' => '(SELECT activo_id FROM inventario_detalle WHERE id=:id)',
            'diferencia_inventario_id' => '(SELECT d.activo_id FROM diferencia_inventario f JOIN inventario_detalle d ON d.id=f.detalle_id WHERE f.id=:id)',
        };
        if ($asset !== null) {
            $stmt = $this->pdo->prepare('SELECT ' . $asset . ' AS activo_id');
            $stmt->execute(['id'=>$id]);
            $assetId = $stmt->fetchColumn();
            if ($assetId === false) $this->notFound('No existe la operación indicada.');
            if ($assetId !== null) $this->assertAssetScope((int)$assetId);
            return;
        }
        $stmt = $this->pdo->prepare('SELECT sede_id,dependencia_id,ubicacion_id FROM inventario WHERE id=:id');
        $stmt->execute(['id'=>$id]); $row=$stmt->fetch();
        if ($row === false) $this->notFound('No existe la operación indicada.');
        if (!(new AutorizacionDAO($this->pdo))->dentroDeAmbito($this->currentUserId(),$row['sede_id']===null?null:(int)$row['sede_id'],$row['dependencia_id']===null?null:(int)$row['dependencia_id'],$row['ubicacion_id']===null?null:(int)$row['ubicacion_id'])) throw new ApiException(403,'SCOPE_DENIED','Operación fuera del ámbito autorizado.');
    }

    public function descargar(array $params): never
    {
        $document = (new DocumentoDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($document === null) $this->notFound('No existe el documento.');
        $userId = $this->currentUserId();
        $canReadLinked=false;$links=$this->pdo->prepare('SELECT * FROM documento_operacion WHERE documento_id=:id');$links->execute(['id'=>$document->getId()]);
        $columns=['asignacion_id','devolucion_id','solicitud_traslado_id','movimiento_activo_id','prestamo_id','novedad_id','mantenimiento_id','baja_activo_id','inventario_id','inventario_detalle_id','diferencia_inventario_id'];
        foreach($links->fetchAll() as $link){foreach($columns as $column){if($link[$column]!==null){try{$this->validarOperacion($column,(int)$link[$column]);$canReadLinked=true;break 2;}catch(ApiException){} }}}
        if ($document->getResponsableId() !== $userId && !$canReadLinked
            && !(new AutorizacionDAO($this->pdo))->tienePermiso($userId, 'usuarios', 'cuenta', 'administrar')) {
            throw new ApiException(403, 'DOCUMENT_FORBIDDEN', 'No tienes acceso al archivo.');
        }
        $root = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'documents');
        $path = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $document->getRutaPrivada()));
        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
            $this->notFound('El archivo no está disponible.');
        }
        header('Content-Type: ' . $document->getTipo());
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . rawurlencode($document->getNombreOriginal()) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
