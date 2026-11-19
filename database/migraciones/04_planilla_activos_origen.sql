-- Almacenamiento fiel de filas provenientes de la planilla institucional.
-- Importar una sola vez después de database/base/01_schema.sql. No modifica activos.
USE gestion_activos;

CREATE TABLE IF NOT EXISTS fila_planilla_activo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  importacion_id BIGINT UNSIGNED NOT NULL,
  numero_fila INT UNSIGNED NOT NULL,
  placa VARCHAR(100) NULL,
  lote VARCHAR(100) NULL,
  descripcion TEXT NULL,
  ubicacion_fisica TEXT NULL,
  identificacion_responsable VARCHAR(100) NULL,
  nombre_responsable VARCHAR(255) NULL,
  centro_costo VARCHAR(100) NULL,
  nombre_centro_costo TEXT NULL,
  nombre_estado VARCHAR(255) NULL,
  depreciado VARCHAR(20) NULL,
  resultado VARCHAR(30) NOT NULL,
  observacion VARCHAR(500) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fila_planilla_importacion FOREIGN KEY (importacion_id) REFERENCES importacion(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_fila_planilla_numero (importacion_id, numero_fila),
  KEY idx_fila_planilla_placa (placa),
  KEY idx_fila_planilla_centro_costo (centro_costo),
  KEY idx_fila_planilla_estado (nombre_estado),
  KEY idx_fila_planilla_resultado (importacion_id, resultado)
) ENGINE=InnoDB;
