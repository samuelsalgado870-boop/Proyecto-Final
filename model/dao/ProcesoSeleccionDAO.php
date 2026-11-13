<?php

declare(strict_types=1);

namespace App\Model\DAO;

final class ProcesoSeleccionDAO extends AbstractDAO
{
    public function listarDependenciasActivas(): array
    {
        return $this->pdo->query("SELECT id,codigo,nombre FROM dependencia WHERE estado='ACTIVA' ORDER BY nombre")->fetchAll();
    }

    public function listar(?string $estado = null, ?int $dependenciaId = null, ?string $q = null, int $limite = 100): array
    {
        if ($limite < 1 || $limite > 200) throw new \InvalidArgumentException('Límite inválido.');
        $where = [];
        $params = [];
        if ($estado !== null) { $where[] = 'p.estado = :estado'; $params['estado'] = $estado; }
        if ($dependenciaId !== null) { $where[] = 'p.dependencia_id = :dependencia'; $params['dependencia'] = $dependenciaId; }
        if ($q !== null && trim($q) !== '') { $where[] = 'p.nombre LIKE :consulta'; $params['consulta'] = '%' . trim($q) . '%'; }
        $sql = 'SELECT p.id,p.nombre,p.dependencia_id,d.nombre AS unidad,p.estado,p.fecha_apertura,p.fecha_cierre,p.creado_en,'
            . '(SELECT COUNT(*) FROM postulacion_seleccion ps WHERE ps.proceso_id=p.id) AS postulaciones,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp JOIN postulacion_seleccion ps ON ps.id=dp.postulacion_id WHERE ps.proceso_id=p.id) AS documentos,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp JOIN postulacion_seleccion ps ON ps.id=dp.postulacion_id WHERE ps.proceso_id=p.id AND dp.estado_revision="APROBADO") AS aprobados,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp JOIN postulacion_seleccion ps ON ps.id=dp.postulacion_id WHERE ps.proceso_id=p.id AND dp.estado_revision="PENDIENTE") AS pendientes,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp JOIN postulacion_seleccion ps ON ps.id=dp.postulacion_id WHERE ps.proceso_id=p.id AND dp.estado_revision="RECHAZADO") AS rechazados '
            . 'FROM proceso_seleccion p JOIN dependencia d ON d.id=p.dependencia_id'
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY p.creado_en DESC,p.id DESC LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT p.id,p.nombre,p.dependencia_id,d.nombre AS unidad,p.estado,p.fecha_apertura,p.fecha_cierre,p.creado_por FROM proceso_seleccion p JOIN dependencia d ON d.id=p.dependencia_id WHERE p.id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function crear(string $nombre, int $dependenciaId, string $estado, ?string $apertura, ?string $cierre, int $creadoPor): int
    {
        return $this->transaction(function () use ($nombre, $dependenciaId, $estado, $apertura, $cierre, $creadoPor): int {
            $stmt = $this->pdo->prepare('INSERT INTO proceso_seleccion (nombre,dependencia_id,estado,fecha_apertura,fecha_cierre,creado_por) VALUES (:nombre,:dependencia,:estado,:apertura,:cierre,:creador)');
            $stmt->execute(['nombre' => $nombre, 'dependencia' => $dependenciaId, 'estado' => $estado, 'apertura' => $apertura, 'cierre' => $cierre, 'creador' => $creadoPor]);
            $id = (int) $this->pdo->lastInsertId();
            $this->registrarAuditoria($creadoPor, 'proceso_seleccion', $id, 'CREAR', null, ['estado' => $estado, 'dependencia_id' => $dependenciaId]);
            return $id;
        });
    }

    public function cambiarEstado(int $id, string $estado, int $usuarioId): bool
    {
        return $this->transaction(function () use ($id, $estado, $usuarioId): bool {
            $find = $this->pdo->prepare('SELECT estado FROM proceso_seleccion WHERE id=:id FOR UPDATE');
            $find->execute(['id' => $id]);
            $previous = $find->fetchColumn();
            if ($previous === false) return false;
            $stmt = $this->pdo->prepare('UPDATE proceso_seleccion SET estado=:estado WHERE id=:id');
            $stmt->execute(['estado' => $estado, 'id' => $id]);
            if ($previous !== $estado) $this->registrarAuditoria($usuarioId, 'proceso_seleccion', $id, 'CAMBIAR_ESTADO', ['estado' => $previous], ['estado' => $estado]);
            return true;
        });
    }

    public function listarPostulaciones(int $procesoId, ?string $q = null, int $limite = 200): array
    {
        if ($limite < 1 || $limite > 500) throw new \InvalidArgumentException('Límite inválido.');
        $sql = 'SELECT ps.id AS postulacion_id,ps.proceso_id,ps.estado,ps.fecha_postulacion,po.id AS postulante_id,po.nombre AS usuario,po.correo,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp WHERE dp.postulacion_id=ps.id) AS documentos_cargados,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp WHERE dp.postulacion_id=ps.id AND dp.estado_revision="APROBADO") AS aprobados,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp WHERE dp.postulacion_id=ps.id AND dp.estado_revision="PENDIENTE") AS pendientes,'
            . '(SELECT COUNT(*) FROM documento_postulacion dp WHERE dp.postulacion_id=ps.id AND dp.estado_revision="RECHAZADO") AS rechazados '
            . 'FROM postulacion_seleccion ps JOIN postulante_seleccion po ON po.id=ps.postulante_id WHERE ps.proceso_id=:proceso';
        if ($q !== null && trim($q) !== '') $sql .= ' AND (po.nombre LIKE :consulta OR po.correo LIKE :consulta_correo)';
        $sql .= ' ORDER BY po.nombre,ps.id LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':proceso', $procesoId, \PDO::PARAM_INT);
        if ($q !== null && trim($q) !== '') {
            $like = '%' . trim($q) . '%';
            $stmt->bindValue(':consulta', $like, \PDO::PARAM_STR);
            $stmt->bindValue(':consulta_correo', $like, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrarPostulacion(int $procesoId, string $nombre, string $correo, int $actorId): int
    {
        return $this->transaction(function () use ($procesoId, $nombre, $correo, $actorId): int {
            $process = $this->pdo->prepare('SELECT estado FROM proceso_seleccion WHERE id=:id FOR UPDATE');
            $process->execute(['id' => $procesoId]);
            $state = $process->fetchColumn();
            if ($state === false) throw new \OutOfBoundsException('No existe el proceso de selección.');
            if ($state !== 'ABIERTO') throw new \DomainException('El proceso no está abierto para nuevas postulaciones.');

            $candidate = $this->pdo->prepare('INSERT INTO postulante_seleccion (nombre,correo) VALUES (:nombre,:correo) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
            $candidate->execute(['nombre' => $nombre, 'correo' => $correo]);
            $candidateId = (int) $this->pdo->lastInsertId();
            $application = $this->pdo->prepare('INSERT INTO postulacion_seleccion (proceso_id,postulante_id,estado) VALUES (:proceso,:postulante,\'INCOMPLETA\')');
            $application->execute(['proceso' => $procesoId, 'postulante' => $candidateId]);
            $applicationId = (int) $this->pdo->lastInsertId();
            $this->registrarAuditoria($actorId, 'postulacion_seleccion', $applicationId, 'CREAR', null, ['proceso_id' => $procesoId, 'postulante_id' => $candidateId]);
            return $applicationId;
        });
    }

    private function registrarAuditoria(?int $usuarioId, string $entidad, int $entidadId, string $accion, ?array $antes, ?array $despues): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO auditoria (usuario_id,entidad,entidad_id,accion,antes,despues) VALUES (:usuario,:entidad,:entidad_id,:accion,:antes,:despues)');
        $stmt->execute([
            'usuario' => $usuarioId, 'entidad' => $entidad, 'entidad_id' => $entidadId, 'accion' => $accion,
            'antes' => $antes === null ? null : json_encode($antes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'despues' => $despues === null ? null : json_encode($despues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
