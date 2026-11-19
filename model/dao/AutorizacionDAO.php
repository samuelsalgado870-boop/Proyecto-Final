<?php

declare(strict_types=1);

namespace App\Model\DAO;

final class AutorizacionDAO extends AbstractDAO
{
    /** Query fragment limiting rows to the caller's explicit institutional scope. */
    public function scopePredicate(string $alias): array
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $alias)) throw new \InvalidArgumentException('Alias SQL inválido.');
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return [
            'sql' => "EXISTS (SELECT 1 FROM ambito_acceso aa WHERE aa.usuario_id=:scope_usuario "
                . 'AND (aa.vigente_desde IS NULL OR aa.vigente_desde<=CURRENT_DATE) '
                . 'AND (aa.vigente_hasta IS NULL OR aa.vigente_hasta>=CURRENT_DATE) '
                . "AND (aa.tipo_ambito='GLOBAL' OR (aa.tipo_ambito='SEDE' AND aa.sede_id={$alias}.sede_id) "
                . "OR (aa.tipo_ambito='DEPENDENCIA' AND aa.dependencia_id={$alias}.dependencia_id) "
                . "OR (aa.tipo_ambito='UBICACION' AND aa.ubicacion_id={$alias}.ubicacion_id) "
                . "OR (aa.tipo_ambito='CUSTODIA' AND {$alias}.activo_id IS NOT NULL AND EXISTS (SELECT 1 FROM asignacion acs WHERE acs.activo_id={$alias}.activo_id AND acs.custodio_id=:scope_custodio AND acs.fecha_fin IS NULL))))",
            'params' => ['scope_usuario'=>$userId,'scope_custodio'=>$userId],
        ];
    }

    public function tienePermiso(int $usuarioId, string $modulo, string $recurso, string $accion): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM usuario_rol ur '
            . 'JOIN rol r ON r.id = ur.rol_id AND r.estado = :rol_estado '
            . 'JOIN rol_permiso rp ON rp.rol_id = r.id '
            . 'JOIN permiso p ON p.id = rp.permiso_id '
            . 'WHERE ur.usuario_id = :usuario AND p.modulo = :modulo AND p.recurso = :recurso AND p.accion = :accion LIMIT 1'
        );
        $stmt->execute([
            'rol_estado' => 'ACTIVO', 'usuario' => $usuarioId,
            'modulo' => $modulo, 'recurso' => $recurso, 'accion' => $accion,
        ]);
        return $stmt->fetchColumn() !== false;
    }

    public function listarPermisos(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT p.modulo, p.recurso, p.accion FROM usuario_rol ur '
            . 'JOIN rol r ON r.id = ur.rol_id AND r.estado = :rol_estado '
            . 'JOIN rol_permiso rp ON rp.rol_id = r.id JOIN permiso p ON p.id = rp.permiso_id '
            . 'WHERE ur.usuario_id = :usuario ORDER BY p.modulo,p.recurso,p.accion'
        );
        $stmt->execute(['rol_estado' => 'ACTIVO', 'usuario' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function dentroDeAmbito(int $usuarioId, ?int $sedeId, ?int $dependenciaId, ?int $ubicacionId, ?int $activoId = null): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM ambito_acceso aa WHERE aa.usuario_id = :usuario "
            . 'AND (aa.vigente_desde IS NULL OR aa.vigente_desde <= CURRENT_DATE) '
            . 'AND (aa.vigente_hasta IS NULL OR aa.vigente_hasta >= CURRENT_DATE) '
            . "AND (aa.tipo_ambito = 'GLOBAL' "
            . "OR (aa.tipo_ambito = 'SEDE' AND aa.sede_id = :sede) "
            . "OR (aa.tipo_ambito = 'DEPENDENCIA' AND aa.dependencia_id = :dependencia) "
            . "OR (aa.tipo_ambito = 'UBICACION' AND aa.ubicacion_id = :ubicacion) "
            . "OR (aa.tipo_ambito = 'CUSTODIA' AND :activo IS NOT NULL AND EXISTS ("
            . 'SELECT 1 FROM asignacion a WHERE a.activo_id = :activo_asignado AND a.custodio_id = :usuario_custodio AND a.fecha_fin IS NULL))) LIMIT 1'
        );
        $stmt->execute([
            'usuario' => $usuarioId, 'sede' => $sedeId, 'dependencia' => $dependenciaId,
            'ubicacion' => $ubicacionId, 'activo' => $activoId, 'activo_asignado' => $activoId,
            'usuario_custodio' => $usuarioId,
        ]);
        return $stmt->fetchColumn() !== false;
    }

    public function dentroDeActivo(int $usuarioId, int $activoId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM activo a JOIN ubicacion u ON u.id=a.ubicacion_id '
            . 'WHERE a.id=:activo AND EXISTS (SELECT 1 FROM ambito_acceso aa WHERE aa.usuario_id=:usuario '
            . 'AND (aa.vigente_desde IS NULL OR aa.vigente_desde<=CURRENT_DATE) '
            . 'AND (aa.vigente_hasta IS NULL OR aa.vigente_hasta>=CURRENT_DATE) '
            . "AND (aa.tipo_ambito='GLOBAL' OR (aa.tipo_ambito='SEDE' AND aa.sede_id=u.sede_id) "
            . "OR (aa.tipo_ambito='DEPENDENCIA' AND aa.dependencia_id=a.dependencia_id) "
            . "OR (aa.tipo_ambito='UBICACION' AND aa.ubicacion_id=a.ubicacion_id) "
            . "OR (aa.tipo_ambito='CUSTODIA' AND EXISTS (SELECT 1 FROM asignacion ag WHERE ag.activo_id=a.id AND ag.custodio_id=:custodio AND ag.fecha_fin IS NULL)))) LIMIT 1"
        );
        $stmt->execute(['activo' => $activoId, 'usuario' => $usuarioId, 'custodio' => $usuarioId]);
        return $stmt->fetchColumn() !== false;
    }

    public function dentroDeUbicacion(int $usuarioId, int $ubicacionId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM ubicacion u WHERE u.id=:ubicacion AND EXISTS (SELECT 1 FROM ambito_acceso aa '
            . 'WHERE aa.usuario_id=:usuario AND (aa.vigente_desde IS NULL OR aa.vigente_desde<=CURRENT_DATE) '
            . 'AND (aa.vigente_hasta IS NULL OR aa.vigente_hasta>=CURRENT_DATE) '
            . "AND (aa.tipo_ambito='GLOBAL' OR (aa.tipo_ambito='SEDE' AND aa.sede_id=u.sede_id) "
            . "OR (aa.tipo_ambito='DEPENDENCIA' AND aa.dependencia_id=u.dependencia_id) "
            . "OR (aa.tipo_ambito='UBICACION' AND aa.ubicacion_id=u.id))) LIMIT 1"
        );
        $stmt->execute(['ubicacion' => $ubicacionId, 'usuario' => $usuarioId]);
        return $stmt->fetchColumn() !== false;
    }
}
