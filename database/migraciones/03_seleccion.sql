-- Extension non-destructive for the selection-process module.
-- Import after database/base/01_schema.sql and, optionally, database/datos-prueba/02_seed.sql.
-- MySQL 8.0.16+ is required for enforced CHECK constraints.
-- Does not drop, truncate, or alter the existing asset-management tables.
USE gestion_activos;

CREATE TABLE IF NOT EXISTS proceso_seleccion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  dependencia_id BIGINT UNSIGNED NOT NULL,
  estado VARCHAR(25) NOT NULL DEFAULT 'BORRADOR',
  fecha_apertura DATE NULL,
  fecha_cierre DATE NULL,
  creado_por BIGINT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sel_proceso_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_sel_proceso_creador FOREIGN KEY (creado_por) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT ck_sel_proceso_estado CHECK (estado IN ('BORRADOR','ABIERTO','EN_REVISION','CERRADO','FINALIZADO')),
  CONSTRAINT ck_sel_proceso_fechas CHECK (fecha_apertura IS NULL OR fecha_cierre IS NULL OR fecha_cierre >= fecha_apertura),
  KEY idx_sel_proceso_estado_fecha (estado, fecha_apertura, fecha_cierre),
  KEY idx_sel_proceso_dependencia (dependencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulante_seleccion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  correo VARCHAR(190) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sel_postulante_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulacion_seleccion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proceso_id BIGINT UNSIGNED NOT NULL,
  postulante_id BIGINT UNSIGNED NOT NULL,
  estado VARCHAR(25) NOT NULL DEFAULT 'EN_REVISION',
  fecha_postulacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sel_postulacion_proceso FOREIGN KEY (proceso_id) REFERENCES proceso_seleccion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_sel_postulacion_postulante FOREIGN KEY (postulante_id) REFERENCES postulante_seleccion(id) ON DELETE RESTRICT,
  CONSTRAINT ck_sel_postulacion_estado CHECK (estado IN ('INCOMPLETA','EN_REVISION','SUBSANACION','APROBADA','RECHAZADA','SELECCIONADA')),
  UNIQUE KEY uq_sel_postulacion_proceso_postulante (proceso_id, postulante_id),
  KEY idx_sel_postulacion_estado_fecha (estado, fecha_postulacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documento_postulacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  postulacion_id BIGINT UNSIGNED NOT NULL,
  tipo_documento VARCHAR(120) NOT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  tipo_mime VARCHAR(100) NOT NULL,
  tamano_bytes BIGINT UNSIGNED NOT NULL,
  ruta_privada VARCHAR(500) NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  estado_revision VARCHAR(25) NOT NULL DEFAULT 'PENDIENTE',
  observacion_revision VARCHAR(1000) NULL,
  subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revisado_por BIGINT UNSIGNED NULL,
  revisado_en DATETIME NULL,
  CONSTRAINT fk_sel_documento_postulacion FOREIGN KEY (postulacion_id) REFERENCES postulacion_seleccion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_sel_documento_revisor FOREIGN KEY (revisado_por) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT ck_sel_documento_estado CHECK (estado_revision IN ('PENDIENTE','APROBADO','RECHAZADO','SUBSANADO')),
  UNIQUE KEY uq_sel_documento_version (postulacion_id, tipo_documento, version),
  KEY idx_sel_documento_estado (estado_revision, subido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions are created without hard-coded IDs. Assign them only to authorized staff roles.
INSERT INTO permiso (modulo, recurso, accion) VALUES
  ('seleccion','proceso','leer'),
  ('seleccion','proceso','administrar'),
  ('seleccion','postulacion','revisar')
ON DUPLICATE KEY UPDATE accion = VALUES(accion);

-- For a local demo only, attach these permissions to the seeded administrator role if it exists.
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM rol r
CROSS JOIN permiso p
WHERE r.nombre = 'ADMIN_PRUEBA'
  AND p.modulo = 'seleccion'
  AND ((p.recurso = 'proceso' AND p.accion IN ('leer','administrar'))
       OR (p.recurso = 'postulacion' AND p.accion = 'revisar'))
ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id);

-- Verification
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('proceso_seleccion','postulante_seleccion','postulacion_seleccion','documento_postulacion')
ORDER BY TABLE_NAME;

SELECT modulo, recurso, accion
FROM permiso
WHERE modulo = 'seleccion'
ORDER BY recurso, accion;
