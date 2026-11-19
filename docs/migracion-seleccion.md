# Extensión de base de datos: Procesos de selección

`database/migraciones/03_seleccion.sql` agrega de forma no destructiva las tablas del módulo. No modifica ni borra tablas de activos.

## Tablas y relaciones

| Tabla | Propósito | Relación |
|---|---|---|
| `proceso_seleccion` | Convocatoria, unidad, fechas y estado | Pertenece a `dependencia`; registra el usuario creador |
| `postulante_seleccion` | Nombre y correo de cada postulante | Puede tener postulaciones en varios procesos |
| `postulacion_seleccion` | Participación del postulante en una convocatoria y su estado | FK a proceso y postulante; evita duplicar la postulación dentro del mismo proceso |
| `documento_postulacion` | Metadata, ruta privada, versión y estado de revisión de cada documento | FK a postulación y al usuario revisor; conserva versiones y no permite borrados en cascada |

Las tarjetas se calculan desde procesos, postulaciones y documentos; los contadores no deben almacenarse duplicados. El archivo de documento se guarda fuera de `public/`; esta migración solo agrega metadata y ruta privada.

La migración crea permisos `seleccion.proceso.leer`, `seleccion.proceso.administrar` y `seleccion.postulacion.revisar`. En una BD de prueba los asocia al rol `ADMIN_PRUEBA` si existe. En otros entornos deben asignarse explícitamente al rol autorizado de Gestión Humana. La pantalla usa el API para listar procesos, crear convocatorias y postulaciones, cargar documentos y revisar estados. Los endpoints aplican los tres permisos indicados.

## Importar en phpMyAdmin

1. Exporta una copia de seguridad de `gestion_activos` antes de cambiarla.
2. En phpMyAdmin, selecciona `gestion_activos` y abre **Importar**.
3. Elige `database/migraciones/03_seleccion.sql` y ejecútalo. Importa `database/base/01_schema.sql` y `database/datos-prueba/02_seed.sql` solo si la base aún no los tiene; no los vuelvas a importar sobre datos existentes.
4. Revisa las dos consultas de verificación al final del script: deben listar cuatro tablas y tres permisos.

El script requiere MySQL 8.0.16 o superior para hacer cumplir sus restricciones `CHECK`. No es una operación destructiva y se puede volver a ejecutar, aunque `CREATE TABLE IF NOT EXISTS` no repara una tabla preexistente con estructura distinta.

## Privacidad y despliegue

El módulo guarda nombre y correo de postulantes, por lo que el acceso debe limitarse al personal autorizado y se debe acordar retención/eliminación según la política institucional antes de cargar datos reales. Usa exclusivamente datos ficticios en desarrollo. Los archivos se autentican, validan por MIME y tamaño y se guardan fuera de public/. El análisis antivirus aún debe configurarse antes de producción.
