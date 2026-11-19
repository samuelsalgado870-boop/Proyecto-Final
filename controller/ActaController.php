<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DAO\ActaDAO;

final class ActaController extends ApiController
{
    public function crear(array $params = []): never
    {
        $body = $this->body();
        $type = strtoupper($this->requiredString($body, 'operacionTipo', 30));
        $operationId = $this->positiveInt($body, 'operacionId');
        $documentId = $this->positiveInt($body, 'documentoId', true);
        $date = $this->requiredString($body, 'fecha', 10);
        $parsedDate=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);$dateErrors=\DateTimeImmutable::getLastErrors();
        if ($parsedDate===false || $parsedDate->format('Y-m-d')!==$date || ($dateErrors!==false && ($dateErrors['warning_count']>0 || $dateErrors['error_count']>0))) throw new ApiException(400, 'DATE_INVALID', 'fecha debe tener formato AAAA-MM-DD y ser una fecha real.');
        $map = ['ASIGNACION'=>'asignacion_id','DEVOLUCION'=>'devolucion_id','TRASLADO'=>'solicitud_traslado_id','MOVIMIENTO'=>'movimiento_activo_id','PRESTAMO'=>'prestamo_id','NOVEDAD'=>'novedad_id','MANTENIMIENTO'=>'mantenimiento_id','BAJA'=>'baja_activo_id','INVENTARIO'=>'inventario_id','VERIFICACION'=>'inventario_detalle_id','DIFERENCIA'=>'diferencia_inventario_id'];
        if (!isset($map[$type])) throw new ApiException(400, 'OPERATION_TYPE_INVALID', 'Tipo de operación no permitido.');
        (new DocumentoController($this->pdo))->validarOperacion($map[$type], (int)$operationId);
        if($documentId!==null){$linked=$this->pdo->prepare('SELECT 1 FROM documento_operacion WHERE documento_id=:documento AND '.$map[$type].'=:operacion');$linked->execute(['documento'=>$documentId,'operacion'=>$operationId]);if($linked->fetchColumn()===false)throw new ApiException(409,'DOCUMENT_OPERATION_MISMATCH','El documento debe estar asociado a esta misma operación.');}
        $dao = new ActaDAO($this->pdo);
        $actaId = $dao->registrarTipada($this->requiredString($body,'tipo',50),$this->requiredString($body,'numero',60),$date,$documentId,$type,(int)$operationId,$map[$type]);
        $this->respond(['id'=>$actaId],201);
    }

    public function listar(array $params = []): never
    {
        $type = strtoupper(trim((string)($_GET['operacionTipo']??'')));
        $id = filter_var($_GET['operacionId']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $map = ['ASIGNACION'=>'asignacion_id','DEVOLUCION'=>'devolucion_id','TRASLADO'=>'solicitud_traslado_id','MOVIMIENTO'=>'movimiento_activo_id','PRESTAMO'=>'prestamo_id','NOVEDAD'=>'novedad_id','MANTENIMIENTO'=>'mantenimiento_id','BAJA'=>'baja_activo_id','INVENTARIO'=>'inventario_id','VERIFICACION'=>'inventario_detalle_id','DIFERENCIA'=>'diferencia_inventario_id'];
        if (!isset($map[$type]) || $id===false || $id===null) throw new ApiException(400,'OPERATION_LINK_INVALID','Indica tipo e ID de operación válidos.');
        (new DocumentoController($this->pdo))->validarOperacion($map[$type],(int)$id);
        $stmt=$this->pdo->prepare('SELECT id,tipo,numero,fecha,version,documento_id FROM acta WHERE '.$map[$type].'=:id ORDER BY fecha DESC,id DESC');
        $stmt->execute(['id'=>$id]); $this->respond($stmt->fetchAll());
    }
}
