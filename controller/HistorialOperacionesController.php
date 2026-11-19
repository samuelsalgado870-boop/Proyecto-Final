<?php

declare(strict_types=1);

namespace App\Controller;

use PDO;
use PDOStatement;

final class HistorialOperacionesController extends ApiController
{
    public function listar(array $params = []): never
    {
        $pageRaw = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>1000000]]);
        $limitRaw = filter_var($_GET['limite'] ?? 50, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100]]);
        if ($pageRaw === false || $limitRaw === false) throw new ApiException(400,'PAGINATION_INVALID','pagina debe ser positiva y limite debe estar entre 1 y 100.');
        $page = $pageRaw;
        $limit = $limitRaw;
        $offset = ($page - 1) * $limit;
        $queryRaw=$_GET['q']??'';$typeRaw=$_GET['tipo']??'';
        if(!is_string($queryRaw)||!is_string($typeRaw))throw new ApiException(400,'FILTER_INVALID','Los filtros q y tipo deben ser texto.');
        $query = trim($queryRaw);
        if (mb_strlen($query) > 100) throw new ApiException(400, 'SEARCH_INVALID', 'La búsqueda no puede superar 100 caracteres.');
        $type = strtoupper(trim($typeRaw));
        $allowed = ['', 'ASIGNACION', 'DEVOLUCION', 'TRASLADO', 'MOVIMIENTO', 'PRESTAMO', 'NOVEDAD', 'MANTENIMIENTO', 'BAJA', 'INVENTARIO', 'VERIFICACION', 'DIFERENCIA', 'APROBACION'];
        if (!in_array($type, $allowed, true)) throw new ApiException(400, 'TYPE_INVALID', 'Tipo de operación inválido.');
        $siteRaw = $_GET['sedeId'] ?? '';
        $locationRaw = $_GET['ubicacionId'] ?? '';
        $site = $siteRaw === '' ? null : filter_var($siteRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $location = $locationRaw === '' ? null : filter_var($locationRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (($siteRaw !== '' && $site === false) || ($locationRaw !== '' && $location === false)) throw new ApiException(400, 'SCOPE_FILTER_INVALID', 'La sede o ubicación indicada no es válida.');
        $scope = (new \App\Model\DAO\AutorizacionDAO($this->pdo))->scopePredicate('h');
        $sql = "SELECT h.*,s.nombre sede_nombre,u.nombre ubicacion_nombre FROM (
            SELECT 'ASIGNACION' tipo, a.id, a.fecha_inicio fecha, a.activo_id, ac.placa, ac.descripcion, a.estado, a.observacion detalle, a.ubicacion_id, u.sede_id, a.dependencia_id, a.entregado_por usuario_id FROM asignacion a JOIN activo ac ON ac.id=a.activo_id JOIN ubicacion u ON u.id=a.ubicacion_id
            UNION ALL SELECT 'DEVOLUCION', d.id, d.fecha, COALESCE(a.activo_id,p.activo_id), ac.placa, ac.descripcion, 'REGISTRADA', d.excepcion_justificada, COALESCE(a.ubicacion_id,ac.ubicacion_id), u.sede_id, COALESCE(a.dependencia_id,ac.dependencia_id), d.recibido_por FROM devolucion d LEFT JOIN asignacion a ON a.id=d.asignacion_id LEFT JOIN prestamo p ON p.id=d.prestamo_id JOIN activo ac ON ac.id=COALESCE(a.activo_id,p.activo_id) JOIN ubicacion u ON u.id=COALESCE(a.ubicacion_id,ac.ubicacion_id)
            UNION ALL SELECT 'TRASLADO', t.id, t.solicitado_en, t.activo_id, ac.placa, ac.descripcion, t.estado, t.motivo, t.origen_ubicacion_id, u.sede_id, t.origen_dependencia_id, t.solicitante_id FROM solicitud_traslado t JOIN activo ac ON ac.id=t.activo_id JOIN ubicacion u ON u.id=t.origen_ubicacion_id
            UNION ALL SELECT 'MOVIMIENTO', m.id, COALESCE(m.fecha_salida,m.creado_en), m.activo_id, ac.placa, ac.descripcion, m.tipo, m.motivo, COALESCE(m.destino_ubicacion_id,m.origen_ubicacion_id,ac.ubicacion_id), u.sede_id, COALESCE(m.destino_dependencia_id,m.origen_dependencia_id,ac.dependencia_id), m.usuario_id FROM movimiento_activo m JOIN activo ac ON ac.id=m.activo_id JOIN ubicacion u ON u.id=COALESCE(m.destino_ubicacion_id,m.origen_ubicacion_id,ac.ubicacion_id)
            UNION ALL SELECT 'PRESTAMO', p.id, p.fecha_salida, p.activo_id, ac.placa, ac.descripcion, p.estado, p.destino, ac.ubicacion_id, u.sede_id, ac.dependencia_id, p.responsable_id FROM prestamo p JOIN activo ac ON ac.id=p.activo_id JOIN ubicacion u ON u.id=ac.ubicacion_id
            UNION ALL SELECT 'NOVEDAD', n.id, n.fecha, n.activo_id, ac.placa, ac.descripcion, n.estado, n.descripcion, ac.ubicacion_id, u.sede_id, ac.dependencia_id, n.reportante_id FROM novedad n JOIN activo ac ON ac.id=n.activo_id JOIN ubicacion u ON u.id=ac.ubicacion_id
            UNION ALL SELECT 'MANTENIMIENTO', m.id, m.fecha_inicio, m.activo_id, ac.placa, ac.descripcion, m.estado, m.diagnostico, ac.ubicacion_id, u.sede_id, ac.dependencia_id, NULL FROM mantenimiento m JOIN activo ac ON ac.id=m.activo_id JOIN ubicacion u ON u.id=ac.ubicacion_id
            UNION ALL SELECT 'BAJA', b.id, b.fecha_solicitud, b.activo_id, ac.placa, ac.descripcion, b.estado, b.causal, ac.ubicacion_id, u.sede_id, ac.dependencia_id, NULL FROM baja_activo b JOIN activo ac ON ac.id=b.activo_id JOIN ubicacion u ON u.id=ac.ubicacion_id
            UNION ALL SELECT 'INVENTARIO', i.id, i.fecha_inicio, NULL, NULL, i.nombre, i.estado, i.alcance, i.ubicacion_id, COALESCE(i.sede_id,ui.sede_id), i.dependencia_id, i.responsable_id FROM inventario i LEFT JOIN ubicacion ui ON ui.id=i.ubicacion_id
            UNION ALL SELECT 'VERIFICACION', d.id, d.fecha_verificacion, d.activo_id, COALESCE(ac.placa,d.placa_observada), COALESCE(ac.descripcion,d.ubicacion_observada), d.resultado, d.observacion, COALESCE(i.ubicacion_id,ac.ubicacion_id), COALESCE(i.sede_id,ui.sede_id), COALESCE(i.dependencia_id,ac.dependencia_id), d.verificador_id FROM inventario_detalle d JOIN inventario i ON i.id=d.inventario_id LEFT JOIN activo ac ON ac.id=d.activo_id LEFT JOIN ubicacion ui ON ui.id=COALESCE(i.ubicacion_id,ac.ubicacion_id)
            UNION ALL SELECT 'DIFERENCIA', f.id, f.fecha, d.activo_id, COALESCE(ac.placa,d.placa_observada), i.nombre, f.estado, f.descripcion, COALESCE(i.ubicacion_id,ac.ubicacion_id), COALESCE(i.sede_id,ui.sede_id), COALESCE(i.dependencia_id,ac.dependencia_id), f.responsable_id FROM diferencia_inventario f JOIN inventario_detalle d ON d.id=f.detalle_id JOIN inventario i ON i.id=d.inventario_id LEFT JOIN activo ac ON ac.id=d.activo_id LEFT JOIN ubicacion ui ON ui.id=COALESCE(i.ubicacion_id,ac.ubicacion_id)
            UNION ALL SELECT 'APROBACION', ap.id, ap.fecha, t.activo_id, ac.placa, ac.descripcion, ap.decision, ap.observacion, t.origen_ubicacion_id, u.sede_id, t.origen_dependencia_id, ap.aprobador_id FROM aprobacion ap JOIN solicitud_traslado t ON ap.operacion_tipo='TRASLADO' AND ap.operacion_id=t.id JOIN activo ac ON ac.id=t.activo_id JOIN ubicacion u ON u.id=t.origen_ubicacion_id
        ) h LEFT JOIN sede s ON s.id=h.sede_id LEFT JOIN ubicacion u ON u.id=h.ubicacion_id WHERE (:tipo_filtro='' OR h.tipo=:tipo_valor) AND (:q_filtro='' OR h.placa LIKE :q1 OR h.descripcion LIKE :q2 OR h.detalle LIKE :q3 OR CAST(h.id AS CHAR)=:q4) AND (:sede_filtro IS NULL OR h.sede_id=:sede_valor) AND (:ubicacion_filtro IS NULL OR h.ubicacion_id=:ubicacion_valor) AND {$scope['sql']} ORDER BY h.fecha DESC,h.id DESC LIMIT :limite OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $params = ['tipo_filtro'=>$type,'tipo_valor'=>$type,'q_filtro'=>$query,'q1'=>'%'.$query.'%','q2'=>'%'.$query.'%','q3'=>'%'.$query.'%','q4'=>$query,'sede_filtro'=>$site,'sede_valor'=>$site,'ubicacion_filtro'=>$location,'ubicacion_valor'=>$location,'limite'=>$limit,'offset'=>$offset];
        foreach ($params + $scope['params'] as $name=>$value) $stmt->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : ($value === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
        $stmt->execute();
        $this->respond(['filas'=>$stmt->fetchAll(),'pagina'=>$page,'limite'=>$limit,'sedeId'=>$site === false ? null : $site,'ubicacionId'=>$location === false ? null : $location]);
    }
}
