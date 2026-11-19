-- Los valores de clasificación importados siguen pendientes de aprobación de CECAR.
USE gestion_activos;

UPDATE activo a
JOIN fila_planilla_activo f ON f.activo_id=a.id
SET a.estado_validacion='PENDIENTE';

UPDATE categoria_activo c
JOIN (SELECT DISTINCT a.categoria_id FROM activo a JOIN fila_planilla_activo f ON f.activo_id=a.id) importadas
  ON importadas.categoria_id=c.id
SET c.estado_validacion='PENDIENTE';
