<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\AuditoriaVO;

final class AuditoriaDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'auditoria';
    protected const VO = AuditoriaVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'usuario_id', 'entidad', 'entidad_id', 'accion', 'motivo', 'antes', 'despues', 'fecha_hora'];

    public function listar(?string $entidad, ?int $entidadId, int $limite = 100): array
    {
        if ($limite < 1 || $limite > 500) throw new \InvalidArgumentException('Límite inválido.');
        $conditions = [];
        $params = [];
        if ($entidad !== null) { $conditions[] = 'entidad = :entidad'; $params['entidad'] = $entidad; }
        if ($entidadId !== null) { $conditions[] = 'entidad_id = :entidad_id'; $params['entidad_id'] = $entidadId; }
        $sql = 'SELECT * FROM auditoria' . ($conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions)) . ' ORDER BY fecha_hora DESC,id DESC LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn (array $row) => $this->hidratar($row), $stmt->fetchAll());
    }

    public function registrar(?int $usuarioId, string $entidad, ?int $entidadId, string $accion, ?string $motivo = null, ?array $antes = null, ?array $despues = null): int
    {
        $encode = static fn (?array $snapshot): ?string => $snapshot === null
            ? null
            : json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $stmt = $this->pdo->prepare(
            'INSERT INTO auditoria (usuario_id,entidad,entidad_id,accion,motivo,antes,despues) '
            . 'VALUES (:usuario,:entidad,:entidad_id,:accion,:motivo,:antes,:despues)'
        );
        $stmt->execute([
            'usuario' => $usuarioId, 'entidad' => $entidad, 'entidad_id' => $entidadId,
            'accion' => $accion, 'motivo' => $motivo, 'antes' => $encode($antes), 'despues' => $encode($despues),
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
