-- Seguimiento de filas de planilla convertidas en activos operativos.
-- Importar una sola vez después de database/migraciones/04_planilla_activos_origen.sql.
USE gestion_activos;

ALTER TABLE fila_planilla_activo
  ADD COLUMN conversion_estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
  ADD COLUMN activo_id BIGINT UNSIGNED NULL,
  ADD COLUMN conversion_observacion VARCHAR(500) NULL,
  ADD UNIQUE KEY uq_fila_planilla_activo (activo_id),
  ADD CONSTRAINT fk_fila_planilla_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT;

UPDATE fila_planilla_activo
SET conversion_estado = 'OMITIDO',
    conversion_observacion = observacion
WHERE resultado IN ('SIN_PLACA', 'PLACA_REPETIDA');
