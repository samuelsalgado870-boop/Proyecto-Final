-- Esquema inicial para Gestión de Activos Fijos CECAR.
-- Requiere MySQL 8.0+. Script no destructivo: no elimina bases ni tablas.
CREATE DATABASE IF NOT EXISTS gestion_activos
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_activos;

CREATE TABLE usuario (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identificacion VARCHAR(30) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario_identificacion (identificacion),
  UNIQUE KEY uq_usuario_correo (correo)
) ENGINE=InnoDB;

CREATE TABLE rol (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB;

CREATE TABLE permiso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  modulo VARCHAR(100) NOT NULL,
  recurso VARCHAR(100) NOT NULL,
  accion VARCHAR(80) NOT NULL,
  UNIQUE KEY uq_permiso (modulo, recurso, accion)
) ENGINE=InnoDB;

CREATE TABLE usuario_rol (
  usuario_id BIGINT UNSIGNED NOT NULL,
  rol_id BIGINT UNSIGNED NOT NULL,
  fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  asignado_por BIGINT UNSIGNED NULL,
  PRIMARY KEY (usuario_id, rol_id),
  CONSTRAINT fk_usuario_rol_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_usuario_rol_rol FOREIGN KEY (rol_id) REFERENCES rol(id) ON DELETE RESTRICT,
  CONSTRAINT fk_usuario_rol_asignador FOREIGN KEY (asignado_por) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE rol_permiso (
  rol_id BIGINT UNSIGNED NOT NULL,
  permiso_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (rol_id, permiso_id),
  CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES rol(id) ON DELETE RESTRICT,
  CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permiso(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE sede (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  direccion VARCHAR(255) NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA'
) ENGINE=InnoDB;

CREATE TABLE dependencia (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sede_id BIGINT UNSIGNED NULL,
  codigo VARCHAR(30) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  tipo VARCHAR(80) NULL,
  dependencia_padre_id BIGINT UNSIGNED NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
  UNIQUE KEY uq_dependencia_sede_codigo (sede_id, codigo),
  CONSTRAINT fk_dependencia_sede FOREIGN KEY (sede_id) REFERENCES sede(id) ON DELETE RESTRICT,
  CONSTRAINT fk_dependencia_padre FOREIGN KEY (dependencia_padre_id) REFERENCES dependencia(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ubicacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sede_id BIGINT UNSIGNED NOT NULL,
  dependencia_id BIGINT UNSIGNED NULL,
  codigo VARCHAR(30) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  tipo VARCHAR(80) NULL,
  ubicacion_padre_id BIGINT UNSIGNED NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
  UNIQUE KEY uq_ubicacion_sede_codigo (sede_id, codigo),
  CONSTRAINT fk_ubicacion_sede FOREIGN KEY (sede_id) REFERENCES sede(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ubicacion_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ubicacion_padre FOREIGN KEY (ubicacion_padre_id) REFERENCES ubicacion(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE categoria_activo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA'
) ENGINE=InnoDB;

CREATE TABLE categoria_campo (
  categoria_id BIGINT UNSIGNED NOT NULL,
  campo_codigo VARCHAR(50) NOT NULL,
  obligatorio BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (categoria_id, campo_codigo),
  CONSTRAINT fk_categoria_campo_categoria FOREIGN KEY (categoria_id) REFERENCES categoria_activo(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE activo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  placa VARCHAR(50) NOT NULL UNIQUE,
  fecha_asignacion_placa DATE NULL,
  serial VARCHAR(100) NULL,
  descripcion TEXT NOT NULL,
  categoria_id BIGINT UNSIGNED NOT NULL,
  marca VARCHAR(100) NULL,
  modelo VARCHAR(100) NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'REGISTRADO',
  ubicacion_id BIGINT UNSIGNED NOT NULL,
  dependencia_id BIGINT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activo_categoria FOREIGN KEY (categoria_id) REFERENCES categoria_activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_activo_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_activo_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  KEY idx_activo_categoria (categoria_id), KEY idx_activo_estado (estado),
  KEY idx_activo_dependencia_ubicacion (dependencia_id, ubicacion_id)
) ENGINE=InnoDB;

CREATE TABLE ambito_acceso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  tipo_ambito ENUM('GLOBAL','SEDE','DEPENDENCIA','UBICACION','CUSTODIA') NOT NULL,
  sede_id BIGINT UNSIGNED NULL,
  dependencia_id BIGINT UNSIGNED NULL,
  ubicacion_id BIGINT UNSIGNED NULL,
  vigente_desde DATE NULL,
  vigente_hasta DATE NULL,
  CONSTRAINT fk_ambito_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ambito_sede FOREIGN KEY (sede_id) REFERENCES sede(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ambito_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ambito_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT ck_ambito_tipo CHECK (
    (tipo_ambito IN ('GLOBAL','CUSTODIA') AND sede_id IS NULL AND dependencia_id IS NULL AND ubicacion_id IS NULL) OR
    (tipo_ambito = 'SEDE' AND sede_id IS NOT NULL AND dependencia_id IS NULL AND ubicacion_id IS NULL) OR
    (tipo_ambito = 'DEPENDENCIA' AND sede_id IS NULL AND dependencia_id IS NOT NULL AND ubicacion_id IS NULL) OR
    (tipo_ambito = 'UBICACION' AND sede_id IS NULL AND dependencia_id IS NULL AND ubicacion_id IS NOT NULL)
  )
) ENGINE=InnoDB;

CREATE TABLE documento (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_original VARCHAR(255) NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  tamano_bytes BIGINT UNSIGNED NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responsable_id BIGINT UNSIGNED NULL,
  ruta_privada VARCHAR(500) NOT NULL,
  CONSTRAINT fk_documento_responsable FOREIGN KEY (responsable_id) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE asignacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  custodio_id BIGINT UNSIGNED NULL,
  dependencia_id BIGINT UNSIGNED NOT NULL,
  ubicacion_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
  entregado_por BIGINT UNSIGNED NULL,
  fecha_aceptacion DATETIME NULL,
  observacion TEXT NULL,
  vigente_activo_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN fecha_fin IS NULL THEN activo_id ELSE NULL END) STORED,
  CONSTRAINT fk_asignacion_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_asignacion_custodio FOREIGN KEY (custodio_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_asignacion_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_asignacion_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_asignacion_entregado_por FOREIGN KEY (entregado_por) REFERENCES usuario(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_asignacion_vigente (vigente_activo_id),
  KEY idx_asignacion_custodio_vigencia (custodio_id, fecha_fin)
) ENGINE=InnoDB;

CREATE TABLE solicitud_traslado (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  origen_ubicacion_id BIGINT UNSIGNED NOT NULL,
  destino_ubicacion_id BIGINT UNSIGNED NOT NULL,
  origen_dependencia_id BIGINT UNSIGNED NULL,
  destino_dependencia_id BIGINT UNSIGNED NULL,
  motivo TEXT NOT NULL,
  solicitante_id BIGINT UNSIGNED NOT NULL,
  fecha_propuesta DATETIME NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'SOLICITADO',
  CONSTRAINT fk_traslado_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_traslado_origen FOREIGN KEY (origen_ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_traslado_destino FOREIGN KEY (destino_ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_traslado_origen_dep FOREIGN KEY (origen_dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_traslado_destino_dep FOREIGN KEY (destino_dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_traslado_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_traslado_estado_activo (estado, activo_id)
) ENGINE=InnoDB;

CREATE TABLE aprobacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  operacion_tipo VARCHAR(50) NOT NULL,
  operacion_id BIGINT UNSIGNED NOT NULL,
  aprobador_id BIGINT UNSIGNED NOT NULL,
  decision VARCHAR(20) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacion TEXT NULL,
  CONSTRAINT fk_aprobacion_aprobador FOREIGN KEY (aprobador_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_aprobacion_operacion (operacion_tipo, operacion_id)
) ENGINE=InnoDB;

CREATE TABLE movimiento_activo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  usuario_id BIGINT UNSIGNED NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  motivo TEXT NULL,
  origen_ubicacion_id BIGINT UNSIGNED NULL,
  destino_ubicacion_id BIGINT UNSIGNED NULL,
  origen_dependencia_id BIGINT UNSIGNED NULL,
  destino_dependencia_id BIGINT UNSIGNED NULL,
  fecha_salida DATETIME NULL,
  fecha_recepcion DATETIME NULL,
  observaciones TEXT NULL,
  CONSTRAINT fk_movimiento_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_origen_ubi FOREIGN KEY (origen_ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_destino_ubi FOREIGN KEY (destino_ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_origen_dep FOREIGN KEY (origen_dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_destino_dep FOREIGN KEY (destino_dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  KEY idx_movimiento_activo_fecha (activo_id, fecha_salida)
) ENGINE=InnoDB;

CREATE TABLE prestamo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  responsable_id BIGINT UNSIGNED NOT NULL,
  destino VARCHAR(255) NULL,
  fecha_salida DATETIME NOT NULL,
  fecha_limite DATETIME NOT NULL,
  condiciones_retorno TEXT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'EN_PRESTAMO',
  CONSTRAINT fk_prestamo_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_prestamo_responsable FOREIGN KEY (responsable_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_prestamo_estado_limite (estado, fecha_limite)
) ENGINE=InnoDB;

CREATE TABLE novedad (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  reportante_id BIGINT UNSIGNED NOT NULL,
  tipo VARCHAR(80) NOT NULL,
  descripcion TEXT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(30) NOT NULL DEFAULT 'ABIERTA',
  CONSTRAINT fk_novedad_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_novedad_reportante FOREIGN KEY (reportante_id) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE mantenimiento (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  diagnostico TEXT NOT NULL,
  acciones TEXT NULL,
  tecnico_proveedor VARCHAR(150) NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  resultado TEXT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'EN_MANTENIMIENTO',
  CONSTRAINT fk_mantenimiento_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  KEY idx_mantenimiento_activo_fecha (activo_id, fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE devolucion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asignacion_id BIGINT UNSIGNED NULL,
  prestamo_id BIGINT UNSIGNED NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  recibido_por BIGINT UNSIGNED NOT NULL,
  estado_fisico VARCHAR(80) NOT NULL,
  destino_posterior VARCHAR(255) NULL,
  excepcion_justificada TEXT NULL,
  CONSTRAINT fk_devolucion_asignacion FOREIGN KEY (asignacion_id) REFERENCES asignacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_devolucion_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_devolucion_recibido_por FOREIGN KEY (recibido_por) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT ck_devolucion_origen CHECK ((asignacion_id IS NOT NULL) <> (prestamo_id IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE baja_activo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activo_id BIGINT UNSIGNED NOT NULL,
  causal TEXT NOT NULL,
  solicitud TEXT NOT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'EN_TRAMITE',
  fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_cierre DATETIME NULL,
  CONSTRAINT fk_baja_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  KEY idx_baja_activo_estado (activo_id, estado)
) ENGINE=InnoDB;

CREATE TABLE acta (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(50) NOT NULL,
  numero VARCHAR(60) NOT NULL,
  fecha DATE NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  documento_id BIGINT UNSIGNED NULL,
  operacion_tipo VARCHAR(50) NOT NULL,
  operacion_id BIGINT UNSIGNED NOT NULL,
  CONSTRAINT fk_acta_documento FOREIGN KEY (documento_id) REFERENCES documento(id) ON DELETE RESTRICT,
  KEY idx_acta_operacion (operacion_tipo, operacion_id)
) ENGINE=InnoDB;

CREATE TABLE acta_participante (
  acta_id BIGINT UNSIGNED NOT NULL,
  usuario_id BIGINT UNSIGNED NOT NULL,
  papel VARCHAR(80) NOT NULL,
  PRIMARY KEY (acta_id, usuario_id),
  CONSTRAINT fk_acta_participante_acta FOREIGN KEY (acta_id) REFERENCES acta(id) ON DELETE RESTRICT,
  CONSTRAINT fk_acta_participante_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE inventario (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  alcance VARCHAR(255) NOT NULL,
  sede_id BIGINT UNSIGNED NULL,
  dependencia_id BIGINT UNSIGNED NULL,
  ubicacion_id BIGINT UNSIGNED NULL,
  categoria_id BIGINT UNSIGNED NULL,
  responsable_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'PLANIFICADO',
  CONSTRAINT fk_inventario_sede FOREIGN KEY (sede_id) REFERENCES sede(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inventario_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencia(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inventario_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inventario_categoria FOREIGN KEY (categoria_id) REFERENCES categoria_activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inventario_responsable FOREIGN KEY (responsable_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_inventario_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE inventario_detalle (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inventario_id BIGINT UNSIGNED NOT NULL,
  activo_id BIGINT UNSIGNED NULL,
  verificador_id BIGINT UNSIGNED NULL,
  resultado VARCHAR(60) NOT NULL,
  placa_observada VARCHAR(60) NULL,
  ubicacion_observada VARCHAR(255) NULL,
  condicion_fisica VARCHAR(80) NULL,
  fecha_verificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacion TEXT NULL,
  CONSTRAINT fk_inv_det_inventario FOREIGN KEY (inventario_id) REFERENCES inventario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inv_det_activo FOREIGN KEY (activo_id) REFERENCES activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inv_det_verificador FOREIGN KEY (verificador_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_inventario_activo (inventario_id, activo_id)
) ENGINE=InnoDB;

CREATE TABLE diferencia_inventario (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  detalle_id BIGINT UNSIGNED NOT NULL,
  descripcion TEXT NOT NULL,
  decision TEXT NULL,
  responsable_id BIGINT UNSIGNED NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
  CONSTRAINT fk_diferencia_detalle FOREIGN KEY (detalle_id) REFERENCES inventario_detalle(id) ON DELETE RESTRICT,
  CONSTRAINT fk_diferencia_responsable FOREIGN KEY (responsable_id) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  entidad VARCHAR(100) NOT NULL,
  entidad_id BIGINT UNSIGNED NULL,
  accion VARCHAR(80) NOT NULL,
  motivo VARCHAR(255) NULL,
  antes JSON NULL,
  despues JSON NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_auditoria_entidad_fecha (entidad, entidad_id, fecha_hora)
) ENGINE=InnoDB;

CREATE TABLE notificacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  tipo VARCHAR(80) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
  referencia_tipo VARCHAR(50) NULL,
  referencia_id BIGINT UNSIGNED NULL,
  CONSTRAINT fk_notificacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_notificacion_usuario_estado (usuario_id, estado)
) ENGINE=InnoDB;

CREATE TABLE importacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_archivo VARCHAR(255) NOT NULL,
  usuario_id BIGINT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  registros_aceptados INT UNSIGNED NOT NULL DEFAULT 0,
  registros_rechazados INT UNSIGNED NOT NULL DEFAULT 0,
  estado VARCHAR(40) NOT NULL DEFAULT 'VALIDANDO',
  CONSTRAINT fk_importacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  KEY idx_importacion_fecha_estado (fecha, estado)
) ENGINE=InnoDB;

CREATE TABLE detalle_importacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  importacion_id BIGINT UNSIGNED NOT NULL,
  numero_fila INT UNSIGNED NOT NULL,
  resultado VARCHAR(30) NOT NULL,
  error TEXT NULL,
  posible_duplicado_id BIGINT UNSIGNED NULL,
  CONSTRAINT fk_detalle_importacion FOREIGN KEY (importacion_id) REFERENCES importacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_detalle_posible_duplicado FOREIGN KEY (posible_duplicado_id) REFERENCES activo(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_importacion_fila (importacion_id, numero_fila)
) ENGINE=InnoDB;
