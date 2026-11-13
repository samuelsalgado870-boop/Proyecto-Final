<?php

declare(strict_types=1);

namespace App\Model\DAO;

final class DocumentoPostulacionDAO extends AbstractDAO
{
    public function registrar(int $postulacionId, string $tipoDocumento, string $nombreOriginal, string $mime, int $tamano, string $ruta, int $actorId): int
    {
        return $this->transaction(function () use ($postulacionId, $tipoDocumento, $nombreOriginal, $mime, $tamano, $ruta, $actorId): int {
            $lock = $this->pdo->prepare('SELECT id FROM postulacion_seleccion WHERE id=:postulacion FOR UPDATE');
            $lock->execute(['postulacion' => $postulacionId]);
            if ($lock->fetchColumn() === false) throw new \OutOfBoundsException('No existe la postulación.');
            $nextVersion = $this->pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM documento_postulacion WHERE postulacion_id=:postulacion AND tipo_documento=:tipo');
            $nextVersion->execute(['postulacion' => $postulacionId, 'tipo' => $tipoDocumento]);
            $version = (int) $nextVersion->fetchColumn();
            $stmt = $this->pdo->prepare('INSERT INTO documento_postulacion (postulacion_id,tipo_documento,nombre_original,tipo_mime,tamano_bytes,ruta_privada,version) VALUES (:postulacion,:tipo,:nombre,:mime,:tamano,:ruta,:version)');
            $stmt->execute(['postulacion' => $postulacionId, 'tipo' => $tipoDocumento, 'nombre' => $nombreOriginal, 'mime' => $mime, 'tamano' => $tamano, 'ruta' => $ruta, 'version' => $version]);
            $id = (int) $this->pdo->lastInsertId();
            $this->registrarAuditoria($actorId, $id, 'CARGAR', null, ['postulacion_id' => $postulacionId, 'tipo_documento' => $tipoDocumento, 'version' => $version]);
            return $id;
        });
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id,postulacion_id,tipo_documento,nombre_original,tipo_mime,tamano_bytes,ruta_privada,version,estado_revision,observacion_revision FROM documento_postulacion WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function listarPorPostulacion(int $postulacionId): array
    {
        $stmt = $this->pdo->prepare('SELECT id,postulacion_id,tipo_documento,nombre_original,tipo_mime,tamano_bytes,version,estado_revision,observacion_revision,subido_en,revisado_por,revisado_en FROM documento_postulacion WHERE postulacion_id=:postulacion ORDER BY subido_en DESC,id DESC');
        $stmt->execute(['postulacion' => $postulacionId]);
        return $stmt->fetchAll();
    }

    public function perteneceAProceso(int $postulacionId, int $procesoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM postulacion_seleccion WHERE id=:postulacion AND proceso_id=:proceso LIMIT 1');
        $stmt->execute(['postulacion' => $postulacionId, 'proceso' => $procesoId]);
        return $stmt->fetchColumn() !== false;
    }

    public function postulacionExiste(int $postulacionId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM postulacion_seleccion WHERE id=:postulacion LIMIT 1');
        $stmt->execute(['postulacion' => $postulacionId]);
        return $stmt->fetchColumn() !== false;
    }

    public function revisar(int $id, string $estado, ?string $observacion, int $revisorId): bool
    {
        return $this->transaction(function () use ($id, $estado, $observacion, $revisorId): bool {
            $find = $this->pdo->prepare('SELECT estado_revision FROM documento_postulacion WHERE id=:id FOR UPDATE');
            $find->execute(['id' => $id]);
            $previous = $find->fetchColumn();
            if ($previous === false) return false;
            $stmt = $this->pdo->prepare('UPDATE documento_postulacion SET estado_revision=:estado,observacion_revision=:observacion,revisado_por=:revisor,revisado_en=CURRENT_TIMESTAMP WHERE id=:id');
            $stmt->execute(['estado' => $estado, 'observacion' => $observacion, 'revisor' => $revisorId, 'id' => $id]);
            if ($previous !== $estado) $this->registrarAuditoria($revisorId, $id, 'REVISAR', ['estado_revision' => $previous], ['estado_revision' => $estado]);
            return true;
        });
    }

    private function registrarAuditoria(int $usuarioId, int $documentoId, string $accion, ?array $antes, ?array $despues): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO auditoria (usuario_id,entidad,entidad_id,accion,antes,despues) VALUES (:usuario,\'documento_postulacion\',:id,:accion,:antes,:despues)');
        $stmt->execute([
            'usuario' => $usuarioId, 'id' => $documentoId, 'accion' => $accion,
            'antes' => $antes === null ? null : json_encode($antes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'despues' => $despues === null ? null : json_encode($despues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
