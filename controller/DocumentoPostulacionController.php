<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\DocumentoPostulacionDAO;

final class DocumentoPostulacionController extends ApiController
{
    public function listar(array $params): never
    {
        $dao = new DocumentoPostulacionDAO($this->pdo);
        $postulacionId = $this->routeId($params, 'postulacionId');
        if (!$dao->postulacionExiste($postulacionId)) $this->notFound('No existe la postulación.');
        $this->respond($dao->listarPorPostulacion($postulacionId));
    }

    public function subir(array $params): never
    {
        $file = $_FILES['archivo'] ?? null;
        if (($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST) && empty($_FILES)) {
            throw new ApiException(413, 'REQUEST_TOO_LARGE', 'La solicitud supera el límite de carga configurado en PHP. Ajusta post_max_size para permitir archivos de hasta 10 MB.');
        }
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            $uploadError = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                throw new ApiException(413, 'FILE_TOO_LARGE', 'El archivo supera el límite permitido por el servidor.');
            }
            throw new ApiException(400, 'UPLOAD_INVALID', 'Selecciona un documento válido y vuelve a intentarlo.');
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > 10 * 1024 * 1024) {
            throw new ApiException(413, 'FILE_TOO_LARGE', 'El documento debe pesar hasta 10 MB.');
        }
        $tipoDocumento = $this->requiredString($_POST, 'tipoDocumento', 120);
        if (!in_array($tipoDocumento, ['CEDULA_CIUDADANIA', 'CEDULA_EXTRANJERIA', 'PASAPORTE'], true)) {
            throw new ApiException(400, 'DOCUMENT_TYPE_INVALID', 'Selecciona un tipo de documento de identidad válido.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg'];
        if (!is_string($mime) || !isset($allowed[$mime])) throw new ApiException(415, 'FILE_TYPE_UNSUPPORTED', 'Solo se aceptan PDF, PNG o JPEG.');

        $processId = $this->routeId($params, 'id');
        $applicationId = $this->routeId($params, 'postulacionId');
        $dao = new DocumentoPostulacionDAO($this->pdo);
        if (!$dao->perteneceAProceso($applicationId, $processId)) $this->notFound('No existe esa postulación dentro del proceso.');

        $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'selection-documents';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new \RuntimeException('No se pudo preparar el almacenamiento privado.');
        $storedName = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
        $absolute = $directory . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $absolute)) throw new \RuntimeException('No se pudo guardar el documento.');
        try {
            $documentId = $dao->registrar($applicationId, $tipoDocumento, mb_substr(basename((string) $file['name']), 0, 255), $mime, (int) $file['size'], 'storage/private/selection-documents/' . $storedName, $this->currentUserId());
        } catch (\OutOfBoundsException $error) {
            @unlink($absolute);
            throw new ApiException(404, 'APPLICATION_NOT_FOUND', $error->getMessage());
        } catch (\Throwable $error) {
            @unlink($absolute);
            throw $error;
        }
        $this->respond(['id' => $documentId], 201);
    }

    public function revisar(array $params): never
    {
        $body = $this->body();
        $estado = strtoupper($this->requiredString($body, 'estado', 25));
        if (!in_array($estado, ['PENDIENTE','APROBADO','RECHAZADO','SUBSANADO'], true)) {
            throw new ApiException(400, 'STATE_INVALID', 'El estado de revisión no es válido.');
        }
        $observacion = $this->optionalString($body, 'observacion', 1000);
        $dao = new DocumentoPostulacionDAO($this->pdo);
        $id = $this->routeId($params);
        if ($dao->buscarPorId($id) === null) $this->notFound('No existe el documento.');
        $dao->revisar($id, $estado, $observacion, $this->currentUserId());
        $this->respond(['id' => $id, 'estadoRevision' => $estado]);
    }

    public function descargar(array $params): never
    {
        $document = (new DocumentoPostulacionDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($document === null) $this->notFound('No existe el documento.');
        $root = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'selection-documents');
        $path = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $document['ruta_privada']));
        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) $this->notFound('El archivo no está disponible.');
        header('Content-Type: ' . $document['tipo_mime']);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . rawurlencode($document['nombre_original']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
