# DAO y consultas disponibles

Todas las clases están en `App\Model\DAO` y reciben una instancia `PDO`. `ConsultaEntidadDAO` ofrece lectura para cada entidad: `buscarPorId(int $id)` cuando la PK es simple, `buscarPorClave(array $clave)` para PK compuesta y `buscarPorCampo(string $campo, int|string|null $valor, int $limite = 100)` para filtros de igualdad. El filtro valida la columna contra una lista fija; `password_hash` está excluida de las columnas buscables. Los valores y el límite se enlazan como parámetros. Devuelven VO, `null` o listas de VO.

## Protección contra inyección SQL

Las consultas enlazan los datos recibidos con parámetros PDO; no incorporan valores de formularios, URL o archivos dentro del texto SQL. Las consultas dinámicas solo ensamblan identificadores y fragmentos definidos en el código: `ConsultaEntidadDAO` valida columnas y claves contra constantes permitidas, y los filtros de activos, usuarios, auditoría e informes usan nombres de columnas fijos. No se usa `PDO::query()`, `PDO::exec()` ni concatenación de valores del usuario en SQL. La conexión desactiva las preparaciones emuladas con `PDO::ATTR_EMULATE_PREPARES = false`.

## Tabla y DAO

| Tabla | DAO | Identificador de lectura |
|---|---|---|
| `usuario` | `UsuarioDAO` | `usuario.id`; `autenticar()` busca por correo |
| `rol` | `RolDAO` | `rol.id` |
| `permiso` | `PermisoDAO` | `permiso.id` |
| `usuario_rol` | `UsuarioRolDAO` | PK `(usuario_id, rol_id)` |
| `rol_permiso` | `RolPermisoDAO` | PK `(rol_id, permiso_id)` |
| `sede` | `SedeDAO` | `sede.id` |
| `dependencia` | `DependenciaDAO` | `dependencia.id` |
| `ubicacion` | `UbicacionDAO` | `ubicacion.id` |
| `categoria_activo` | `CategoriaActivoDAO` | `categoria_activo.id` |
| `categoria_campo` | `CategoriaCampoDAO` | PK `(categoria_id, campo_codigo)` |
| `activo` | `ActivoDAO` | `activo.id`; también placa |
| `ambito_acceso` | `AmbitoAccesoDAO` | `ambito_acceso.id` |
| `documento` | `DocumentoDAO` | `documento.id` |
| `asignacion` | `AsignacionDAO` | `asignacion.id`; vigente por `activo_id` |
| `solicitud_traslado` | `SolicitudTrasladoDAO` | `solicitud_traslado.id` |
| `aprobacion` | `AprobacionDAO` | `aprobacion.id` |
| `movimiento_activo` | `MovimientoActivoDAO` | `movimiento_activo.id` |
| `prestamo` | `PrestamoDAO` | `prestamo.id` |
| `novedad` | `NovedadDAO` | `novedad.id` |
| `mantenimiento` | `MantenimientoDAO` | `mantenimiento.id` |
| `devolucion` | `DevolucionDAO` | `devolucion.id` |
| `baja_activo` | `BajaActivoDAO` | `baja_activo.id` |
| `acta` | `ActaDAO` | `acta.id` |
| `acta_participante` | `ActaParticipanteDAO` | PK `(acta_id, usuario_id)` |
| `inventario` | `InventarioDAO` | `inventario.id` |
| `inventario_detalle` | `InventarioDetalleDAO` | `inventario_detalle.id` |
| `diferencia_inventario` | `DiferenciaInventarioDAO` | `diferencia_inventario.id` |
| `auditoria` | `AuditoriaDAO` | `auditoria.id` |
| `notificacion` | `NotificacionDAO` | `notificacion.id` |
| `importacion` | `ImportacionDAO` | `importacion.id` |
| `detalle_importacion` | `DetalleImportacionDAO` | `detalle_importacion.id` |

## Operaciones de dominio

- `UsuarioDAO::crear()` crea cuenta y guarda el resultado de `password_hash`; `buscarPorId()` recibe `usuario.id`; `autenticar()` recibe correo y contraseña; `cambiarEstado()` recibe `usuario.id`.
- `ActivoDAO::buscarPorPlaca()` recibe placa; `buscar()` filtra por estado, `categoria_id` y `dependencia_id`, con límite; `crear()` inserta un activo; `cambiarEstado()` recibe `activo.id`.
- `AsignacionDAO::buscarVigentePorActivo()` recibe `activo.id`; `crear()` inserta la asignación y actualiza el activo en una transacción; `finalizar()` recibe `asignacion.id` y cierra la custodia.
- `SolicitudTrasladoDAO::crear()` inserta una solicitud y cambia el estado del activo atómicamente; `buscarPorId()` y `resolver()` reciben `solicitud_traslado.id`. Resolver añade la fila correspondiente a `aprobacion`.
- `PrestamoDAO::crear()` registra préstamo y actualiza estado del activo en una transacción; `buscarPorId()` recibe `prestamo.id`; `registrarDevolucion()` recibe ese mismo ID y crea `devolucion` actualizando el préstamo y el activo.
- `InventarioDAO::crear()` abre una jornada; `buscarPorId()`, `iniciar()` y `cerrar()` reciben `inventario.id`; `registrarDetalle()` agrega detalle; `listarDetalles()` recibe `inventario.id`.
- `MovimientoActivoDAO::completarTraslado()` recibe `solicitud_traslado.id` autorizada, registra el movimiento y actualiza ubicación/estado en una transacción.
- `DevolucionDAO::registrarDevolucionAsignacion()` recibe `asignacion.id`, registra devolución y finaliza custodia en una transacción. `PrestamoDAO::registrarDevolucion()` hace lo propio con `prestamo.id`.
- `NovedadDAO::reportar()` recibe `activo.id` y `usuario.id` del reportante y devuelve `novedad.id`.
- `BajaActivoDAO::solicitar()` recibe `activo.id`, crea la solicitud y cambia el activo a revisión dentro de una transacción.
- `AuditoriaDAO::registrar()` recibe el ID opcional de usuario y el ID de la entidad auditada, guardando snapshots JSON.
- `NotificacionDAO::marcarLeida()` recibe `notificacion.id` y solo marca notificaciones pendientes.
- `UsuarioRolDAO::asignar()`/`revocar()` usan ambos IDs de `(usuario_id, rol_id)`; `RolPermisoDAO::asignar()`/`revocar()` usan ambos IDs de `(rol_id, permiso_id)`. Revocar una asociación es un borrado permitido; las entidades con historial se conservan.

## Ejemplo

```php
$pdo = (new App\Config\Database())->connect();
$dao = new App\Model\DAO\ActivoDAO($pdo);

$activo = $dao->buscarPorId(9001); // activo.id
$coincidencias = $dao->buscar('DISPONIBLE', 9001, null, 50);

$usuarios = new App\Model\DAO\UsuarioDAO($pdo);
$sufijo = bin2hex(random_bytes(4));
$nuevoId = $usuarios->crear('QA-' . $sufijo, 'Usuario ficticio QA', 'qa-' . $sufijo . '@example.test', 'ClaveTemporal!');
$usuario = $usuarios->buscarPorId($nuevoId); // usuario.id
$usuarios->cambiarEstado($nuevoId, 'INACTIVO');

// Revocación permitida de relación; transacción reversible para una prueba.
$pdo->beginTransaction();
try {
    $relaciones = new App\Model\DAO\RolPermisoDAO($pdo);
    $relaciones->revocar(9001, 9010); // rol_id + permiso_id
    $relaciones->asignar(9001, 9010);
    $pdo->rollBack(); // prueba sin dejar cambios
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
```

La aplicación no genera HTML desde los DAO. Los errores PDO se propagan como excepciones para que la capa de servicio/controlador decida cómo registrarlos y responder.

## Procesos de selección (módulo adicional)

- `ProcesoSeleccionDAO::listar()` devuelve procesos, unidad y conteos agregados de postulaciones/documentos; admite filtros por estado, dependencia y nombre.
- `ProcesoSeleccionDAO::listarDependenciasActivas()` proporciona unidades activas para la creación de procesos.
- `ProcesoSeleccionDAO::buscarPorId()` recibe `proceso_seleccion.id`.
- `ProcesoSeleccionDAO::crear()` recibe nombre, `dependencia_id`, estado inicial, fechas y `usuario.id` creador; registra auditoría en la misma transacción.
- `ProcesoSeleccionDAO::cambiarEstado()` recibe `proceso_seleccion.id`, estado y actor; conserva el evento de auditoría.
- `ProcesoSeleccionDAO::listarPostulaciones()` recibe `proceso_seleccion.id` y devuelve postulantes más conteos documentales.
- `ProcesoSeleccionDAO::registrarPostulacion()` recibe `proceso_seleccion.id`, nombre/correo y usuario actor; exige que el proceso esté abierto y evita duplicados.
- `DocumentoPostulacionDAO::registrar()` recibe `postulacion_seleccion.id`, metadata privada y actor; calcula versión y audita dentro de una transacción.
- `DocumentoPostulacionDAO::listarPorPostulacion()` y `buscarPorId()` reciben respectivamente `postulacion_seleccion.id` y `documento_postulacion.id`.
- `DocumentoPostulacionDAO::revisar()` recibe `documento_postulacion.id`, estado, observación y revisor; registra el cambio de estado en auditoría.
- `DocumentoPostulacionDAO::perteneceAProceso()` valida que la postulación de carga pertenezca al proceso de la ruta.

Para probar contra MySQL, configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` con los mismos datos de conexión usados por phpMyAdmin. Las pruebas que modifican filas deben ejecutarse en una base de pruebas; para validar una revocación se puede envolver el cambio en una transacción y revertirla.
