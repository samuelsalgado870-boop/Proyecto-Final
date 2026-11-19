-- Datos de prueba 100% ficticios para gestion_activos.
-- Importar DESPUÉS de database/base/01_schema.sql. Este archivo no borra datos.
-- IDs de prueba: 9001-9999. Ejecutarlo solo en una BD vacía o de pruebas.
USE gestion_activos;
START TRANSACTION;

-- Hash bcrypt generado con password_hash() para "Demo-Activo-2026!".
-- Solo para entorno local. En producción, crear usuarios y hashes con password_hash().
INSERT INTO usuario (id, identificacion, nombre, correo, password_hash, estado, ultimo_acceso, creado_en) VALUES
(9001,'TEST-9001','Alex Rivera (prueba)','alex.rivera@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9002,'TEST-9002','Bruno Salas (prueba)','bruno.salas@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9003,'TEST-9003','Camila Ríos (prueba)','camila.rios@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9004,'TEST-9004','Diego Mora (prueba)','diego.mora@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9005,'TEST-9005','Elena Cruz (prueba)','elena.cruz@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9006,'TEST-9006','Fabio León (prueba)','fabio.leon@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9007,'TEST-9007','Gabriela Pardo (prueba)','gabriela.pardo@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9008,'TEST-9008','Hugo Peña (prueba)','hugo.pena@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9009,'TEST-9009','Inés Gil (prueba)','ines.gil@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00'),
(9010,'TEST-9010','Julián Vega (prueba)','julian.vega@example.test','$2y$12$sjDIXw9yF1zeFPMu1DM9UOIamazPXAe7duyLHyfZcd3ajr7axfDSS','ACTIVO',NULL,'2026-01-05 09:00:00');

INSERT INTO rol (id,nombre,descripcion,estado) VALUES
(9001,'ADMIN_PRUEBA','Administración de pruebas','ACTIVO'),
(9002,'INVENTARIO_PRUEBA','Inventario de pruebas','ACTIVO'),
(9003,'CUSTODIO_PRUEBA','Custodia de pruebas','ACTIVO'),
(9004,'CONSULTA_PRUEBA','Consulta de pruebas','ACTIVO'),
(9005,'APROBADOR_PRUEBA','Aprobación de pruebas','ACTIVO'),
(9006,'TECNICO_PRUEBA','Mantenimiento de pruebas','ACTIVO'),
(9007,'AUDITOR_PRUEBA','Auditoría de pruebas','ACTIVO'),
(9008,'IMPORTADOR_PRUEBA','Importación de pruebas','ACTIVO'),
(9009,'SUPERVISOR_PRUEBA','Supervisión de pruebas','ACTIVO'),
(9010,'OPERADOR_PRUEBA','Operación de pruebas','ACTIVO');

INSERT INTO permiso (id,modulo,recurso,accion) VALUES
(9001,'activos','ficha','crear'),(9002,'activos','ficha','leer'),(9003,'activos','ficha','actualizar'),
(9004,'activos','ficha','dar_baja'),(9005,'asignaciones','asignacion','crear'),(9006,'traslados','solicitud','aprobar'),
(9007,'inventarios','jornada','crear'),(9008,'inventarios','detalle','verificar'),(9009,'reportes','informe','exportar'),
(9010,'usuarios','cuenta','administrar');

INSERT INTO sede (id,codigo,nombre,direccion,estado) VALUES
(9001,'SED-PR-01','Sede de prueba Norte','Calle Ficticia 100','ACTIVA'),
(9002,'SED-PR-02','Sede de prueba Centro','Carrera Imaginaria 200','ACTIVA'),
(9003,'SED-PR-03','Sede de prueba Sur','Avenida Simulada 300','ACTIVA'),
(9004,'SED-PR-04','Sede de prueba Río','Dirección ficticia 4','ACTIVA'),
(9005,'SED-PR-05','Sede de prueba Bosque','Dirección ficticia 5','ACTIVA'),
(9006,'SED-PR-06','Sede de prueba Lago','Dirección ficticia 6','ACTIVA'),
(9007,'SED-PR-07','Sede de prueba Valle','Dirección ficticia 7','ACTIVA'),
(9008,'SED-PR-08','Sede de prueba Sierra','Dirección ficticia 8','ACTIVA'),
(9009,'SED-PR-09','Sede de prueba Prado','Dirección ficticia 9','ACTIVA'),
(9010,'SED-PR-10','Sede de prueba Jardín','Dirección ficticia 10','INACTIVA');

INSERT INTO dependencia (id,sede_id,codigo,nombre,tipo,dependencia_padre_id,estado) VALUES
(9001,9001,'DEP-PR-01','Facultad de Ciencias (prueba)','FACULTAD',NULL,'ACTIVA'),
(9002,9001,'DEP-PR-02','Laboratorio Alfa (prueba)','LABORATORIO',9001,'ACTIVA'),
(9003,9002,'DEP-PR-03','Biblioteca Beta (prueba)','BIBLIOTECA',NULL,'ACTIVA'),
(9004,9003,'DEP-PR-04','Administración Gamma (prueba)','ADMINISTRATIVA',NULL,'ACTIVA'),
(9005,9004,'DEP-PR-05','Centro Delta (prueba)','CENTRO',NULL,'ACTIVA'),
(9006,9005,'DEP-PR-06','Facultad Épsilon (prueba)','FACULTAD',NULL,'ACTIVA'),
(9007,9006,'DEP-PR-07','Laboratorio Zeta (prueba)','LABORATORIO',NULL,'ACTIVA'),
(9008,9007,'DEP-PR-08','Archivo Eta (prueba)','ARCHIVO',NULL,'ACTIVA'),
(9009,9008,'DEP-PR-09','Taller Theta (prueba)','TALLER',NULL,'ACTIVA'),
(9010,9009,'DEP-PR-10','Centro Iota (prueba)','CENTRO',NULL,'INACTIVA');

INSERT INTO ubicacion (id,sede_id,dependencia_id,codigo,nombre,tipo,ubicacion_padre_id,estado) VALUES
(9001,9001,9001,'UBI-PR-01','Edificio A - recepción','EDIFICIO',NULL,'ACTIVA'),
(9002,9001,9002,'UBI-PR-02','Laboratorio A-101','LABORATORIO',9001,'ACTIVA'),
(9003,9002,9003,'UBI-PR-03','Biblioteca - sala 1','SALA',NULL,'ACTIVA'),
(9004,9003,9004,'UBI-PR-04','Oficina administrativa 2','OFICINA',NULL,'ACTIVA'),
(9005,9004,9005,'UBI-PR-05','Aula D-12','AULA',NULL,'ACTIVA'),
(9006,9005,9006,'UBI-PR-06','Edificio E - piso 2','PISO',NULL,'ACTIVA'),
(9007,9006,9007,'UBI-PR-07','Laboratorio Z-03','LABORATORIO',NULL,'ACTIVA'),
(9008,9007,9008,'UBI-PR-08','Archivo central - módulo 4','ARCHIVO',NULL,'ACTIVA'),
(9009,9008,9009,'UBI-PR-09','Taller de soporte','TALLER',NULL,'ACTIVA'),
(9010,9009,9010,'UBI-PR-10','Centro de prueba cerrado','OFICINA',NULL,'INACTIVA');

INSERT INTO categoria_activo (id,codigo,nombre,estado) VALUES
(9001,'CAT-PR-01','Cómputo de prueba','ACTIVA'),(9002,'CAT-PR-02','Mobiliario de prueba','ACTIVA'),
(9003,'CAT-PR-03','Audiovisual de prueba','ACTIVA'),(9004,'CAT-PR-04','Laboratorio de prueba','ACTIVA'),
(9005,'CAT-PR-05','Redes de prueba','ACTIVA'),(9006,'CAT-PR-06','Impresión de prueba','ACTIVA'),
(9007,'CAT-PR-07','Herramientas de prueba','ACTIVA'),(9008,'CAT-PR-08','Seguridad de prueba','ACTIVA'),
(9009,'CAT-PR-09','Comunicaciones de prueba','ACTIVA'),(9010,'CAT-PR-10','Categoría archivada de prueba','INACTIVA');

INSERT INTO categoria_campo (categoria_id,campo_codigo,obligatorio) VALUES
(9001,'serial',TRUE),(9001,'marca',TRUE),(9002,'material',FALSE),(9003,'serial',TRUE),(9004,'codigo_interno',TRUE),
(9005,'serial',TRUE),(9006,'serial',FALSE),(9007,'estado_calibracion',FALSE),(9008,'fecha_revision',FALSE),(9009,'serial',TRUE);

INSERT INTO activo (id,placa,fecha_asignacion_placa,serial,descripcion,categoria_id,marca,modelo,estado,ubicacion_id,dependencia_id,creado_en) VALUES
(9001,'PR-AC-0001','2024-01-10','PR-SER-0001','Portátil de prueba para oficina',9001,'Marca ficticia A','Modelo A1','ASIGNADO',9001,9001,'2024-01-10 10:00:00'),
(9002,'PR-AC-0002','2024-02-10','PR-SER-0002','Silla ergonómica de prueba',9002,'Muebles ficticios','Ergo 2','DISPONIBLE',9002,9002,'2024-02-10 10:00:00'),
(9003,'PR-AC-0003','2024-03-10','PR-SER-0003','Proyector de prueba',9003,'Marca ficticia B','Luz 3','EN_PRESTAMO',9003,9003,'2024-03-10 10:00:00'),
(9004,'PR-AC-0004','2024-04-10',NULL,'Microscopio de prueba',9004,'Marca ficticia C','Lab 4','EN_MANTENIMIENTO',9004,9004,'2024-04-10 10:00:00'),
(9005,'PR-AC-0005','2024-05-10','PR-SER-0005','Switch de red de prueba',9005,'Marca ficticia D','Net 5','TRASLADO_SOLICITADO',9005,9005,'2024-05-10 10:00:00'),
(9006,'PR-AC-0006','2024-06-10','PR-SER-0006','Impresora de prueba',9006,'Marca ficticia E','Print 6','EN_REVISION',9006,9006,'2024-06-10 10:00:00'),
(9007,'PR-AC-0007','2024-07-10',NULL,'Taladro de banco ficticio',9007,'Marca ficticia F','Tool 7','REGISTRADO',9007,9007,'2024-07-10 10:00:00'),
(9008,'PR-AC-0008','2024-08-10','PR-SER-0008','Cámara de seguridad de prueba',9008,'Marca ficticia G','Secure 8','DADO_DE_BAJA',9008,9008,'2024-08-10 10:00:00'),
(9009,'PR-AC-0009','2024-09-10','PR-SER-0009','Radio de prueba',9009,'Marca ficticia H','Radio 9','ASIGNADO',9009,9009,'2024-09-10 10:00:00'),
(9010,'PR-AC-0010','2024-10-10','PR-SER-0010','Activo archivado de prueba',9010,'Marca ficticia I','Old 10','INACTIVO',9010,9010,'2024-10-10 10:00:00');

INSERT INTO usuario_rol (usuario_id,rol_id,fecha_asignacion,asignado_por) VALUES
(9001,9001,'2026-01-06 09:00:00',9001),(9002,9002,'2026-01-06 09:00:00',9001),
(9003,9003,'2026-01-06 09:00:00',9001),(9004,9004,'2026-01-06 09:00:00',9001),
(9005,9005,'2026-01-06 09:00:00',9001),(9006,9006,'2026-01-06 09:00:00',9001),
(9007,9007,'2026-01-06 09:00:00',9001),(9008,9008,'2026-01-06 09:00:00',9001),
(9009,9009,'2026-01-06 09:00:00',9001),(9010,9010,'2026-01-06 09:00:00',9001);

INSERT INTO rol_permiso (rol_id,permiso_id) VALUES
(9001,9001),(9001,9002),(9001,9003),(9001,9004),(9001,9005),(9001,9006),(9001,9007),(9001,9008),(9001,9009),(9001,9010),
(9002,9002),(9002,9007),(9002,9008),(9003,9002),(9003,9005),(9004,9002),(9005,9006),(9006,9003),(9007,9002),(9008,9009);

INSERT INTO ambito_acceso (id,usuario_id,tipo_ambito,sede_id,dependencia_id,ubicacion_id,vigente_desde,vigente_hasta) VALUES
(9001,9001,'GLOBAL',NULL,NULL,NULL,'2026-01-01',NULL),(9002,9002,'SEDE',9001,NULL,NULL,'2026-01-01',NULL),
(9003,9003,'DEPENDENCIA',NULL,9002,NULL,'2026-01-01',NULL),(9004,9004,'UBICACION',NULL,NULL,9003,'2026-01-01',NULL),
(9005,9005,'CUSTODIA',NULL,NULL,NULL,'2026-01-01',NULL),(9006,9006,'SEDE',9006,NULL,NULL,'2026-01-01','2026-12-31'),
(9007,9007,'DEPENDENCIA',NULL,9007,NULL,'2026-01-01',NULL),(9008,9008,'UBICACION',NULL,NULL,9008,'2026-01-01',NULL),
(9009,9009,'SEDE',9008,NULL,NULL,'2026-01-01',NULL),(9010,9010,'CUSTODIA',NULL,NULL,NULL,'2026-01-01',NULL);

INSERT INTO documento (id,nombre_original,tipo,tamano_bytes,version,fecha,responsable_id,ruta_privada) VALUES
(9001,'ficha_equipo_prueba_01.pdf','FICHA',204800,1,'2026-01-10 09:00:00',9001,'private/test/doc-9001.pdf'),
(9002,'acta_prueba_02.pdf','ACTA',180000,1,'2026-01-11 09:00:00',9002,'private/test/doc-9002.pdf'),
(9003,'garantia_prueba_03.pdf','GARANTIA',95000,1,'2026-01-12 09:00:00',9003,'private/test/doc-9003.pdf'),
(9004,'mantenimiento_prueba_04.pdf','MANTENIMIENTO',110000,1,'2026-01-13 09:00:00',9004,'private/test/doc-9004.pdf'),
(9005,'inventario_prueba_05.pdf','INVENTARIO',130000,1,'2026-01-14 09:00:00',9005,'private/test/doc-9005.pdf'),
(9006,'baja_prueba_06.pdf','BAJA',145000,1,'2026-01-15 09:00:00',9006,'private/test/doc-9006.pdf'),
(9007,'traslado_prueba_07.pdf','TRASLADO',87000,1,'2026-01-16 09:00:00',9007,'private/test/doc-9007.pdf'),
(9008,'prestamo_prueba_08.pdf','PRESTAMO',76000,1,'2026-01-17 09:00:00',9008,'private/test/doc-9008.pdf'),
(9009,'revision_prueba_09.pdf','REVISION',68000,1,'2026-01-18 09:00:00',9009,'private/test/doc-9009.pdf'),
(9010,'importacion_prueba_10.csv','IMPORTACION',54000,1,'2026-01-19 09:00:00',9010,'private/test/doc-9010.csv');

INSERT INTO asignacion (id,activo_id,custodio_id,dependencia_id,ubicacion_id,fecha_inicio,fecha_fin,estado,entregado_por,fecha_aceptacion,observacion) VALUES
(9001,9001,9002,9001,9001,'2025-01-10 09:00:00','2026-01-01 09:00:00','FINALIZADA',9001,'2025-01-10 10:00:00','Cierre previo al préstamo'),
(9002,9002,9003,9002,9002,'2024-01-10 09:00:00','2024-12-10 09:00:00','FINALIZADA',9001,'2024-01-10 10:00:00','Asignación histórica'),
(9003,9003,9004,9003,9003,'2025-02-10 09:00:00','2026-01-01 09:00:00','FINALIZADA',9001,'2025-02-10 10:00:00','Cierre previo al préstamo'),
(9004,9004,9005,9004,9004,'2024-02-10 09:00:00','2024-08-10 09:00:00','FINALIZADA',9001,'2024-02-10 10:00:00','Asignación histórica'),
(9005,9005,9006,9005,9005,'2025-03-10 09:00:00',NULL,'PENDIENTE',9001,NULL,'Pendiente de aceptación'),
(9006,9006,9007,9006,9006,'2024-03-10 09:00:00','2024-09-10 09:00:00','FINALIZADA',9001,'2024-03-10 10:00:00','Asignación histórica'),
(9007,9007,9008,9007,9007,'2025-04-10 09:00:00','2025-12-31 09:00:00','FINALIZADA',9001,'2025-04-10 10:00:00','Cierre previo a solicitud de baja'),
(9008,9008,9009,9008,9008,'2024-04-10 09:00:00','2024-10-10 09:00:00','FINALIZADA',9001,'2024-04-10 10:00:00','Cierre previo a baja'),
(9009,9009,9010,9009,9009,'2025-05-10 09:00:00',NULL,'OBSERVADA',9001,NULL,'Pendiente de revisión'),
(9010,9010,9002,9010,9010,'2024-05-10 09:00:00','2024-11-10 09:00:00','FINALIZADA',9001,'2024-05-10 10:00:00','Asignación histórica');

INSERT INTO solicitud_traslado (id,activo_id,origen_ubicacion_id,destino_ubicacion_id,origen_dependencia_id,destino_dependencia_id,motivo,solicitante_id,fecha_propuesta,estado) VALUES
(9001,9001,9001,9002,9001,9002,'Reubicación planificada de prueba',9002,'2026-02-01 09:00:00','SOLICITADO'),
(9002,9002,9002,9003,9002,9003,'Ajuste de espacio de prueba',9003,'2026-02-02 09:00:00','AUTORIZADO'),
(9003,9003,9003,9004,9003,9004,'Cambio de sala de prueba',9004,'2026-02-03 09:00:00','RECHAZADO'),
(9004,9004,9004,9005,9004,9005,'Traslado por mantenimiento de prueba',9005,'2026-02-04 09:00:00','EN_TRASLADO'),
(9005,9005,9005,9006,9005,9006,'Reorganización de laboratorio de prueba',9006,'2026-02-05 09:00:00','COMPLETADO'),
(9006,9006,9006,9007,9006,9007,'Cambio de ubicación temporal de prueba',9007,'2026-02-06 09:00:00','SOLICITADO'),
(9007,9007,9007,9008,9007,9008,'Optimización de espacio de prueba',9008,'2026-02-07 09:00:00','AUTORIZADO'),
(9008,9008,9008,9009,9008,9009,'Movimiento previo a cierre de prueba',9009,'2026-02-08 09:00:00','RECHAZADO'),
(9009,9009,9009,9001,9009,9001,'Reubicación para inventario de prueba',9010,'2026-02-09 09:00:00','EN_TRASLADO'),
(9010,9010,9010,9001,9010,9001,'Reapertura de ubicación de prueba',9002,'2026-02-10 09:00:00','COMPLETADO');

INSERT INTO aprobacion (id,operacion_tipo,operacion_id,aprobador_id,decision,fecha,observacion) VALUES
(9001,'TRASLADO',9001,9005,'PENDIENTE','2026-01-20 09:00:00','En espera'),(9002,'TRASLADO',9002,9005,'APROBADA','2026-01-21 09:00:00','Aprobación ficticia'),
(9003,'TRASLADO',9003,9006,'RECHAZADA','2026-01-22 09:00:00','Falta justificación ficticia'),(9004,'TRASLADO',9004,9005,'APROBADA','2026-01-23 09:00:00','Aprobación ficticia'),
(9005,'TRASLADO',9005,9006,'APROBADA','2026-01-24 09:00:00','Aprobación ficticia'),(9006,'BAJA',9001,9005,'PENDIENTE','2026-01-25 09:00:00','En espera'),
(9007,'TRASLADO',9006,9005,'PENDIENTE','2026-01-26 09:00:00','En espera'),(9008,'TRASLADO',9007,9006,'APROBADA','2026-01-27 09:00:00','Aprobación ficticia'),
(9009,'BAJA',9002,9005,'RECHAZADA','2026-01-28 09:00:00','Se conserva activo ficticio'),(9010,'TRASLADO',9009,9006,'PENDIENTE','2026-01-29 09:00:00','En espera');

INSERT INTO movimiento_activo (id,activo_id,usuario_id,tipo,motivo,origen_ubicacion_id,destino_ubicacion_id,origen_dependencia_id,destino_dependencia_id,fecha_salida,fecha_recepcion,observaciones) VALUES
(9001,9001,9001,'TRASLADO','Movimiento de prueba',9001,9002,9001,9002,'2025-01-02 09:00:00','2025-01-02 11:00:00','Completado'),
(9002,9002,9002,'ASIGNACION','Entrega ficticia',9002,9002,9002,9002,'2025-01-03 09:00:00','2025-01-03 09:30:00','Recibido'),
(9003,9003,9003,'PRESTAMO','Préstamo de demostración',9003,9003,9003,9003,'2025-01-04 09:00:00',NULL,'Pendiente devolución'),
(9004,9004,9004,'MANTENIMIENTO','Ingreso a revisión',9004,9004,9004,9004,'2025-01-05 09:00:00',NULL,'En proceso'),
(9005,9005,9005,'TRASLADO','Movimiento de prueba',9005,9006,9005,9006,'2025-01-06 09:00:00','2025-01-06 11:00:00','Completado'),
(9006,9006,9006,'INVENTARIO','Verificación física',9006,9006,9006,9006,'2025-01-07 09:00:00','2025-01-07 09:15:00','Coincide'),
(9007,9007,9007,'ASIGNACION','Entrega ficticia',9007,9007,9007,9007,'2025-01-08 09:00:00','2025-01-08 09:30:00','Recibido'),
(9008,9008,9008,'BAJA','Cierre de activo ficticio',9008,NULL,9008,NULL,'2025-01-09 09:00:00',NULL,'Retirado'),
(9009,9009,9009,'TRASLADO','Movimiento parcial de prueba',9009,9001,9009,9001,'2025-01-10 09:00:00',NULL,'En tránsito'),
(9010,9010,9010,'REVISION','Control de calidad ficticio',9010,9010,9010,9010,'2025-01-11 09:00:00','2025-01-11 10:00:00','Revisado');

INSERT INTO prestamo (id,activo_id,responsable_id,destino,fecha_salida,fecha_limite,condiciones_retorno,estado) VALUES
(9001,9003,9004,'Sala de presentaciones de prueba','2026-01-01 09:00:00','2026-01-03 17:00:00','Devolver con estuche','EN_PRESTAMO'),
(9002,9001,9003,'Aula ficticia 2','2025-01-01 09:00:00','2025-01-02 17:00:00','Sin daños','DEVUELTO'),
(9003,9002,9005,'Sala ficticia 3','2025-02-01 09:00:00','2025-02-02 17:00:00','Limpio','DEVUELTO'),
(9004,9005,9006,'Laboratorio ficticio 4','2026-02-01 09:00:00','2026-02-02 17:00:00','Empaque original','VENCIDO'),
(9005,9006,9007,'Oficina ficticia 5','2026-03-01 09:00:00','2026-03-04 17:00:00','Revisar cable','EN_PRESTAMO'),
(9006,9007,9008,'Taller ficticio 6','2025-03-01 09:00:00','2025-03-02 17:00:00','Completo','DEVUELTO'),
(9007,9009,9010,'Evento de prueba 7','2026-04-01 09:00:00','2026-04-03 17:00:00','Cargar batería','EN_PRESTAMO'),
(9008,9004,9002,'Laboratorio ficticio 8','2025-04-01 09:00:00','2025-04-02 17:00:00','Conservar calibración','DEVUELTO'),
(9009,9001,9005,'Sala ficticia 9','2025-05-01 09:00:00','2025-05-02 17:00:00','Ninguna','DEVUELTO'),
(9010,9002,9006,'Aula ficticia 10','2026-05-01 09:00:00','2026-05-02 17:00:00','Revisar patas','EN_PRESTAMO');

INSERT INTO novedad (id,activo_id,reportante_id,tipo,descripcion,fecha,estado) VALUES
(9001,9001,9002,'DESGASTE','Desgaste leve ficticio','2026-01-02 09:00:00','ABIERTA'),
(9002,9002,9003,'DANO','Rueda floja en silla ficticia','2026-01-03 09:00:00','EN_REVISION'),
(9003,9003,9004,'OTRA','Control remoto extraviado en prueba','2026-01-04 09:00:00','CERRADA'),
(9004,9004,9005,'DANO','Ajuste óptico requerido','2026-01-05 09:00:00','EN_MANTENIMIENTO'),
(9005,9005,9006,'FALLA','Puerto de red intermitente ficticio','2026-01-06 09:00:00','ABIERTA'),
(9006,9006,9007,'OTRA','Atasco ocasional de prueba','2026-01-07 09:00:00','CERRADA'),
(9007,9007,9008,'DESGASTE','Empuñadura gastada ficticia','2026-01-08 09:00:00','ABIERTA'),
(9008,9008,9009,'BAJA','Equipo retirado para prueba','2026-01-09 09:00:00','CERRADA'),
(9009,9009,9010,'FALLA','Batería con poca autonomía ficticia','2026-01-10 09:00:00','EN_REVISION'),
(9010,9010,9002,'OTRA','Observación de control ficticia','2026-01-11 09:00:00','CERRADA');

INSERT INTO mantenimiento (id,activo_id,diagnostico,acciones,tecnico_proveedor,fecha_inicio,fecha_fin,resultado,estado) VALUES
(9001,9004,'Desajuste ficticio','Ajuste y prueba','Técnico de prueba A','2026-01-01 09:00:00',NULL,NULL,'EN_MANTENIMIENTO'),
(9002,9001,'Limpieza preventiva','Limpieza interna','Técnico de prueba B','2025-01-01 09:00:00','2025-01-02 09:00:00','Operativo','FINALIZADO'),
(9003,9002,'Revisión de uniones','Apriete','Técnico de prueba C','2025-02-01 09:00:00','2025-02-01 12:00:00','Operativo','FINALIZADO'),
(9004,9003,'Reemplazo de control','Sustitución de accesorio','Técnico de prueba D','2025-03-01 09:00:00','2025-03-02 09:00:00','Operativo','FINALIZADO'),
(9005,9005,'Diagnóstico de red','Prueba de puertos','Técnico de prueba E','2026-02-01 09:00:00',NULL,NULL,'EN_MANTENIMIENTO'),
(9006,9006,'Limpieza de rodillos','Limpieza','Técnico de prueba F','2025-04-01 09:00:00','2025-04-01 12:00:00','Operativa','FINALIZADO'),
(9007,9007,'Inspección de seguridad','Ajuste','Técnico de prueba G','2025-05-01 09:00:00','2025-05-01 11:00:00','Apta','FINALIZADO'),
(9008,9008,'Evaluación para retiro','Inspección','Técnico de prueba H','2025-06-01 09:00:00','2025-06-02 09:00:00','No reparable ficticio','CERRADO'),
(9009,9009,'Prueba de batería','Carga y medición','Técnico de prueba I','2026-03-01 09:00:00',NULL,NULL,'PENDIENTE'),
(9010,9010,'Revisión histórica','Inspección visual','Técnico de prueba J','2025-07-01 09:00:00','2025-07-01 10:00:00','Sin hallazgos','FINALIZADO');

INSERT INTO devolucion (id,asignacion_id,prestamo_id,fecha,recibido_por,estado_fisico,destino_posterior,excepcion_justificada) VALUES
(9001,NULL,9002,'2025-01-02 16:00:00',9001,'BUENO','UBICACION 9001',NULL),
(9002,NULL,9003,'2025-02-02 16:00:00',9001,'BUENO','UBICACION 9002',NULL),
(9003,NULL,9006,'2025-03-02 16:00:00',9001,'OBSERVACION','Taller de prueba','Accesorio reportado'),
(9004,NULL,9008,'2025-04-02 16:00:00',9001,'BUENO','UBICACION 9004',NULL),
(9005,NULL,9009,'2025-05-02 16:00:00',9001,'BUENO','UBICACION 9001',NULL),
(9006,9002,NULL,'2024-12-10 09:00:00',9001,'BUENO','UBICACION 9002',NULL),
(9007,9004,NULL,'2024-08-10 09:00:00',9001,'BUENO','UBICACION 9004',NULL),
(9008,9006,NULL,'2024-09-10 09:00:00',9001,'OBSERVACION','Revisión técnica','Desgaste esperado'),
(9009,9008,NULL,'2024-10-10 09:00:00',9001,'REGULAR','Ubicación temporal','Cierre antes de baja'),
(9010,9010,NULL,'2024-11-10 09:00:00',9001,'BUENO','UBICACION 9010',NULL);

INSERT INTO baja_activo (id,activo_id,causal,solicitud,estado,fecha_solicitud,fecha_cierre) VALUES
(9001,9008,'Obsolescencia ficticia','Solicitud de baja de prueba','APROBADA','2025-01-01 09:00:00','2025-02-01 09:00:00'),
(9002,9007,'Desgaste ficticio','Evaluación de baja de prueba','EN_TRAMITE','2026-01-02 09:00:00',NULL),
(9003,9006,'Daño no reparable ficticio','Solicitud ficticia 3','RECHAZADA','2025-03-01 09:00:00','2025-03-15 09:00:00'),
(9004,9005,'Renovación tecnológica ficticia','Solicitud ficticia 4','EN_TRAMITE','2026-01-04 09:00:00',NULL),
(9005,9004,'Fin de vida útil ficticio','Solicitud ficticia 5','APROBADA','2025-05-01 09:00:00','2025-06-01 09:00:00'),
(9006,9003,'Evaluación administrativa ficticia','Solicitud ficticia 6','EN_TRAMITE','2026-01-06 09:00:00',NULL),
(9007,9002,'Reemplazo planificado ficticio','Solicitud ficticia 7','RECHAZADA','2025-07-01 09:00:00','2025-07-10 09:00:00'),
(9008,9001,'Cambio de tecnología ficticio','Solicitud ficticia 8','EN_TRAMITE','2026-01-08 09:00:00',NULL),
(9009,9009,'Obsolescencia ficticia','Solicitud ficticia 9','APROBADA','2025-09-01 09:00:00','2025-10-01 09:00:00'),
(9010,9010,'Retiro de registro de prueba','Solicitud ficticia 10','EN_TRAMITE','2026-01-10 09:00:00',NULL);

INSERT INTO acta (id,tipo,numero,fecha,version,documento_id,operacion_tipo,operacion_id) VALUES
(9001,'ASIGNACION','ACT-PR-9001','2025-01-10',1,9001,'ASIGNACION',9001),
(9002,'TRASLADO','ACT-PR-9002','2025-02-10',1,9002,'TRASLADO',9002),
(9003,'PRESTAMO','ACT-PR-9003','2025-03-10',1,9003,'PRESTAMO',9001),
(9004,'DEVOLUCION','ACT-PR-9004','2025-04-10',1,9004,'DEVOLUCION',9001),
(9005,'BAJA','ACT-PR-9005','2025-05-10',1,9005,'BAJA',9001),
(9006,'ASIGNACION','ACT-PR-9006','2025-06-10',1,9006,'ASIGNACION',9002),
(9007,'TRASLADO','ACT-PR-9007','2025-07-10',1,9007,'TRASLADO',9007),
(9008,'PRESTAMO','ACT-PR-9008','2025-08-10',1,9008,'PRESTAMO',9002),
(9009,'DEVOLUCION','ACT-PR-9009','2025-09-10',1,9009,'DEVOLUCION',9006),
(9010,'BAJA','ACT-PR-9010','2025-10-10',1,9010,'BAJA',9009);

INSERT INTO acta_participante (acta_id,usuario_id,papel) VALUES
(9001,9001,'ENTREGA'),(9001,9002,'RECIBE'),(9002,9003,'SOLICITA'),(9002,9005,'APRUEBA'),
(9003,9004,'ENTREGA'),(9003,9003,'RECIBE'),(9004,9001,'RECIBE'),(9004,9005,'ENTREGA'),
(9005,9006,'SOLICITA'),(9005,9005,'APRUEBA'),(9006,9001,'ENTREGA'),(9006,9007,'RECIBE'),
(9007,9008,'SOLICITA'),(9007,9006,'APRUEBA'),(9008,9002,'ENTREGA'),(9008,9006,'RECIBE'),
(9009,9001,'RECIBE'),(9009,9009,'ENTREGA'),(9010,9010,'SOLICITA'),(9010,9005,'APRUEBA');

INSERT INTO inventario (id,nombre,alcance,sede_id,dependencia_id,ubicacion_id,categoria_id,responsable_id,fecha_inicio,fecha_fin,estado) VALUES
(9001,'Inventario ficticio Norte','Sede de prueba Norte',9001,NULL,NULL,NULL,9001,'2026-01-01 08:00:00',NULL,'EN_CURSO'),
(9002,'Inventario ficticio laboratorio','Laboratorio Alfa',9001,9002,NULL,NULL,9002,'2025-01-01 08:00:00','2025-01-03 17:00:00','CERRADO'),
(9003,'Inventario ficticio biblioteca','Biblioteca Beta',9002,9003,NULL,NULL,9003,'2026-02-01 08:00:00',NULL,'PLANIFICADO'),
(9004,'Inventario ficticio equipos','Categoría cómputo',NULL,NULL,NULL,9001,9004,'2025-02-01 08:00:00','2025-02-02 17:00:00','CERRADO'),
(9005,'Inventario ficticio Centro','Sede Centro',9002,NULL,NULL,NULL,9005,'2026-03-01 08:00:00',NULL,'EN_CONCILIACION'),
(9006,'Inventario ficticio oficinas','Dependencia Gamma',9003,9004,NULL,NULL,9006,'2025-03-01 08:00:00','2025-03-02 17:00:00','CERRADO'),
(9007,'Inventario ficticio taller','Ubicación taller',9008,9009,9009,NULL,9007,'2026-04-01 08:00:00',NULL,'EN_CURSO'),
(9008,'Inventario ficticio audiovisual','Categoría audiovisual',NULL,NULL,NULL,9003,9008,'2025-04-01 08:00:00','2025-04-03 17:00:00','CERRADO'),
(9009,'Inventario ficticio parcial','Sede Valle',9007,NULL,NULL,NULL,9009,'2026-05-01 08:00:00',NULL,'PLANIFICADO'),
(9010,'Inventario ficticio archivado','Sede inactiva',9010,NULL,NULL,NULL,9010,'2025-05-01 08:00:00','2025-05-01 17:00:00','CERRADO');

INSERT INTO inventario_detalle (id,inventario_id,activo_id,verificador_id,resultado,placa_observada,ubicacion_observada,condicion_fisica,fecha_verificacion,observacion) VALUES
(9001,9001,9001,9002,'ENCONTRADO','PR-AC-0001','Edificio A - recepción','BUENO','2026-01-02 09:00:00','Coincide'),
(9002,9002,9002,9003,'ENCONTRADO','PR-AC-0002','Laboratorio A-101','BUENO','2025-01-02 09:00:00','Coincide'),
(9003,9003,9003,9004,'DIFERENCIA_UBICACION','PR-AC-0003','Sala ficticia alterna','BUENO','2026-02-02 09:00:00','Ubicación por confirmar'),
(9004,9004,9004,9005,'DANADO','PR-AC-0004','Oficina administrativa 2','REQUIERE_REVISION','2025-02-02 09:00:00','Crear mantenimiento'),
(9005,9005,9005,9006,'NO_ENCONTRADO','PR-AC-0005',NULL,NULL,'2026-03-02 09:00:00','Pendiente búsqueda'),
(9006,9006,9006,9007,'ENCONTRADO','PR-AC-0006','Oficina administrativa 2','REGULAR','2025-03-02 09:00:00','Coincidencia parcial'),
(9007,9007,9007,9008,'SOBRANTE',NULL,'Taller de soporte','BUENO','2026-04-02 09:00:00','Hallazgo sin placa registrada'),
(9008,9008,9008,9009,'ENCONTRADO','PR-AC-0008','Archivo central - módulo 4','BAJA','2025-04-02 09:00:00','Baja en historial'),
(9009,9009,9009,9010,'ENCONTRADO','PR-AC-0009','Radio de prueba','BUENO','2026-05-02 09:00:00','Coincide'),
(9010,9010,9010,9002,'ENCONTRADO','PR-AC-0010','Centro de prueba cerrado','REGULAR','2025-05-01 10:00:00','Registro archivado');

INSERT INTO diferencia_inventario (id,detalle_id,descripcion,decision,responsable_id,fecha,estado) VALUES
(9001,9003,'Ubicación observada distinta','Actualizar ubicación tras validación',9001,'2026-02-03 09:00:00','EN_INVESTIGACION'),
(9002,9004,'Condición física requiere revisión','Crear orden de mantenimiento',9006,'2025-02-03 09:00:00','RESUELTA'),
(9003,9005,'Activo no localizado','Continuar búsqueda',9005,'2026-03-03 09:00:00','PENDIENTE'),
(9004,9007,'Hallazgo sin placa','Solicitar identificación institucional',9001,'2026-04-03 09:00:00','EN_INVESTIGACION'),
(9005,9008,'Estado físico de activo dado de baja','Verificar expediente',9007,'2025-04-03 09:00:00','RESUELTA'),
(9006,9001,'Diferencia menor de descripción','Corregir descripción validada',9002,'2026-01-03 09:00:00','CERRADA'),
(9007,9002,'Accesorio no listado','Agregar observación al detalle',9003,'2025-01-03 09:00:00','CERRADA'),
(9008,9006,'Condición regular','Programar revisión',9006,'2025-03-03 09:00:00','PENDIENTE'),
(9009,9009,'Verificación de ubicación','Confirmar con responsable',9009,'2026-05-03 09:00:00','EN_INVESTIGACION'),
(9010,9010,'Registro en sede inactiva','Revisar alcance de jornada',9010,'2025-05-02 09:00:00','RESUELTA');

INSERT INTO auditoria (id,usuario_id,entidad,entidad_id,accion,motivo,antes,despues,fecha_hora) VALUES
(9001,9001,'activo',9001,'CREAR','Alta ficticia',NULL,JSON_OBJECT('placa','PR-AC-0001'),'2024-01-10 10:00:00'),
(9002,9002,'activo',9002,'ACTUALIZAR','Cambio ficticio',JSON_OBJECT('estado','REGISTRADO'),JSON_OBJECT('estado','DISPONIBLE'),'2024-02-10 10:00:00'),
(9003,9003,'asignacion',9001,'CONFIRMAR','Aceptación ficticia',NULL,JSON_OBJECT('estado','CONFIRMADA'),'2025-01-10 10:00:00'),
(9004,9004,'traslado',9002,'APROBAR','Aprobación ficticia',NULL,JSON_OBJECT('estado','AUTORIZADO'),'2025-02-10 10:00:00'),
(9005,9005,'prestamo',9001,'CREAR','Préstamo ficticio',NULL,JSON_OBJECT('estado','EN_PRESTAMO'),'2026-01-01 09:00:00'),
(9006,9006,'inventario',9001,'INICIAR','Jornada ficticia',NULL,JSON_OBJECT('estado','EN_CURSO'),'2026-01-01 08:00:00'),
(9007,9007,'mantenimiento',9001,'CERRAR','Servicio ficticio',JSON_OBJECT('estado','EN_MANTENIMIENTO'),JSON_OBJECT('estado','FINALIZADO'),'2025-01-02 09:00:00'),
(9008,9008,'activo',9008,'DAR_BAJA','Obsolescencia ficticia',JSON_OBJECT('estado','ASIGNADO'),JSON_OBJECT('estado','DADO_DE_BAJA'),'2025-02-01 09:00:00'),
(9009,9009,'inventario_detalle',9007,'REGISTRAR_SOBRANTE','Hallazgo ficticio',NULL,JSON_OBJECT('resultado','SOBRANTE'),'2026-04-02 09:00:00'),
(9010,NULL,'sistema',NULL,'RESPALDO','Evento automático ficticio',NULL,NULL,'2026-05-01 00:00:00');

INSERT INTO notificacion (id,usuario_id,tipo,fecha,estado,referencia_tipo,referencia_id) VALUES
(9001,9001,'APROBACION_PENDIENTE','2026-01-20 09:00:00','PENDIENTE','TRASLADO',9001),
(9002,9002,'TRASLADO_AUTORIZADO','2026-01-21 09:00:00','LEIDA','TRASLADO',9002),
(9003,9003,'PRESTAMO_POR_VENCER','2026-01-02 09:00:00','PENDIENTE','PRESTAMO',9001),
(9004,9004,'MANTENIMIENTO_ACTUALIZADO','2026-01-05 09:00:00','LEIDA','MANTENIMIENTO',9001),
(9005,9005,'DIFERENCIA_INVENTARIO','2026-02-03 09:00:00','PENDIENTE','DIFERENCIA',9001),
(9006,9006,'BAJA_PENDIENTE','2026-01-04 09:00:00','ARCHIVADA','BAJA',9004),
(9007,9007,'ACTA_DISPONIBLE','2026-01-06 09:00:00','LEIDA','ACTA',9001),
(9008,9008,'IMPORTACION_FINALIZADA','2026-01-07 09:00:00','LEIDA','IMPORTACION',9001),
(9009,9009,'ASIGNACION_OBSERVADA','2026-01-08 09:00:00','PENDIENTE','ASIGNACION',9009),
(9010,9010,'JORNADA_INVENTARIO','2026-01-09 09:00:00','PENDIENTE','INVENTARIO',9010);

INSERT INTO importacion (id,nombre_archivo,usuario_id,fecha,registros_aceptados,registros_rechazados,estado) VALUES
(9001,'inventario_demo_01.csv',9008,'2026-01-01 09:00:00',10,0,'COMPLETADA'),
(9002,'inventario_demo_02.csv',9008,'2026-01-02 09:00:00',8,2,'COMPLETADA'),
(9003,'inventario_demo_03.csv',9008,'2026-01-03 09:00:00',0,10,'RECHAZADA'),
(9004,'inventario_demo_04.csv',9001,'2026-01-04 09:00:00',4,1,'COMPLETADA'),
(9005,'inventario_demo_05.csv',9002,'2026-01-05 09:00:00',5,0,'VALIDANDO'),
(9006,'inventario_demo_06.csv',9003,'2026-01-06 09:00:00',7,3,'COMPLETADA'),
(9007,'inventario_demo_07.csv',9004,'2026-01-07 09:00:00',2,8,'FALLIDA'),
(9008,'inventario_demo_08.csv',9005,'2026-01-08 09:00:00',9,1,'COMPLETADA'),
(9009,'inventario_demo_09.csv',9006,'2026-01-09 09:00:00',3,2,'COMPLETADA'),
(9010,'inventario_demo_10.csv',9007,'2026-01-10 09:00:00',1,0,'VALIDANDO');

INSERT INTO detalle_importacion (id,importacion_id,numero_fila,resultado,error,posible_duplicado_id) VALUES
(9001,9001,1,'ACEPTADO',NULL,NULL),(9002,9002,2,'RECHAZADO','Placa duplicada ficticia',9001),
(9003,9003,3,'RECHAZADO','Categoría no reconocida en prueba',NULL),(9004,9004,4,'ACEPTADO',NULL,NULL),
(9005,9005,5,'ACEPTADO',NULL,NULL),(9006,9006,6,'RECHAZADO','Campo obligatorio ausente',NULL),
(9007,9007,7,'RECHAZADO','Fecha con formato inválido',NULL),(9008,9008,8,'ACEPTADO',NULL,NULL),
(9009,9009,9,'ACEPTADO',NULL,NULL),(9010,9010,10,'RECHAZADO','Posible duplicado',9008);

COMMIT;

-- Verificación de volumen: deben aparecer 10 filas por tabla.
SELECT 'usuario' tabla,COUNT(*) cantidad FROM usuario WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'rol',COUNT(*) FROM rol WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'permiso',COUNT(*) FROM permiso WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'sede',COUNT(*) FROM sede WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'dependencia',COUNT(*) FROM dependencia WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'ubicacion',COUNT(*) FROM ubicacion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'categoria_activo',COUNT(*) FROM categoria_activo WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'categoria_campo',COUNT(*) FROM categoria_campo WHERE categoria_id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'activo',COUNT(*) FROM activo WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'ambito_acceso',COUNT(*) FROM ambito_acceso WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'documento',COUNT(*) FROM documento WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'asignacion',COUNT(*) FROM asignacion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'solicitud_traslado',COUNT(*) FROM solicitud_traslado WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'aprobacion',COUNT(*) FROM aprobacion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'movimiento_activo',COUNT(*) FROM movimiento_activo WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'prestamo',COUNT(*) FROM prestamo WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'novedad',COUNT(*) FROM novedad WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'mantenimiento',COUNT(*) FROM mantenimiento WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'devolucion',COUNT(*) FROM devolucion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'baja_activo',COUNT(*) FROM baja_activo WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'acta',COUNT(*) FROM acta WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'acta_participante',COUNT(*) FROM acta_participante WHERE acta_id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'inventario',COUNT(*) FROM inventario WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'inventario_detalle',COUNT(*) FROM inventario_detalle WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'diferencia_inventario',COUNT(*) FROM diferencia_inventario WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'auditoria',COUNT(*) FROM auditoria WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'notificacion',COUNT(*) FROM notificacion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'importacion',COUNT(*) FROM importacion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'detalle_importacion',COUNT(*) FROM detalle_importacion WHERE id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'usuario_rol',COUNT(*) FROM usuario_rol WHERE usuario_id BETWEEN 9001 AND 9999 UNION ALL
SELECT 'rol_permiso',COUNT(*) FROM rol_permiso WHERE rol_id BETWEEN 9001 AND 9999
ORDER BY tabla;

-- Integridad: ambas consultas deben devolver cero.
SELECT 'activos sin categoria/ubicacion/dependencia' prueba,COUNT(*) errores
FROM activo a LEFT JOIN categoria_activo c ON c.id=a.categoria_id
LEFT JOIN ubicacion u ON u.id=a.ubicacion_id LEFT JOIN dependencia d ON d.id=a.dependencia_id
WHERE a.id BETWEEN 9001 AND 9999 AND (c.id IS NULL OR u.id IS NULL OR d.id IS NULL);

SELECT 'asignaciones vigentes duplicadas' prueba,COUNT(*) errores
FROM (SELECT activo_id FROM asignacion WHERE fecha_fin IS NULL AND id BETWEEN 9001 AND 9999 GROUP BY activo_id HAVING COUNT(*)>1) x;

-- JOIN representativo: ficha del activo, ubicación, sede, dependencia y custodia vigente.
SELECT a.placa,a.descripcion,a.estado,c.nombre categoria,s.nombre sede,d.nombre dependencia,
       u.nombre ubicacion,us.nombre custodio
FROM activo a JOIN categoria_activo c ON c.id=a.categoria_id
JOIN ubicacion u ON u.id=a.ubicacion_id JOIN sede s ON s.id=u.sede_id
JOIN dependencia d ON d.id=a.dependencia_id
LEFT JOIN asignacion ag ON ag.activo_id=a.id AND ag.fecha_fin IS NULL
LEFT JOIN usuario us ON us.id=ag.custodio_id
WHERE a.id BETWEEN 9001 AND 9999 ORDER BY a.id;

-- JOIN de préstamos con devoluciones (incluye préstamos aún abiertos).
SELECT p.id,p.estado,p.fecha_salida,p.fecha_limite,d.fecha fecha_devolucion,d.estado_fisico
FROM prestamo p LEFT JOIN devolucion d ON d.prestamo_id=p.id
WHERE p.id BETWEEN 9001 AND 9999 ORDER BY p.id;

-- JOIN de inventario y hallazgos, incluyendo sobrantes no registrados.
SELECT i.nombre inventario,det.resultado,det.placa_observada,a.placa placa_catalogo,di.estado estado_diferencia
FROM inventario_detalle det JOIN inventario i ON i.id=det.inventario_id
LEFT JOIN activo a ON a.id=det.activo_id
LEFT JOIN diferencia_inventario di ON di.detalle_id=det.id
WHERE det.id BETWEEN 9001 AND 9999 ORDER BY det.id;
