# Alcance revisado y propuesta de modelo de datos

## Estado y fuente

Se revisaron las seis hojas del Excel de requisitos. Este documento consolida el alcance funcional y una propuesta de modelo relacional para orientar la implementación. Los RF-001 a RF-028 aparecen como obligatorios en la fuente; varias reglas y decisiones siguen pendientes de validación institucional. El modelo queda sujeto a aprobación de CECAR.

La propuesta no implica que el esquema SQL haya sido generado, validado contra una instancia ni ejecutado. `database/base/01_schema.sql` es un artefacto existente del proyecto y debe revisarse frente a este documento antes de considerarlo aprobado.

## Convenciones

- Motor previsto: MySQL 8.0 o superior, InnoDB y `utf8mb4`.
- Identificadores propuestos: `BIGINT UNSIGNED`.
- `PK`: clave primaria; `FK`: clave foránea; `UQ`: único; `NN`: obligatorio.
- Los nombres y tipos son una base de conversación y pueden acordarse antes de consolidar el esquema.

## Modelo relacional propuesto

### Acceso, roles y ámbitos

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `usuario` | `id` PK; `identificacion VARCHAR(30)` NN UQ; `nombre VARCHAR(150)` NN; `correo VARCHAR(150)` NN UQ; `password_hash VARCHAR(255)` NN; `estado VARCHAR(20)` NN | RF-001–003, RNF-002, Modelo de datos |
| `rol` | `id` PK; `nombre VARCHAR(80)` NN UQ; `descripcion VARCHAR(255)`; `estado VARCHAR(20)` NN | RF-002, roles del Resumen |
| `permiso` | `id` PK; `modulo VARCHAR(100)` NN; `recurso VARCHAR(100)` NN; `accion VARCHAR(80)` NN; UQ(`modulo`,`recurso`,`accion`) | RF-002–003, RNF-001 |
| `usuario_rol` | `usuario_id`, `rol_id` PK compuesta y FK; `fecha_asignacion DATETIME` NN; `asignado_por` FK a usuario | RF-002; relación M:N |
| `rol_permiso` | `rol_id`, `permiso_id` PK compuesta y FK | RF-002–003 |
| `ambito_acceso` | `id` PK; `usuario_id` FK NN; tipo `GLOBAL`, `SEDE`, `DEPENDENCIA`, `UBICACION` o `CUSTODIA`; vigencia; FKs opcionales a sede, dependencia, ubicación y activo | RF-003, RN-007, RNF-001, RNF-011 |

Para conservar integridad referencial, se proponen FKs tipadas y un `CHECK` que permita solo las columnas correspondientes al tipo de ámbito. `CUSTODIA` se resolvería mediante las asignaciones vigentes, sin guardar un identificador de activo arbitrario.

### Estructura institucional y ficha del activo

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `sede` | `id` PK; `codigo VARCHAR(30)` NN UQ; `nombre VARCHAR(150)` NN; `direccion VARCHAR(255)`; `estado VARCHAR(20)` NN | RF-004, RF-006, RNF-010 |
| `dependencia` | `id` PK; `sede_id` FK opcional; `codigo VARCHAR(30)` NN; `nombre VARCHAR(150)` NN; `tipo VARCHAR(80)`; `dependencia_padre_id` FK a sí misma; `estado`; unicidad de código por confirmar | RF-005, RN-002 |
| `ubicacion` | `id` PK; `sede_id` FK NN; `dependencia_id` FK opcional; `codigo VARCHAR(30)` NN; `nombre VARCHAR(150)` NN; `tipo VARCHAR(80)`; `ubicacion_padre_id` FK a sí misma; `estado`; UQ(`sede_id`,`codigo`) | RF-006, AC-008, RN-002 |
| `categoria_activo` | `id` PK; `codigo VARCHAR(30)` NN UQ; `nombre VARCHAR(120)` NN; `estado` | RF-007–008, Modelo de datos |
| `categoria_campo` | `categoria_id` FK y `campo_codigo VARCHAR(50)` PK compuesta; `obligatorio BOOLEAN` NN | RF-007–008, AC-004; procede si los campos obligatorios se configuran por categoría |
| `activo` | `id` PK; `placa VARCHAR(50)` NN UQ; `fecha_asignacion_placa DATE`; `serial VARCHAR(100)` opcional; `descripcion TEXT` NN; `categoria_id` FK NN; `marca VARCHAR(100)` y `modelo VARCHAR(100)` opcionales; `estado VARCHAR(30)` NN; `ubicacion_id` FK NN; `dependencia_id` FK NN | RF-007–009, RN-001–002, RNF-004, AC-001–009 |

La sede se obtiene desde la ubicación para evitar duplicarla en `activo`. La dependencia se conserva porque RN-002 establece que dependencia y ubicación no son equivalentes. La placa debe seguir siendo única incluso después de la baja, conforme a RN-001/RNF-004.

`categoria_campo` normaliza los campos que dependen de la categoría. Solo debe incluirse si se confirma que el catálogo determina qué campos son obligatorios; los códigos permitidos requieren validación.

### Asignaciones, movimientos y operaciones

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `asignacion` | `id` PK; `activo_id` FK NN; `custodio_id` FK opcional; `dependencia_id` y `ubicacion_id` FKs NN; `fecha_inicio DATETIME` NN; `fecha_fin DATETIME` opcional; `estado VARCHAR(20)` NN; `entregado_por` FK usuario; fecha de aceptación y observación | RF-010–013, RN-003–004, RN-014 |
| `solicitud_traslado` | `id` PK; `activo_id` FK NN; origen y destino como FKs a ubicación y dependencia; `motivo TEXT` NN; `solicitante_id` FK; `fecha_propuesta DATETIME`; `estado VARCHAR(30)` NN | RF-014–015, RN-005–006 |
| `movimiento_activo` | `id` PK; `activo_id` y `usuario_id` FKs NN; tipo y motivo; origen y destino de ubicación y dependencia como FKs; fechas de salida y recepción; observaciones | RF-009, RF-016, AC-012, AC-020, RN-005–006 |
| `aprobacion` | `id` PK; operación aprobada; `aprobador_id` FK NN; decisión, fecha y observación | RF-015, RF-023–024, RN-008 |

Cada activo tendría como máximo una asignación vigente. El historial terminado se conserva. Se debe definir cómo aplicar esa unicidad con MySQL y cómo impedir que la misma persona registre y apruebe una operación. También falta decidir si una operación admite varias aprobaciones o etapas.

### Préstamos, devoluciones, novedades y mantenimiento

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `prestamo` | `id` PK; `activo_id` y `responsable_id` FKs NN; destino, `fecha_salida`, `fecha_limite`, condiciones de retorno y `estado` | RF-017–018, RN-010 |
| `devolucion` | `id` PK; FK a asignación o préstamo (exactamente una); fecha, `recibido_por` FK, estado físico, destino posterior y excepción justificada | RF-013, RF-018, RN-014 |
| `novedad` | `id` PK; `activo_id` y `reportante_id` FKs NN; tipo, descripción, fecha y estado | RF-019, AC-014 |
| `mantenimiento` | `id` PK; `activo_id` FK NN; diagnóstico, acciones, técnico/proveedor como referencia de texto, fechas, resultado y estado | RF-020, RN-011, AC-015 |

La fecha de devolución del préstamo se obtiene del registro en `devolucion`, evitando duplicarla en `prestamo`. Para devoluciones de asignación o préstamo, un `CHECK` debe exigir exactamente una FK informada.

### Inventario físico y diferencias

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `inventario` | `id` PK; nombre, alcance, FKs opcionales para sede/dependencia/ubicación/categoría; responsable FK; fechas de inicio/fin y estado | RF-021, AC-016 |
| `inventario_detalle` | `id` PK; `inventario_id` FK NN; `activo_id` FK opcional para hallazgos no registrados; verificador FK; resultado, placa y ubicación observadas, condición física, fecha y observación | RF-022, AC-016 |
| `diferencia_inventario` | `id` PK; `detalle_id` FK NN; descripción, decisión, responsable de investigación, fecha y estado | RF-023, RN-012 |

Se propone UQ(`inventario_id`,`activo_id`) para evitar registrar dos veces el mismo activo esperado en una jornada; `activo_id` nulo permite sobrantes. Falta decidir cómo registrar sobrantes sin placa y ubicaciones observadas que aún no estén catalogadas.

### Bajas, documentos, actas y trazabilidad

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `baja_activo` | `id` PK; `activo_id` FK NN; causal, solicitud, estado y fechas; solicitud/acta de soporte según relación aprobada | RF-024, RN-013, AC-017 |
| `documento` | `id` PK; nombre original, tipo, tamaño, versión, fecha, responsable FK y ruta privada | RF-008, RF-019, RF-026, RNF-008, AC-005/010/018 |
| `acta` | `id` PK; tipo, número, fecha, versión y documento FK; relacionada con asignación, traslado, préstamo, devolución o baja | RF-011, RF-016, RF-018, RF-026, RN-004/009 |
| `acta_participante` | `acta_id` y `usuario_id` PK compuesta y FKs; papel del participante | RF-026 |
| `auditoria` | `id` PK; `usuario_id` FK opcional; entidad, id de entidad, acción, motivo, `antes JSON`, `despues JSON`, `fecha_hora` | RF-009, RF-027, RN-009/015, RNF-003 |

Los JSON de auditoría son instantáneas históricas y no reemplazan las columnas normalizadas. La bitácora debe ser de solo inserción para perfiles operativos; las referencias históricas se conservan mediante bajas lógicas.

### Notificaciones e importación

| Tabla | Columnas y restricciones principales | Trazabilidad |
|---|---|---|
| `notificacion` | `id` PK; `usuario_id` FK NN; tipo, fecha, estado y referencia al evento | RF-017, RF-025, AC-013 |
| `importacion` | `id` PK; nombre del archivo, `usuario_id` FK, fecha, registros aceptados/rechazados y estado | RF-028 |
| `detalle_importacion` | `id` PK; `importacion_id` FK NN; número de fila, resultado, error y referencia a posible duplicado | RF-028 |

La referencia de una notificación puede apuntar a distintos eventos. Si se exige FK estricta, se deben usar referencias tipadas en lugar de una referencia genérica.

## Relaciones y cardinalidades

- Un usuario puede tener varios roles y cada rol varios usuarios: **M:N** mediante `usuario_rol`.
- Un rol puede tener varios permisos y un permiso pertenecer a varios roles: **M:N** mediante `rol_permiso`.
- Una sede contiene ubicaciones y puede contener dependencias; una dependencia puede tener una dependencia padre: **1:N**.
- Una ubicación pertenece a una sede y puede tener una ubicación padre: **1:N** recursiva.
- Una categoría clasifica muchos activos: **1:N**. Si se aprueba `categoria_campo`, categoría y campo quedan en **1:N**.
- Un activo conserva asignaciones y movimientos históricos: **1:N**; se limita a una asignación vigente.
- Una solicitud de traslado puede tener varias aprobaciones y generar movimientos: **1:N**, sujeto a confirmar el flujo.
- Un activo puede tener préstamos, novedades y mantenimientos: **1:N**.
- Una jornada de inventario tiene muchos detalles; cada detalle puede originar diferencias: **1:N**.
- Una importación tiene muchas filas de resultado: **1:N**.
- Un acta puede tener varios participantes: **M:N** mediante `acta_participante`.

## Índices y borrado

Además de PK y UQ, se proponen índices para estado y placa del activo; asignaciones por activo, custodio y vigencia; solicitudes y movimientos por estado, activo y fecha; préstamos por estado y fecha límite; inventarios por estado y alcance; auditoría por entidad/id y fecha; e importaciones por fecha y estado. Las FKs ya suelen recibir índices en InnoDB; se revisarán los planes de consulta antes de añadir índices redundantes.

Se propone `ON DELETE RESTRICT` para datos maestros e historial. Sedes, dependencias, ubicaciones, usuarios, activos y operaciones se inactivan o cierran; no se borran físicamente. Las relaciones y bitácoras se retiran mediante acciones trazables. No se recomienda `CASCADE` para movimientos, actas, auditoría ni inventarios.

## Trazabilidad y prioridad

Los requisitos funcionales RF-001–RF-028 se cubren en las tablas indicadas. En especial:

- Seguridad y permisos: RF-001–003; RNF-001–002; RN-007–008.
- Estructura y activos: RF-004–009; RN-001–002; RNF-004/010.
- Custodia y operaciones: RF-010–020; RN-003–011/014.
- Inventarios y bajas: RF-021–024; RN-012–013.
- Informes, documentos, auditoría e importación: RF-025–028; RN-015–016; RNF-003/008/013.

El Excel marca los RF-001–028 como **Obligatorios**. La prioridad técnica propuesta es construir primero seguridad, estructura, activos y asignación, que son dependencias de las demás operaciones; luego movimientos, préstamos, inventarios, bajas, informes e importación. Esto ordena la implementación, pero no cambia la prioridad funcional del Excel.

## Decisiones pendientes de aprobación

1. **Asignaciones y ubicación:** ¿todo activo debe tener siempre sede, dependencia, ubicación y custodio, o se permiten activos disponibles sin custodio? ¿Se admite un custodio principal único para bienes compartidos?
2. **Aprobaciones:** ¿qué operaciones requieren aprobación previa, cuántas aprobaciones puede tener cada operación y puede aprobarla quien la registra?
3. **Documentos:** ¿qué formatos y tamaños se aceptan, qué operaciones admiten varios soportes y dónde se almacenan de forma privada?
4. **Catálogos:** ¿cuáles son los estados definitivos de activo y operación? ¿La unicidad del serial se exige para todas las categorías o solo algunas? ¿La placa anterior debe seguir consultable al cambiarla?
5. **Ámbitos y notificaciones:** ¿qué ámbitos corresponden a cada rol y qué eventos/plazos generan alertas?
6. **Base de datos:** ¿el nombre objetivo es `gestion_activos` (usado por el esquema SQL existente) o `proyecto` (configurado en `config/database.php`)?

## Nota para implementación SQL

`database/base/01_schema.sql` contiene instrucciones `DROP TABLE`, por lo que no debe ejecutarse sobre una base con datos que se quieran conservar. Tras aprobar este modelo, debe prepararse un script de creación reproducible para una base nueva, revisarse y probarse de forma controlada antes de cualquier ejecución local.
