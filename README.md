# Proyecto

Esqueleto inicial de una aplicacion PHP con arquitectura MVC y patrones DAO/VO.

## Estructura

- `config/`: configuracion de servicios, incluida la conexion a MySQL.
- `controller/`: controladores de la aplicacion.
- `database/`: esquema y datos iniciales.
- `docs/`: requerimientos, decisiones y pruebas.
- `model/dao/`: objetos de acceso a datos.
- `model/vo/`: objetos de valor.
- `public/`: punto de entrada y recursos publicos.
- `view/`: vistas de la aplicacion.

La matriz de rutas y contratos API implementados estÃ¡ en [docs/rutas.md](docs/rutas.md).

## Ejecucion local
Inicia MySQL en XAMPP e importa `database/base/01_schema.sql` desde phpMyAdmin o con el cliente MySQL. El script crea la base `gestion_activos` y sus tablas; no elimina tablas existentes. Si necesitas datos ficticios para una demostración, importa después `database/datos-prueba/02_seed.sql`. El seed usa IDs del rango 9001–9999 y no borra datos; ejecútalo una sola vez en una base vacía o de pruebas. El seed y la cuenta de demostración son solo para desarrollo/pruebas y nunca deben cargarse en producción.

Para reiniciar **solo la base de pruebas**, confirma primero que seleccionaste `gestion_activos` y que no contiene información que necesites. En phpMyAdmin, elimina esa base, vuelve a importar primero `database/base/01_schema.sql` y luego `database/datos-prueba/02_seed.sql`. Esta operación borra todos los datos de esa base; no la uses en una base con información que quieras conservar.

Luego sirve el directorio `public/` como raíz web con Apache o el servidor integrado de PHP.
En XAMPP, para cualquier despliegue fuera de pruebas, configura Apache para que el `DocumentRoot` apunte a `Proyecto_Final/public`; no publiques la raíz del proyecto.

La comparación del informe funcional recibido con el proyecto está en [docs/comparacion_informe_proyecto.md](docs/comparacion_informe_proyecto.md).

### Configuración de conexión

La conexión lee `APP_ENV`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` y `DB_CHARSET` del entorno. El valor predeterminado `APP_ENV=local` usa `127.0.0.1:3306`, `gestion_activos`, `root`, contraseña vacía y `utf8mb4`, según XAMPP. En producción (`APP_ENV=production`) el sistema exige host, base, usuario y contraseña explícitos, y rechaza la cuenta `root`. Define el usuario de base de datos con privilegios mínimos y guarda la contraseña en el gestor de secretos del entorno; no la escribas en el código ni en el repositorio. En PowerShell local se pueden definir así antes de iniciar PHP:

```powershell
$env:APP_ENV = 'local'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_NAME = 'gestion_activos'
$env:DB_USER = 'root'
$env:DB_PASSWORD = ''
```

### DAO

Hay un DAO para cada una de las 31 tablas. Las entidades tienen consultas por clave primaria y búsquedas filtradas; los flujos de usuarios, activos, asignaciones, traslados, préstamos e inventarios tienen métodos de dominio específicos. El mapeo de tablas a clases, los IDs que recibe cada método y ejemplos están en [docs/dao.md](docs/dao.md).

Las eliminaciones físicas no se ofrecen para operaciones con historial; el esquema usa `ON DELETE RESTRICT`. Se permite revocar relaciones de roles y permisos. Para retirar usuarios, activos o catálogos se conserva el registro y se cambia su estado.

La API usa rutas REST bajo `/api/v1`; Apache reescribe las solicitudes con `public/.htaccess` hacia `public/index.php`. La matriz, payloads, errores y ejemplos `curl` están en [docs/rutas.md](docs/rutas.md).

### Importar la planilla institucional de activos

La planilla oficial cargada es la fuente que consultan los módulos **Activos** y **Reportes**; muestran sus valores originales sin convertirlos en categorías, asignaciones o estados operativos. Activos permite recorrer todas las filas y Reportes buscar y exportar hasta 500 coincidencias. La carga XLSX conserva todas las filas de `Lectura`, incluidas placas vacías y repetidas. Para cargar una nueva planilla, importa `database/migraciones/04_planilla_activos_origen.sql` y `database/migraciones/07_planilla_hash.sql` en la base configurada; la columna `Categoria` es opcional y no se infiere del lote o la descripción. La migración `database/migraciones/06_planilla_activos_categoria_origen.sql` solo hace falta si el archivo trae categorías para conservar o convertir. Luego abre **Reportes y archivos > Importar base XLSX**. El hash evita importar el mismo archivo más de una vez. Se requiere PHP con las extensiones `zip` y `simplexml`; para archivos grandes, configura `upload_max_filesize=100M`, `post_max_size=110M`, `max_execution_time=300` y `max_input_time=300` en el `php.ini` de Apache y reinicia Apache.

Para convertir filas en activos operativos se requieren las migraciones `database/migraciones/05_planilla_activos_conversion.sql` y `database/migraciones/06_planilla_activos_categoria_origen.sql`, además del mapeo de categoría por fila, centro de costo, ubicación y estado. Revisa la vista previa antes de confirmar. Se omiten de la conversión las placas vacías, repetidas o ya registradas y las filas incompletas; todas permanecen en la fuente para consulta. El permiso requerido es `activos.ficha.crear`. Los responsables permanecen como datos fuente y no se crean asignaciones automáticamente. La planilla real sin categoría puede cargarse íntegramente como fuente, pero no convertirse hasta completar ese dato.

### Catálogos de sedes, dependencias y ubicaciones

Para cargar las tres sedes confirmadas de CECAR y habilitar las opciones de dependencias y ubicaciones con los valores de la planilla, ejecuta `database/migraciones/08_sedes_cecar.sql` y luego `database/migraciones/09_catalogos_desde_planilla.sql`. El segundo script conserva los nombres fuente; solo asigna sede cuando el texto de origen permite identificarla. Las demás ubicaciones se muestran como pendientes de confirmar sede. Para copiar los registros con placa única a la tabla operativa `activo`, ejecuta `database/migraciones/10_cargar_activos_planilla.sql`; usa una categoría explícita «Sin clasificar», deja su estado en `REGISTRADO` y excluye placas repetidas o vacías, que permanecen en la fuente.

### Vistas

Las vistas del portal y la aplicación están en `view/`; los estilos y la integración JavaScript con el API están en `public/assets/`. Con Apache/XAMPP abre `http://localhost/Proyecto_Final/public/`. El mapa de pantallas, la trazabilidad a requisitos y los tokens visuales aproximados están en [docs/vistas.md](docs/vistas.md).



## Extensión de Procesos de selección

La migración opcional `database/migraciones/03_seleccion.sql` agrega tablas y permisos para el módulo, sin borrar ni alterar las tablas de activos. Consulta [docs/migracion-seleccion.md](docs/migracion-seleccion.md) antes de importarla en phpMyAdmin. El módulo ya tiene endpoints y DAO; importa la migración antes de usarlo y asigna sus permisos solo al personal autorizado.

### Funciones CECAR

Las funciones institucionales anadidas, migraciones y decisiones pendientes se documentan en [docs/implementacion-cecar.md](docs/implementacion-cecar.md).
