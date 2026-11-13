# Endpoints API implementados

## Alcance

Prefijo: `/api/v1`. Las rutas devuelven JSON salvo respuestas `204`. Se propone autenticación por sesión PHP con cookie `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS. Las operaciones mutables requieren token CSRF. Los nombres de permisos de la tabla son sugeridos y deben relacionarse con los roles institucionales.

Los endpoints reflejan el alcance RF-001–RF-028. Las rutas de archivos guardan metadata y archivos fuera de `public/`; la tabla `documento` no tiene una FK polimórfica hacia activos, novedades o mantenimientos, así que la asociación automática a esas entidades queda pendiente de una decisión/modelo de datos. Los archivos se vinculan mediante `acta.documento_id` cuando se crea el acta.

## Formato de respuesta y errores

- Éxito con datos: `{"data": ...}`; creación: `201` con `{"data":{"id": ...}}`; lista: `200` con `{"data":[...]}`.
- Error: `{"error":{"code":"...","message":"..."}}`. Nunca incluir SQL, trazas ni credenciales.
- `400` entrada inválida; `401` sesión ausente o inválida; `403` permiso insuficiente; `404` recurso inexistente; `405` método no permitido; `409` conflicto de estado/unicidad; `500` error interno genérico.
- Validación de payload fallida usa `400`; referencias inexistentes `404`; reglas de estado/duplicados `409`.

## Matriz

Para las devoluciones, el usuario que recibe queda registrado como el usuario autenticado; los endpoints no aceptan ecibidoPor en el payload.


| Método | Ruta | Controlador | Parámetros/payload | Éxito | Errores previstos | Requisito |
|---|---|---|---|---|---|---|
| `POST` | `/sesion` | `SesionController::crear` | `{correo, contrasena}` | `200` usuario público | `400, 401, 405, 500` | RF-001 |
| `GET` | `/sesion` | `SesionController::mostrar` | Cookie de sesión | `200` usuario y permisos | `401, 405, 500` | RF-001–003 |
| `DELETE` | `/sesion` | `SesionController::cerrar` | Cookie y CSRF | `204` | `401, 403, 405, 500` | RF-001 |
| `GET` | `/usuarios` | `UsuarioController::listar` | `?estado=&q=&limite=` | `200` lista paginada | `400, 401, 403, 405, 500` | RF-002–003 |
| `POST` | `/usuarios` | `UsuarioController::crear` | `{identificacion,nombre,correo,contrasena}` | `201` ID y perfil público | `400, 401, 403, 409, 500` | RF-001–002 |
| `GET` | `/usuarios/{id}` | `UsuarioController::mostrar` | ID entero | `200` perfil público | `400, 401, 403, 404, 405, 500` | RF-001–003 |
| `PATCH` | `/usuarios/{id}/estado` | `UsuarioController::cambiarEstado` | `{estado}` | `200` perfil actualizado | `400, 401, 403, 404, 409, 500` | RF-002 |
| `PUT` | `/usuarios/{id}/roles` | `UsuarioRolController::reemplazar` | `{roles:[id,...]}` | `200` roles asignados | `400, 401, 403, 404, 409, 500` | RF-002 |
| `GET` | `/roles` | `RolController::listar` | `?estado=` | `200` lista de roles | `401, 403, 405, 500` | RF-002–003 |
| `GET` | `/sedes` | `SedeController::listar` | `?estado=` | `200` lista | `400, 401, 403, 405, 500` | RF-004, RF-006 |
| `POST` | `/sedes` | `SedeController::crear` | `{codigo,nombre,direccion?}` | `201` ID | `400, 401, 403, 409, 500` | RF-004 |
| `GET` | `/dependencias` | `DependenciaController::listar` | `?sedeId=` | `200` lista activa | `400, 401, 403, 405, 500` | RF-005 |
| `POST` | `/dependencias` | `DependenciaController::crear` | `{sedeId?,codigo,nombre,tipo?,dependenciaPadreId?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-005 |
| `GET` | `/ubicaciones` | `UbicacionController::listar` | `?sedeId=` | `200` lista activa | `400, 401, 403, 405, 500` | RF-006 |
| `POST` | `/ubicaciones` | `UbicacionController::crear` | `{sedeId,dependenciaId?,codigo,nombre,tipo?,ubicacionPadreId?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-006 |
| `GET` | `/categorias` | `CategoriaActivoController::listar` | `?estado=` | `200` catálogo/campos | `401, 403, 405, 500` | RF-007–008 |
| `GET` | `/activos` | `ActivoController::listar` | `?estado=&categoriaId=&dependenciaId=&limite=` | `200` lista | `400, 401, 403, 405, 500` | RF-007–009, RF-025 |
| `POST` | `/activos` | `ActivoController::crear` | `{placa,fechaAsignacionPlaca?,serial?,descripcion,categoriaId,marca?,modelo?,estado,ubicacionId,dependenciaId}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-007–009 |
| `GET` | `/activos/{id}` | `ActivoController::mostrar` | ID entero | `200` ficha del activo | `400, 401, 403, 404, 405, 500` | RF-007–009, RF-025 |
| `GET` | `/activos/placa/{placa}` | `ActivoController::buscarPorPlaca` | Placa | `200` ficha | `400, 401, 403, 404, 405, 500` | RF-007, RN-001 |
| `PATCH` | `/activos/{id}/estado` | `ActivoController::cambiarEstado` | `{estado,motivo}` | `200` ficha actualizada | `400, 401, 403, 404, 409, 500` | RF-009, RF-024 |
| `GET` | `/activos/{id}/asignacion-vigente` | `AsignacionController::vigente` | ID del activo | `200` asignación o `data:null` | `400, 401, 403, 404, 405, 500` | RF-010–013 |
| `POST` | `/activos/{id}/asignaciones` | `AsignacionController::crear` | `{custodioId?,dependenciaId,ubicacionId,observacion?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-010–012 |
| `POST` | `/asignaciones/{id}/devolucion` | `DevolucionController::desdeAsignacion` | `{estadoFisico,destinoPosterior?,excepcion?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-013, RN-014 |
| `POST` | `/traslados` | `SolicitudTrasladoController::crear` | `{activoId,origenUbicacionId,destinoUbicacionId,origenDependenciaId?,destinoDependenciaId?,motivo,fechaPropuesta?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-014–015 |
| `GET` | `/traslados/{id}` | `SolicitudTrasladoController::mostrar` | ID de solicitud | `200` detalle y aprobaciones | `400, 401, 403, 404, 405, 500` | RF-014–016 |
| `POST` | `/traslados/{id}/decision` | `SolicitudTrasladoController::resolver` | `{decision,observacion?}` | `200` estado | `400, 401, 403, 404, 409, 500` | RF-015, RN-008 |
| `POST` | `/traslados/{id}/completar` | `MovimientoActivoController::completarTraslado` | `{observaciones?}` | `201` movimiento | `400, 401, 403, 404, 409, 500` | RF-016, RN-005–006 |
| `POST` | `/prestamos` | `PrestamoController::crear` | `{activoId,responsableId,destino?,fechaSalida,fechaLimite,condicionesRetorno?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-017–018, RN-010 |
| `GET` | `/prestamos/{id}` | `PrestamoController::mostrar` | ID de préstamo | `200` préstamo/devolución | `400, 401, 403, 404, 405, 500` | RF-017–018 |
| `POST` | `/prestamos/{id}/devolucion` | `DevolucionController::desdePrestamo` | `{estadoFisico,destinoPosterior?,excepcion?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-018, RN-014 |
| `POST` | `/novedades` | `NovedadController::crear` | `{activoId,tipo,descripcion}` | `201` ID | `400, 401, 403, 404, 500` | RF-019 |
| `POST` | `/activos/{id}/mantenimientos` | `MantenimientoController::crear` | `{diagnostico,acciones?,tecnicoProveedor?,fechaInicio}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-020, RN-011 |
| `POST` | `/inventarios` | `InventarioController::crear` | `{nombre,alcance,sedeId?,dependenciaId?,ubicacionId?,categoriaId?,responsableId,fechaInicio}` | `201` ID | `400, 401, 403, 404, 500` | RF-021 |
| `GET` | `/inventarios/{id}` | `InventarioController::mostrar` | ID de inventario | `200` jornada y resumen | `400, 401, 403, 404, 405, 500` | RF-021–023 |
| `POST` | `/inventarios/{id}/iniciar` | `InventarioController::iniciar` | Sin payload | `200` jornada | `401, 403, 404, 409, 500` | RF-021 |
| `POST` | `/inventarios/{id}/detalles` | `InventarioDetalleController::crear` | `{activoId?,verificadorId?,resultado,placaObservada?,ubicacionObservada?,condicionFisica?,observacion?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-022 |
| `POST` | `/inventarios/{id}/detalles/{detalleId}/diferencias` | `DiferenciaInventarioController::crear` | `{descripcion,responsableId?,decision?}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-023, RN-012 |
| `POST` | `/inventarios/{id}/cerrar` | `InventarioController::cerrar` | Sin payload | `200` jornada cerrada | `401, 403, 404, 409, 500` | RF-021–023 |
| `POST` | `/activos/{id}/bajas` | `BajaActivoController::solicitar` | `{causal,solicitud}` | `201` ID | `400, 401, 403, 404, 409, 500` | RF-024, RN-013 |
| `GET` | `/notificaciones` | `NotificacionController::listar` | `?estado=&limite=` | `200` lista del usuario | `400, 401, 403, 405, 500` | RF-017, RF-025 |
| `PATCH` | `/notificaciones/{id}/leida` | `NotificacionController::marcarLeida` | Sin payload | `200` notificación | `401, 403, 404, 409, 500` | RF-025 |
| `GET` | `/reportes/activos` | `ReporteController::activos` | Filtros de sede, dependencia, ubicación, categoría y estado | `200` filas exportables | `400, 401, 403, 405, 500` | RF-025 |
| `GET` | `/procesos-seleccion/unidades` | `ProcesoSeleccionController::unidades` | Sin parámetros | `200` unidades activas | `401, 403, 405, 500` | Extensión del módulo de selección |
| `GET` | `/procesos-seleccion` | `ProcesoSeleccionController::listar` | `?estado=&dependenciaId=&q=` | `200` procesos con conteos de documentos | `400, 401, 403, 405, 500` | Extensión del módulo de selección |
| `POST` | `/procesos-seleccion` | `ProcesoSeleccionController::crear` | `{nombre,dependenciaId,estado?,fechaApertura?,fechaCierre?}` | `201` ID | `400, 401, 403, 404, 409, 500` | Extensión del módulo de selección |
| `PATCH` | `/procesos-seleccion/{id}/estado` | `ProcesoSeleccionController::cambiarEstado` | `{estado}` | `200` estado actualizado | `400, 401, 403, 404, 500` | Extensión del módulo de selección |
| `GET` | `/procesos-seleccion/{id}/postulaciones` | `ProcesoSeleccionController::postulaciones` | ID y `?q=` | `200` postulaciones con conteos de documentos | `401, 403, 404, 500` | Extensión del módulo de selección |
| `POST` | `/procesos-seleccion/{id}/postulaciones` | `ProcesoSeleccionController::crearPostulacion` | `{nombre,correo}`; solo proceso `ABIERTO` | `201` ID | `400, 401, 403, 404, 409, 500` | Extensión del módulo de selección |
| `GET` | `/postulaciones/{postulacionId}/documentos` | `DocumentoPostulacionController::listar` | ID de postulación | `200` documentos y estados | `401, 403, 404, 500` | Extensión del módulo de selección |
| `POST` | `/procesos-seleccion/{id}/postulaciones/{postulacionId}/documentos` | `DocumentoPostulacionController::subir` | `multipart/form-data`: `tipoDocumento`, `archivo` (PDF/PNG/JPEG, hasta 10 MB) | `201` ID y metadata | `400, 401, 403, 404, 413, 415, 500` | Extensión del módulo de selección |
| `PATCH` | `/documentos-postulacion/{id}/revision` | `DocumentoPostulacionController::revisar` | `{estado,observacion?}` | `200` estado de revisión | `400, 401, 403, 404, 500` | Extensión del módulo de selección |
| `GET` | `/documentos-postulacion/{id}` | `DocumentoPostulacionController::descargar` | ID de documento | `200` archivo privado adjunto | `401, 403, 404, 500` | Extensión del módulo de selección |
| `POST` | `/documentos` | `DocumentoController::subir` | `multipart/form-data`: `archivo` (PDF, PNG o JPEG; máximo 10 MB) | `201` metadatos privados | `400, 401, 403, 413, 415, 500` | RF-008, RF-019, RF-026, RNF-008 |
| `GET` | `/documentos/{id}` | `DocumentoController::descargar` | ID de documento | `200` archivo autorizado | `401, 403, 404, 405, 500` | RF-026, RNF-008 |
| `POST` | `/importaciones` | `ImportacionController::crear` | `multipart/form-data`: archivo de inventario | `202` ID y estado de validación | `400, 401, 403, 413, 415, 500` | RF-028 |
| `GET` | `/importaciones/{id}` | `ImportacionController::mostrar` | ID de importación | `200` resumen y errores por fila | `401, 403, 404, 405, 500` | RF-028 |
| `GET` | `/auditoria` | `AuditoriaController::listar` | `?entidad=&entidadId=&limite=` | `200` eventos | `400, 401, 403, 405, 500` | RF-027, RNF-003 |

## Autorización implementada

- Sin sesión: solo `POST /sesion`; el resto requiere `401`.
- La extensión de selección usa `seleccion.proceso.leer` para consultar, `seleccion.proceso.administrar` para crear convocatorias/postulaciones y cargar archivos, y `seleccion.postulacion.revisar` para dictaminar documentos. Debe asignarse solo a personal autorizado; la migración lo concede al rol de pruebas `ADMIN_PRUEBA` únicamente si está presente.
- Lecturas y cambios de catálogos, usuarios, permisos y ámbitos requieren permisos de administración/consulta según el alcance institucional; fuera del ámbito autorizado, responder `403`.
- Aprobación de traslados, bajas y diferencias debe requerir permiso de aprobación y registrar quién decidió. Se debe confirmar la regla de separación entre solicitante y aprobador (RN-008).
- Cada endpoint valida el alcance de sede/dependencia/ubicación/custodia del usuario. No confiar en IDs enviados por el cliente para determinar autorización.
- La descarga de documentos se limita a quien los cargÃ³ o a administraciÃ³n; nunca se entrega la ruta privada del servidor.

La autenticación usa sesión PHP y token CSRF en métodos mutables. Los permisos y ámbitos se evalúan desde las relaciones de usuario, rol, permiso y alcance existentes. Para documentos, la base actual no modela una relación genérica con novedades o mantenimientos: el archivo se registra en privado y su descarga se limita a quien lo cargó o a administración. La importación acepta CSV con las columnas indicadas en `ImportacionController`; su consulta se limita a quien la inició o a administración.

## Pruebas manuales con curl

El usuario de demostración solo existe si se importó `02_seed.sql`. Al iniciar sesión, guarda el cookie jar; toma `csrfToken` de la respuesta JSON y úsalo en cada solicitud que cambie datos:

```bash
curl.exe -i -c cookies.txt -H "Content-Type: application/json" \
  -d '{"correo":"alex.rivera@example.test","contrasena":"Demo-Activo-2026!"}' \
  http://localhost/Proyecto_Final/public/api/v1/sesion

curl.exe -i -b cookies.txt http://localhost/Proyecto_Final/public/api/v1/activos?limite=10

curl.exe -i -b cookies.txt -H "Content-Type: application/json" \
  -H "X-CSRF-Token: PEGA_AQUI_EL_CSRF" -d '{}' \
  http://localhost/Proyecto_Final/public/api/v1/activos
```

La segunda llamada debe devolver `200` para un usuario con permiso y ámbito global. La tercera debe devolver `400` por campos requeridos. Sin cookie, el listado devuelve `401`; un método incorrecto devuelve `405`; una ruta desconocida devuelve `404`. Un usuario sin permiso recibe `403`.






