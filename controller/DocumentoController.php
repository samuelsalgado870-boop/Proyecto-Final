<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\DocumentoDAO;
use App\Model\DAO\AutorizacionDAO;

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
            $id = (new DocumentoDAO($this->pdo))->registrar(
                mb_substr(basename((string) $file['name']), 0, 255), $mime, (int) $file['size'], $this->currentUserId(), $relative
            );
        } catch (\Throwable $error) {
            @unlink($absolute);
            throw $error;
        }
        $this->respond(['id' => $id, 'nombre' => basename((string) $file['name']), 'tipo' => $mime], 201);
    }

    public function descargar(array $params): never
    {
        $document = (new DocumentoDAO($this->pdo))->buscarPorId($this->routeId($params));
        if ($document === null) $this->notFound('No existe el documento.');
        $userId = $this->currentUserId();
        if ($document->getResponsableId() !== $userId
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
