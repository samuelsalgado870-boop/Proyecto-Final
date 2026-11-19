# Consultas, soportes y reglas CECAR

## Funcionalidad disponible

- **Historial:** `/app/operaciones` permite buscar y paginar operaciones por tipo, placa/texto, sede y ubicación. Incluye asignaciones, devoluciones, traslados, movimientos, préstamos, novedades, mantenimientos, bajas, inventarios, verificaciones, diferencias y decisiones de traslado. Filtra por los ámbitos vigentes del usuario.
- **Conciliación:** una jornada muestra totales esperados, verificados, faltantes, hallazgos sin activo y diferencias. El detalle se agrupa por sede, ubicación y dependencia, y puede descargarse como CSV. El universo esperado usa el catálogo operativo actual dentro del alcance de la jornada; no reemplaza el cierre físico aprobado por CECAR.
- **Soportes:** Operaciones permite subir PDF, PNG o JPEG y asociarlos a una operación existente. También puede registrar un acta tipada ligada a la misma operación y al documento. La base aplica claves foráneas para evitar referencias incompatibles.
- **Permisos por ámbito:** Administración permite asignar o revocar ámbitos globales, por sede, dependencia o ubicación. Sin ámbito vigente, el acceso se deniega. Los cambios se auditan. El administrador local conservado tiene un ámbito global explícito.
- **Aprobaciones:** quien solicita un traslado no puede aprobarlo. La decisión queda registrada en `aprobacion` y en el historial. Es una salvaguarda provisional mientras CECAR define los flujos institucionales completos.
- **Retención y clasificación:** se puede registrar una propuesta de plazo de retención, pero no hay borrado automático. Activos y categorías provenientes de la planilla están marcados `PENDIENTE`; sus valores no deben interpretarse como catálogo aprobado.

## Decisiones pendientes de validación de CECAR

1. Operaciones que requieren aprobación, responsables, suplencias y excepciones.
2. Matriz final de ámbitos por rol, incluidos accesos globales y de custodios.
3. Plazo de conservación por tipo documental; hasta aprobarlo, no se borran datos automáticamente.
4. Catálogo de categorías/estados y reglas para convertir los valores de la planilla.
5. Criterio oficial de conciliación, aceptación de diferencias y cierre del inventario.

## Migración y respaldo

La migración `database/migraciones/11_historial_documentos_politicas.sql` agrega relaciones tipadas y configuración provisional. `database/migraciones/12_validacion_planilla.sql` marca los activos y categorías importados como pendientes. Antes de aplicar la migración 11 en el entorno local se guardó un respaldo completo en `storage/private/backups/gestion_activos_pre_features_20261119.sql`.

`database/base/01_schema.sql` es para una instalación nueva y contiene instrucciones destructivas. En una base existente se aplican solo migraciones incrementales pendientes, una vez y con respaldo.
