<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\ActaVO;

final class ActaDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'acta';
    protected const VO = ActaVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'tipo', 'numero', 'fecha', 'version', 'documento_id', 'operacion_tipo', 'operacion_id'];

    public function registrarTipada(string $tipo, string $numero, string $fecha, ?int $documentoId, string $operacionTipo, int $operacionId, string $columna): int
    {
        $allowed=['asignacion_id','devolucion_id','solicitud_traslado_id','movimiento_activo_id','prestamo_id','novedad_id','mantenimiento_id','baja_activo_id','inventario_id','inventario_detalle_id','diferencia_inventario_id'];
        if (!in_array($columna,$allowed,true)) throw new \InvalidArgumentException('Referencia de operación inválida.');
        $stmt=$this->pdo->prepare('INSERT INTO acta (tipo,numero,fecha,documento_id,operacion_tipo,operacion_id,'.$columna.') VALUES (:tipo,:numero,:fecha,:documento,:operacion_tipo,:operacion_id,:referencia_id)');
        $stmt->execute(['tipo'=>$tipo,'numero'=>$numero,'fecha'=>$fecha,'documento'=>$documentoId,'operacion_tipo'=>$operacionTipo,'operacion_id'=>$operacionId,'referencia_id'=>$operacionId]);
        return (int)$this->pdo->lastInsertId();
    }
}
