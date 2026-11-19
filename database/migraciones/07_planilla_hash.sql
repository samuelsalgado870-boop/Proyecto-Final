-- Evita cargar varias veces el mismo archivo XLSX como base institucional.
-- Ejecutar una sola vez en la base configurada, después de 04_planilla_activos_origen.sql.
USE gestion_activos;

ALTER TABLE importacion
  ADD COLUMN archivo_sha256 CHAR(64) NULL,
  ADD UNIQUE KEY uq_importacion_archivo_sha256 (archivo_sha256);
