# Decisiones de arquitectura e implementación

## Estado del modelo de datos

- **Propuesta funcional:** consultar [requerimientos.md](requerimientos.md), que consolida el modelo relacional derivado de las seis hojas del Excel.
- **Aprobación:** pendiente de validación institucional. Los RF-001 a RF-028 figuran como obligatorios en la fuente, pero algunas reglas del modelo siguen por confirmar.
- **SQL:** no se ha generado ni ejecutado SQL como resultado de esta revisión. El esquema actual es un artefacto previo y no debe asumirse aprobado.

## Discrepancias conocidas del esquema existente

`database/base/01_schema.sql` ya contiene varias entidades de la propuesta, pero no la implementa por completo. Entre las diferencias que se deben resolver antes de consolidar el SQL:

- El esquema usa `documento_activo` en lugar de la entidad general `documento`, e incluye listas de campos de categoría en JSON en vez de `categoria_campo`.
- No aparecen las entidades propuestas `aprobacion`, `devolucion` ni `acta_participante`; `acta` almacena participantes como texto y usa una relación polimórfica con operación.
- En `diferencia_inventario` la FK referencia `aprobado_por`, mientras que la columna declarada es `aprobador_id`; la definición debe corregirse antes de ejecutar el script.
- El esquema guarda `sede_id` en `activo` además de `ubicacion_id`, aunque la propuesta obtiene la sede desde la ubicación para evitar duplicidad.
- `ambito_acceso` usa `entidad_id` genérico, sin FKs tipadas que hagan cumplir la integridad referencial propuesta.
- Hay que revisar las reglas de asignación vigente, las aprobaciones múltiples, los datos de origen y destino de movimientos, y la relación de las bajas con documentos y actas.

Este listado no sustituye una revisión completa del SQL ni constituye validación de ejecución.

## Seguridad ante ejecución

El archivo `database/base/01_schema.sql` contiene `DROP TABLE IF EXISTS`. No ejecutarlo sobre una base que contenga datos que deban conservarse. La siguiente versión, después de aprobar el modelo, debe ser un script de creación para una base nueva, revisado y probado de forma controlada.

## Nombre de base de datos

Pendiente de decisión: el esquema crea/selecciona `gestion_activos`, mientras que `config/database.php` configura `proyecto`. Alinear ambos nombres antes de conectar la aplicación a una base real.
