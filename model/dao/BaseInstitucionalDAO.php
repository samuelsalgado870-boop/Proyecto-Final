<?php

declare(strict_types=1);

namespace App\Model\DAO;

use PDO;

final class BaseInstitucionalDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{importacionId:?int,total:int,placasUnicas:int,sinPlaca:int,repetidas:int,estados:list<array{estado:string,total:int}>,filas:list<array<string,mixed>>} */
    public function consultar(string $busqueda = '', int $limite = 50, int $offset = 0): array
    {
        if ($limite < 1 || $limite > 500 || $offset < 0) {
            throw new \InvalidArgumentException('Paginación de base institucional inválida.');
        }

        $latest = $this->pdo->query(
            "SELECT MAX(i.id) FROM importacion i WHERE LOWER(i.nombre_archivo) LIKE '%.xlsx' "
            . 'AND EXISTS (SELECT 1 FROM fila_planilla_activo f WHERE f.importacion_id=i.id)'
        );
        $importId = (int) $latest->fetchColumn();
        if ($importId < 1) {
            return ['importacionId' => null, 'total' => 0, 'placasUnicas' => 0, 'sinPlaca' => 0, 'repetidas' => 0, 'estados' => [], 'filas' => []];
        }

        $summaryQuery = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,COUNT(DISTINCT UPPER(TRIM(NULLIF(placa,'')))) AS placas_unicas,"
            . "SUM(resultado='SIN_PLACA') AS sin_placa,SUM(resultado='PLACA_REPETIDA') AS repetidas "
            . 'FROM fila_planilla_activo WHERE importacion_id=:importacion'
        );
        $summaryQuery->execute(['importacion' => $importId]);
        $summary = $summaryQuery->fetch() ?: [];

        $stateQuery = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(TRIM(nombre_estado),''),'Sin estado') AS estado,COUNT(*) AS total "
            . 'FROM fila_planilla_activo WHERE importacion_id=:importacion '
            . 'GROUP BY COALESCE(NULLIF(TRIM(nombre_estado),\'\'),\'Sin estado\') ORDER BY total DESC,estado ASC LIMIT 8'
        );
        $stateQuery->execute(['importacion' => $importId]);
        $states = array_map(static fn (array $row): array => ['estado' => (string) $row['estado'], 'total' => (int) $row['total']], $stateQuery->fetchAll());

        $where = 'importacion_id=:importacion';
        $parameters = ['importacion' => $importId];
        if ($busqueda !== '') {
            $columns = ['placa', 'descripcion', 'lote', 'ubicacion_fisica', 'centro_costo', 'nombre_centro_costo', 'nombre_estado'];
            $conditions = [];
            foreach ($columns as $index => $column) {
                $placeholder = 'busqueda' . $index;
                $conditions[] = $column . ' LIKE :' . $placeholder;
                $parameters[$placeholder] = '%' . $busqueda . '%';
            }
            $where .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        $rowsQuery = $this->pdo->prepare(
            'SELECT numero_fila,activo_id,placa,lote,descripcion,ubicacion_fisica,centro_costo,nombre_centro_costo,nombre_estado,depreciado,resultado '
            . 'FROM fila_planilla_activo WHERE ' . $where . ' ORDER BY numero_fila LIMIT :limite OFFSET :offset'
        );
        foreach ($parameters as $name => $value) {
            $rowsQuery->bindValue(':' . $name, $value, $name === 'importacion' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $rowsQuery->bindValue(':limite', $limite, PDO::PARAM_INT);
        $rowsQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
        $rowsQuery->execute();

        $countQuery = $this->pdo->prepare('SELECT COUNT(*) FROM fila_planilla_activo WHERE ' . $where);
        $countQuery->execute($parameters);

        return [
            'importacionId' => $importId,
            'total' => (int) $countQuery->fetchColumn(),
            'placasUnicas' => (int) ($summary['placas_unicas'] ?? 0),
            'sinPlaca' => (int) ($summary['sin_placa'] ?? 0),
            'repetidas' => (int) ($summary['repetidas'] ?? 0),
            'estados' => $states,
            'filas' => $rowsQuery->fetchAll(),
        ];
    }

    /** @return array<string,mixed>|null */
    public function buscarDetalle(int $numeroFila): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.numero_fila,f.activo_id,f.placa,f.lote,f.descripcion,f.ubicacion_fisica,"
            . "f.identificacion_responsable,f.nombre_responsable,f.centro_costo,"
            . "f.nombre_centro_costo,f.nombre_estado,f.depreciado,f.resultado,f.observacion "
            . "FROM fila_planilla_activo f JOIN importacion i ON i.id=f.importacion_id "
            . "WHERE f.numero_fila=:fila AND LOWER(i.nombre_archivo) LIKE '%.xlsx' "
            . 'ORDER BY i.id DESC LIMIT 1'
        );
        $stmt->execute(['fila' => $numeroFila]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
