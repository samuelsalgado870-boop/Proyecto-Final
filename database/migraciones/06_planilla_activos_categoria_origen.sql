-- Guarda la categoría por fila suministrada en la planilla XLSX.
-- Importar una sola vez después de database/migraciones/05_planilla_activos_conversion.sql.
USE gestion_activos;

ALTER TABLE fila_planilla_activo
  ADD COLUMN categoria_origen VARCHAR(255) NULL;
