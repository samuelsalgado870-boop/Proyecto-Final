s# Proyecto

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
Inicia MySQL en XAMPP e importa `database/01_schema.sql` desde phpMyAdmin o con el cliente MySQL. El script crea la base `gestion_activos` y sus tablas; no elimina tablas existentes. Después, importa `database/02_seed.sql` para cargar datos ficticios de prueba. El seed usa IDs del rango 9001–9999 y no borra datos; ejecútalo una sola vez en una base vacía o de pruebas. El seed y la cuenta de demostración son solo para desarrollo/pruebas y nunca deben cargarse en producción.

Para reiniciar **solo la base de pruebas**, confirma primero que seleccionaste `gestion_activos` y que no contiene información que necesites. En phpMyAdmin, elimina esa base, vuelve a importar primero `database/01_schema.sql` y luego `database/02_seed.sql`. Esta operación borra todos los datos de esa base; no la uses en una base con información que quieras conservar.

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

La carga XLSX conserva las filas fuente de la hoja `Lectura`, incluidas las filas sin placa y las placas repetidas. Antes de cargarla, importa una vez `database/04_planilla_activos_origen.sql` en la base configurada. Luego abre **Reportes y archivos > Importar base XLSX** e inicia la carga desde la aplicación. Se requiere PHP con las extensiones `zip` y `simplexml`; para archivos grandes, configura `upload_max_filesize=100M`, `post_max_size=110M`, `max_execution_time=300` y `max_input_time=300` en el `php.ini` de Apache y reinicia Apache.

La planilla se conserva como fuente para revisión; el sistema no convierte automáticamente sus nombres de centro de costo, ubicación, estados ni responsables en relaciones operativas. Las placas vacías y repetidas se identifican por fila para su conciliación.

### Vistas

Las vistas del portal y la aplicación están en `view/`; los estilos y la integración JavaScript con el API están en `public/assets/`. Con Apache/XAMPP abre `http://localhost/Proyecto_Final/public/`. El mapa de pantallas, la trazabilidad a requisitos y los tokens visuales aproximados están en [docs/vistas.md](docs/vistas.md).



## Extensión de Procesos de selección

La migración opcional database/03_seleccion.sql agrega tablas y permisos para el módulo, sin borrar ni alterar las tablas de activos. Consulta [docs/migracion-seleccion.md](docs/migracion-seleccion.md) antes de importarla en phpMyAdmin. El módulo ya tiene endpoints y DAO; importa la migración antes de usarlo y asigna sus permisos solo al personal autorizado.
