<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\PrestamoVO;
use DateTimeImmutable;

final class PrestamoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'prestamo';
    protected const VO = PrestamoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'activo_id', 'responsable_id', 'destino', 'fecha_salida', 'fecha_limite', 'condiciones_retorno', 'estado'];

    public function buscarPorId(int $id): ?PrestamoVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM prestamo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    public function crear(
        int $activoId,
        int $responsableId,
        ?string $destino,
        DateTimeImmutable $fechaSalida,
        DateTimeImmutable $fechaLimite,
        ?string $condicionesRetorno = null
    ): int {
        if ($fechaLimite <= $fechaSalida) {
            throw new \InvalidArgumentException('La fecha límite debe ser posterior a la salida.');
        }

        return $this->transaction(function () use ($activoId, $responsableId, $destino, $fechaSalida, $fechaLimite, $condicionesRetorno): int {
            $lock = $this->pdo->prepare('SELECT estado FROM activo WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $activoId]);
            $asset = $lock->fetch();
            if ($asset === false) {
                throw new \DomainException('No existe el activo indicado.');
            }
            if (in_array($asset['estado'], ['DADO_DE_BAJA', 'EN_PRESTAMO', 'EN_MANTENIMIENTO'], true)) {
                throw new \DomainException('El estado actual del activo no permite préstamo.');
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO prestamo (activo_id,responsable_id,destino,fecha_salida,fecha_limite,condiciones_retorno,estado) "
                . "VALUES (:activo,:responsable,:destino,:salida,:limite,:condiciones,'EN_PRESTAMO')"
            );
            $stmt->execute([
                'activo' => $activoId, 'responsable' => $responsableId, 'destino' => $destino,
                'salida' => $fechaSalida->format('Y-m-d H:i:s'), 'limite' => $fechaLimite->format('Y-m-d H:i:s'),
                'condiciones' => $condicionesRetorno,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $update = $this->pdo->prepare("UPDATE activo SET estado = 'EN_PRESTAMO' WHERE id = :id");
            $update->execute(['id' => $activoId]);
            return $id;
        });
    }

    public function registrarDevolucion(int $prestamoId, int $recibidoPor, string $estadoFisico, ?string $destinoPosterior = null, ?string $excepcion = null): int
    {
        return $this->transaction(function () use ($prestamoId, $recibidoPor, $estadoFisico, $destinoPosterior, $excepcion): int {
            $stmt = $this->pdo->prepare('SELECT activo_id, estado FROM prestamo WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $loan = $stmt->fetch();
            if ($loan === false || $loan['estado'] === 'DEVUELTO') {
                throw new \DomainException('El préstamo no existe o ya fue devuelto.');
            }

            $insert = $this->pdo->prepare(
                "INSERT INTO devolucion (prestamo_id,recibido_por,estado_fisico,destino_posterior,excepcion_justificada) "
                . 'VALUES (:prestamo,:recibido,:estado,:destino,:excepcion)'
            );
            $insert->execute([
                'prestamo' => $prestamoId, 'recibido' => $recibidoPor, 'estado' => $estadoFisico,
                'destino' => $destinoPosterior, 'excepcion' => $excepcion,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare("UPDATE prestamo SET estado = 'DEVUELTO' WHERE id = :id")->execute(['id' => $prestamoId]);
            $this->pdo->prepare("UPDATE activo SET estado = 'DISPONIBLE' WHERE id = :id AND estado = 'EN_PRESTAMO'")->execute(['id' => (int) $loan['activo_id']]);
            return $id;
        });
    }

    protected function hidratar(array $row): PrestamoVO
    {
        return new PrestamoVO(
            (int) $row['id'], (int) $row['activo_id'], (int) $row['responsable_id'], $row['destino'],
            new DateTimeImmutable($row['fecha_salida']), new DateTimeImmutable($row['fecha_limite']),
            $row['condiciones_retorno'], $row['estado']
        );
    }
}
