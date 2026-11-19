<?php

declare(strict_types=1);

namespace App\Controller;

final class PoliticaInstitucionalController extends ApiController
{
    public function listar(array $params = []): never
    {
        $stmt=$this->pdo->query('SELECT clave,valor,estado_validacion,actualizado_en FROM configuracion_institucional ORDER BY clave');
        $rows=$stmt->fetchAll(); foreach($rows as &$row){$row['valor']=json_decode($row['valor'],true,512,JSON_THROW_ON_ERROR);} unset($row);
        $this->respond(['politicas'=>$rows,'retencionAutomatica'=>false]);
    }

    public function actualizar(array $params = []): never
    {
        $body=$this->body(); $key=$this->requiredString($body,'clave',100);
        if($key==='retencion_datos'){
            if(!array_key_exists('dias',$body)||($body['dias']!==null&&(!is_int($body['dias'])||$body['dias']<1||$body['dias']>36500))) throw new ApiException(400,'RETENTION_INVALID','dias debe ser nulo o entre 1 y 36500.');
            $value=['dias'=>$body['dias'],'borrado_automatico'=>false,'validado_por_cecar'=>false];
        } elseif($key==='separacion_aprobacion'){
            $value=['solicitante_puede_aprobar'=>false,'validado_por_cecar'=>false];
        } else throw new ApiException(400,'POLICY_LOCKED','Esta política requiere validación institucional y no se edita desde aquí.');
        $stmt=$this->pdo->prepare('UPDATE configuracion_institucional SET valor=:valor,estado_validacion=\'PENDIENTE\',actualizado_por=:usuario WHERE clave=:clave');
        $stmt->execute(['valor'=>json_encode($value,JSON_THROW_ON_ERROR),'usuario'=>$this->currentUserId(),'clave'=>$key]);
        if($stmt->rowCount()===0){$check=$this->pdo->prepare('SELECT 1 FROM configuracion_institucional WHERE clave=:clave');$check->execute(['clave'=>$key]);if($check->fetchColumn()===false)$this->notFound('No existe esa política.');}
        (new \App\Model\DAO\AuditoriaDAO($this->pdo))->registrar($this->currentUserId(),'configuracion_institucional',null,'ACTUALIZAR_POLITICA',null,null,['clave'=>$key,'valor'=>$value,'estado_validacion'=>'PENDIENTE']);
        $this->respond(['clave'=>$key,'valor'=>$value,'estadoValidacion'=>'PENDIENTE']);
    }
}
