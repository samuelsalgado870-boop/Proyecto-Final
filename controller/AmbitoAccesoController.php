<?php

declare(strict_types=1);

namespace App\Controller;

final class AmbitoAccesoController extends ApiController
{
    public function listar(array $params = []): never
    {
        $userId = filter_var($_GET['usuarioId'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($userId === false || $userId === null) throw new ApiException(400,'USER_ID_INVALID','Indica usuarioId.');
        $stmt=$this->pdo->prepare('SELECT aa.*,s.nombre sede,dep.nombre dependencia,u.nombre ubicacion FROM ambito_acceso aa LEFT JOIN sede s ON s.id=aa.sede_id LEFT JOIN dependencia dep ON dep.id=aa.dependencia_id LEFT JOIN ubicacion u ON u.id=aa.ubicacion_id WHERE aa.usuario_id=:usuario ORDER BY aa.id DESC');
        $stmt->execute(['usuario'=>$userId]); $this->respond($stmt->fetchAll());
    }

    public function crear(array $params = []): never
    {
        $body=$this->body(); $userId=$this->positiveInt($body,'usuarioId'); $type=strtoupper($this->requiredString($body,'tipoAmbito',20));
        $map=['GLOBAL'=>null,'SEDE'=>'sede_id','DEPENDENCIA'=>'dependencia_id','UBICACION'=>'ubicacion_id'];
        if(!array_key_exists($type,$map)) throw new ApiException(400,'SCOPE_TYPE_INVALID','Ámbito no permitido.');
        $ref=$map[$type]===null?null:$this->positiveInt($body,'referenciaId');
        $this->pdo->beginTransaction();
        try {
            $user=$this->pdo->prepare('SELECT id FROM usuario WHERE id=:id AND estado=\'ACTIVO\' FOR UPDATE'); $user->execute(['id'=>$userId]);
            if($user->fetchColumn()===false) $this->notFound('No existe un usuario activo.');
            if($ref!==null){$check=$this->pdo->prepare('SELECT 1 FROM '.($map[$type]==='sede_id'?'sede':($map[$type]==='dependencia_id'?'dependencia':'ubicacion')).' WHERE id=:id');$check->execute(['id'=>$ref]);if($check->fetchColumn()===false)$this->notFound('No existe la referencia institucional.');}
            $params=['usuario'=>$userId,'tipo'=>$type,'sede'=>null,'dependencia'=>null,'ubicacion'=>null];
            if($type==='SEDE')$params['sede']=$ref; if($type==='DEPENDENCIA')$params['dependencia']=$ref; if($type==='UBICACION')$params['ubicacion']=$ref;
            $dup=$this->pdo->prepare('SELECT id FROM ambito_acceso WHERE usuario_id=:usuario AND tipo_ambito=:tipo AND sede_id <=> :sede AND dependencia_id <=> :dependencia AND ubicacion_id <=> :ubicacion AND (vigente_hasta IS NULL OR vigente_hasta>=CURRENT_DATE) LIMIT 1');$dup->execute($params);
            if($dup->fetchColumn()!==false) throw new ApiException(409,'SCOPE_EXISTS','Ese ámbito ya está vigente para el usuario.');
            $insert=$this->pdo->prepare('INSERT INTO ambito_acceso (usuario_id,tipo_ambito,sede_id,dependencia_id,ubicacion_id,vigente_desde) VALUES (:usuario,:tipo,:sede,:dependencia,:ubicacion,CURRENT_DATE)');$insert->execute($params);
            $id=(int)$this->pdo->lastInsertId(); (new \App\Model\DAO\AuditoriaDAO($this->pdo))->registrar($this->currentUserId(),'ambito_acceso',$id,'ASIGNAR_AMBITO',null,null,['usuario_id'=>$userId,'tipo_ambito'=>$type,'referencia_id'=>$ref]); $this->pdo->commit(); $this->respond(['id'=>$id],201);
        } catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }

    public function revocar(array $params): never
    {
        $id=$this->routeId($params);$find=$this->pdo->prepare('SELECT * FROM ambito_acceso WHERE id=:id');$find->execute(['id'=>$id]);$before=$find->fetch();if($before===false)$this->notFound('No existe un ámbito vigente.');
        $stmt=$this->pdo->prepare('UPDATE ambito_acceso SET vigente_hasta=DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY) WHERE id=:id AND (vigente_hasta IS NULL OR vigente_hasta>=CURRENT_DATE)');$stmt->execute(['id'=>$id]);
        if($stmt->rowCount()===0)$this->notFound('No existe un ámbito vigente.');
        (new \App\Model\DAO\AuditoriaDAO($this->pdo))->registrar($this->currentUserId(),'ambito_acceso',$id,'REVOCAR_AMBITO',null,$before,['vigente_hasta'=>date('Y-m-d',strtotime('-1 day'))]);
        $this->respond(['revocado'=>true]);
    }
}
