-- Historial, adjuntos tipados y estado de validación institucional.
-- Ejecutar una sola vez sobre la base gestion_activos.
USE gestion_activos;

ALTER TABLE solicitud_traslado
  ADD COLUMN solicitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD KEY idx_traslado_solicitado_en (solicitado_en);

ALTER TABLE movimiento_activo
  ADD COLUMN creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD KEY idx_movimiento_creado_en (creado_en);

ALTER TABLE categoria_activo
  ADD COLUMN estado_validacion VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE';

ALTER TABLE activo
  ADD COLUMN estado_validacion VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE';

UPDATE categoria_activo SET estado_validacion='PENDIENTE'
WHERE codigo='XLSX-SIN-CLASIFICAR';
UPDATE activo a
JOIN categoria_activo c ON c.id=a.categoria_id
SET a.estado_validacion='PENDIENTE'
WHERE c.codigo='XLSX-SIN-CLASIFICAR';

CREATE TABLE configuracion_institucional (
  clave VARCHAR(100) PRIMARY KEY,
  valor JSON NOT NULL,
  estado_validacion VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
  actualizado_por BIGINT UNSIGNED NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_config_institucional_usuario FOREIGN KEY (actualizado_por) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO configuracion_institucional (clave,valor,estado_validacion) VALUES
('separacion_aprobacion','{"solicitante_puede_aprobar":false,"validado_por_cecar":false}','PENDIENTE'),
('retencion_datos','{"dias":null,"borrado_automatico":false,"validado_por_cecar":false}','PENDIENTE'),
('catalogo_activos','{"categorias":"PENDIENTE","estados":"PENDIENTE"}','PENDIENTE');

ALTER TABLE acta
  ADD COLUMN asignacion_id BIGINT UNSIGNED NULL,
  ADD COLUMN devolucion_id BIGINT UNSIGNED NULL,
  ADD COLUMN solicitud_traslado_id BIGINT UNSIGNED NULL,
  ADD COLUMN movimiento_activo_id BIGINT UNSIGNED NULL,
  ADD COLUMN prestamo_id BIGINT UNSIGNED NULL,
  ADD COLUMN novedad_id BIGINT UNSIGNED NULL,
  ADD COLUMN mantenimiento_id BIGINT UNSIGNED NULL,
  ADD COLUMN baja_activo_id BIGINT UNSIGNED NULL,
  ADD COLUMN inventario_id BIGINT UNSIGNED NULL,
  ADD COLUMN inventario_detalle_id BIGINT UNSIGNED NULL,
  ADD COLUMN diferencia_inventario_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_acta_asignacion FOREIGN KEY (asignacion_id) REFERENCES asignacion(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_devolucion FOREIGN KEY (devolucion_id) REFERENCES devolucion(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_traslado FOREIGN KEY (solicitud_traslado_id) REFERENCES solicitud_traslado(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_movimiento FOREIGN KEY (movimiento_activo_id) REFERENCES movimiento_activo(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamo(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_novedad FOREIGN KEY (novedad_id) REFERENCES novedad(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_mantenimiento FOREIGN KEY (mantenimiento_id) REFERENCES mantenimiento(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_baja FOREIGN KEY (baja_activo_id) REFERENCES baja_activo(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_inventario FOREIGN KEY (inventario_id) REFERENCES inventario(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_inventario_detalle FOREIGN KEY (inventario_detalle_id) REFERENCES inventario_detalle(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_acta_diferencia FOREIGN KEY (diferencia_inventario_id) REFERENCES diferencia_inventario(id) ON DELETE RESTRICT,
  ADD CONSTRAINT ck_acta_operacion_tipada CHECK (
    (IF(asignacion_id IS NULL,0,1)+IF(devolucion_id IS NULL,0,1)+IF(solicitud_traslado_id IS NULL,0,1)+IF(movimiento_activo_id IS NULL,0,1)+IF(prestamo_id IS NULL,0,1)+IF(novedad_id IS NULL,0,1)+IF(mantenimiento_id IS NULL,0,1)+IF(baja_activo_id IS NULL,0,1)+IF(inventario_id IS NULL,0,1)+IF(inventario_detalle_id IS NULL,0,1)+IF(diferencia_inventario_id IS NULL,0,1))=1
    AND ((operacion_tipo='ASIGNACION' AND operacion_id=asignacion_id)
      OR (operacion_tipo='DEVOLUCION' AND operacion_id=devolucion_id)
      OR (operacion_tipo='TRASLADO' AND operacion_id=solicitud_traslado_id)
      OR (operacion_tipo='MOVIMIENTO' AND operacion_id=movimiento_activo_id)
      OR (operacion_tipo='PRESTAMO' AND operacion_id=prestamo_id)
      OR (operacion_tipo='NOVEDAD' AND operacion_id=novedad_id)
      OR (operacion_tipo='MANTENIMIENTO' AND operacion_id=mantenimiento_id)
      OR (operacion_tipo='BAJA' AND operacion_id=baja_activo_id)
      OR (operacion_tipo='INVENTARIO' AND operacion_id=inventario_id)
      OR (operacion_tipo='VERIFICACION' AND operacion_id=inventario_detalle_id)
      OR (operacion_tipo='DIFERENCIA' AND operacion_id=diferencia_inventario_id))
  );

CREATE TABLE documento_operacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  documento_id BIGINT UNSIGNED NOT NULL,
  asignacion_id BIGINT UNSIGNED NULL,
  devolucion_id BIGINT UNSIGNED NULL,
  solicitud_traslado_id BIGINT UNSIGNED NULL,
  movimiento_activo_id BIGINT UNSIGNED NULL,
  prestamo_id BIGINT UNSIGNED NULL,
  novedad_id BIGINT UNSIGNED NULL,
  mantenimiento_id BIGINT UNSIGNED NULL,
  baja_activo_id BIGINT UNSIGNED NULL,
  inventario_id BIGINT UNSIGNED NULL,
  inventario_detalle_id BIGINT UNSIGNED NULL,
  diferencia_inventario_id BIGINT UNSIGNED NULL,
  creado_por BIGINT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doc_operacion_documento FOREIGN KEY (documento_id) REFERENCES documento(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_asignacion FOREIGN KEY (asignacion_id) REFERENCES asignacion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_devolucion FOREIGN KEY (devolucion_id) REFERENCES devolucion(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_traslado FOREIGN KEY (solicitud_traslado_id) REFERENCES solicitud_traslado(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_movimiento FOREIGN KEY (movimiento_activo_id) REFERENCES movimiento_activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_novedad FOREIGN KEY (novedad_id) REFERENCES novedad(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_mantenimiento FOREIGN KEY (mantenimiento_id) REFERENCES mantenimiento(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_baja FOREIGN KEY (baja_activo_id) REFERENCES baja_activo(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_inventario FOREIGN KEY (inventario_id) REFERENCES inventario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_detalle FOREIGN KEY (inventario_detalle_id) REFERENCES inventario_detalle(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_diferencia FOREIGN KEY (diferencia_inventario_id) REFERENCES diferencia_inventario(id) ON DELETE RESTRICT,
  CONSTRAINT fk_doc_operacion_usuario FOREIGN KEY (creado_por) REFERENCES usuario(id) ON DELETE RESTRICT,
  CONSTRAINT ck_doc_operacion_un_tipo CHECK (
    (IF(asignacion_id IS NULL,0,1)+IF(devolucion_id IS NULL,0,1)+IF(solicitud_traslado_id IS NULL,0,1)+IF(movimiento_activo_id IS NULL,0,1)+IF(prestamo_id IS NULL,0,1)+IF(novedad_id IS NULL,0,1)+IF(mantenimiento_id IS NULL,0,1)+IF(baja_activo_id IS NULL,0,1)+IF(inventario_id IS NULL,0,1)+IF(inventario_detalle_id IS NULL,0,1)+IF(diferencia_inventario_id IS NULL,0,1))=1
  ),
  UNIQUE KEY uq_doc_op_asignacion (documento_id,asignacion_id),
  UNIQUE KEY uq_doc_op_devolucion (documento_id,devolucion_id),
  UNIQUE KEY uq_doc_op_traslado (documento_id,solicitud_traslado_id),
  UNIQUE KEY uq_doc_op_movimiento (documento_id,movimiento_activo_id),
  UNIQUE KEY uq_doc_op_prestamo (documento_id,prestamo_id),
  UNIQUE KEY uq_doc_op_novedad (documento_id,novedad_id),
  UNIQUE KEY uq_doc_op_mantenimiento (documento_id,mantenimiento_id),
  UNIQUE KEY uq_doc_op_baja (documento_id,baja_activo_id),
  UNIQUE KEY uq_doc_op_inventario (documento_id,inventario_id),
  UNIQUE KEY uq_doc_op_detalle (documento_id,inventario_detalle_id),
  UNIQUE KEY uq_doc_op_diferencia (documento_id,diferencia_inventario_id)
) ENGINE=InnoDB;

-- El acceso queda cerrado si no hay un ámbito. La cuenta administradora conservada
-- recibe un ámbito global explícito para operar el entorno local.
INSERT INTO ambito_acceso (usuario_id,tipo_ambito)
SELECT u.id,'GLOBAL' FROM usuario u
WHERE u.correo='samuel.camargo@cecar.edu.co'
  AND NOT EXISTS (SELECT 1 FROM ambito_acceso aa WHERE aa.usuario_id=u.id AND aa.tipo_ambito='GLOBAL');
