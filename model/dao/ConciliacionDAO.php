<?php

declare(strict_types=1);

namespace App\Model\DAO;

use PDO;

final class ConciliacionDAO extends AbstractDAO
{
    public function porInventario(int $inventarioId): array
    {
        $sql = "SELECT i.id inventario_id,i.nombre inventario,i.estado,
            COALESCE(s.nombre,'Sin sede') sede,COALESCE(dep.nombre,'Sin dependencia') dependencia,
            COALESCE(u.codigo,'') ubicacion_codigo,COALESCE(u.nombre,'Todas las ubicaciones') ubicacion,
            COALESCE(exp.total_esperados,0) esperados,COALESCE(ver.total_verificados,0) verificados,
            GREATEST(COALESCE(exp.total_esperados,0)-COALESCE(ver.total_en_activo,0),0) faltantes,
            COALESCE(ver.hallazgos_sin_activo,0) hallazgos_sin_activo,
            COALESCE(diff.total_diferencias,0) diferencias
            FROM inventario i
            LEFT JOIN sede s ON s.id=COALESCE(i.sede_id,(SELECT ux.sede_id FROM ubicacion ux WHERE ux.id=i.ubicacion_id))
            LEFT JOIN dependencia dep ON dep.id=i.dependencia_id
            LEFT JOIN ubicacion u ON u.id=i.ubicacion_id
            LEFT JOIN (
                SELECT i2.id inventario_id,COUNT(a.id) total_esperados
                FROM inventario i2 JOIN activo a ON (i2.ubicacion_id IS NULL OR a.ubicacion_id=i2.ubicacion_id)
                    AND (i2.dependencia_id IS NULL OR a.dependencia_id=i2.dependencia_id)
                    AND (i2.categoria_id IS NULL OR a.categoria_id=i2.categoria_id)
                JOIN ubicacion ux ON ux.id=a.ubicacion_id
                WHERE (i2.sede_id IS NULL OR ux.sede_id=i2.sede_id)
                GROUP BY i2.id
            ) exp ON exp.inventario_id=i.id
            LEFT JOIN (
                SELECT inventario_id,COUNT(id) total_verificados,
                    SUM(activo_id IS NOT NULL) total_en_activo,
                    SUM(activo_id IS NULL) hallazgos_sin_activo
                FROM inventario_detalle GROUP BY inventario_id
            ) ver ON ver.inventario_id=i.id
            LEFT JOIN (
                SELECT d.inventario_id,COUNT(DISTINCT f.id) total_diferencias
                FROM inventario_detalle d LEFT JOIN diferencia_inventario f ON f.detalle_id=d.id
                GROUP BY d.inventario_id
            ) diff ON diff.inventario_id=i.id
            WHERE i.id=:id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id'=>$inventarioId]);
        $summary = $stmt->fetch();
        if ($summary === false) return [];

        $groupSql = "SELECT COALESCE(s.nombre,'Sin sede') sede,COALESCE(u.codigo,'') ubicacion_codigo,
            COALESCE(u.nombre,'Sin ubicación') ubicacion,COALESCE(dep.nombre,'Sin dependencia') dependencia,
            COUNT(DISTINCT a.id) esperados,COUNT(DISTINCT CASE WHEN d.activo_id IS NOT NULL THEN d.activo_id END) verificados,
            GREATEST(COUNT(DISTINCT a.id)-COUNT(DISTINCT CASE WHEN d.activo_id IS NOT NULL THEN d.activo_id END),0) faltantes,
            COUNT(DISTINCT CASE WHEN d.activo_id IS NULL THEN d.id END) hallazgos_sin_activo,
            COUNT(DISTINCT f.id) diferencias
            FROM inventario i
            JOIN activo a ON (i.ubicacion_id IS NULL OR a.ubicacion_id=i.ubicacion_id)
                AND (i.dependencia_id IS NULL OR a.dependencia_id=i.dependencia_id)
                AND (i.categoria_id IS NULL OR a.categoria_id=i.categoria_id)
            JOIN ubicacion u ON u.id=a.ubicacion_id
            LEFT JOIN sede s ON s.id=u.sede_id
            LEFT JOIN dependencia dep ON dep.id=a.dependencia_id
            LEFT JOIN inventario_detalle d ON d.inventario_id=i.id AND d.activo_id=a.id
            LEFT JOIN inventario_detalle nd ON nd.inventario_id=i.id AND nd.activo_id IS NULL AND nd.ubicacion_observada=u.nombre
            LEFT JOIN diferencia_inventario f ON f.detalle_id=COALESCE(d.id,nd.id)
            WHERE i.id=:id AND (i.sede_id IS NULL OR i.sede_id=u.sede_id)
            GROUP BY s.nombre,u.codigo,u.nombre,dep.nombre ORDER BY s.nombre,u.nombre,dep.nombre";
        $byLocation = $this->pdo->prepare($groupSql);
        $byLocation->execute(['id'=>$inventarioId]);
        $summary['ubicaciones'] = $byLocation->fetchAll();
        return $summary;
    }
}
