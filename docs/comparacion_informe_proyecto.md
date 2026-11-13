# Comparación del informe funcional con el proyecto

## Resumen

El informe describe una herramienta de apoyo para el inventario físico anual: recibe una exportación de Iceberg, captura lecturas y novedades durante el conteo, concilia resultados y exporta archivos. No debe escribir en Iceberg ni reemplazar ISOLUCION. El proyecto actual es una aplicación web PHP/MySQL para gestión de activos con módulos de usuarios, asignaciones, traslados, préstamos, mantenimiento, bajas, documentos e inventarios. Hay trabajo reutilizable, pero no es todavía una implementación equivalente al flujo de inventario del informe.

## Comparación funcional

| Necesidad del informe | Situación visible en el proyecto | Evaluación |
|---|---|---|
| Captura de inventario y registro de verificaciones | `InventarioController`, `InventarioDetalleController` y vistas de inventario permiten jornadas, detalles, diferencias y estados | Parcial: el modelo cubre verificaciones genéricas, pero no refleja aún explícitamente todos los resultados del informe (recuperado, sin placa temporal, estado físico, usuario real, sububicación, placa anterior/nueva y notas de lectura) |
| Validar placa al leerla contra la exportación de Iceberg | Hay búsqueda de activo por placa; la importación disponible es CSV y crea activos | Parcial: no se ve un flujo dedicado que importe la exportación como base de referencia del inventario y clasifique cada lectura automáticamente |
| Escaneo de placa con cámara y uso en móvil/tablet | No se encontró flujo de cámara/lector ni interfaz específica de captura rápida | Pendiente |
| Autoguardado y tolerancia a interrupciones | La aplicación persiste operaciones en MySQL a través de los DAO | Parcial: cada operación registrada persiste, pero no se encontró cola local, reintento o captura offline para cortes de conectividad |
| Varias personas cuentan el mismo inventario | La API trabaja con sesiones de varios usuarios y registra verificador en detalles | Parcial: no se encontró coordinación de sesiones concurrentes, bloqueo de ubicación ni prevención explícita de conteos duplicados entre operadores |
| Avance por ubicación/sede, cierre y conciliación | Hay cierre de inventario y reportes; el API no ofrece listado de jornadas según `docs/vistas.md` | Parcial: falta comparar con precisión los totales y cruces de faltantes/sobrantes pedidos en el informe |
| Exportar consolidados para actualizar Iceberg por sus vías habituales | Existe reporte/exportación de activos; la importación está orientada a registrar activos | Parcial: falta salida específica de resultados de inventario y dejar claro que es un archivo para revisión humana, no escritura directa en Iceberg |
| El aplicativo no administra el ciclo contable del activo | El proyecto incluye asignaciones, traslados, préstamos, mantenimiento y bajas, además del inventario | Diferencia de alcance: esos módulos exceden el propósito del informe y requieren decisión explícita del equipo funcional |
| Privacidad de responsables y protección de API keys | Roles, sesiones y acceso por permisos existen; no se encontró integración de IA o clave de API en runtime | Parcial: hay controles de acceso técnicos, pero no una definición de retención/minimización ni evaluación institucional de datos personales. La clave de IA no aplica mientras no exista esa integración |

## Hallazgos de seguridad relacionados con el informe

- La sesión usa cookie `HttpOnly`, `SameSite=Lax`, modo estricto y regeneración de ID al autenticar. Las solicitudes de escritura del API validan un token CSRF.
- Los DAO usan PDO y sentencias preparadas; las credenciales se obtienen del entorno. `APP_ENV` rechaza valores desconocidos y, en producción, la conexión exige variables explícitas, contraseña no vacía y usuario distinto de `root` (`config/database.php`).
- Las contraseñas de cuentas se guardan con `password_hash()` y se comprueban con `password_verify()`. `UsuarioVO::jsonSerialize()` omite el hash.
- La carga de documentos requiere sesión/permisos, limita tamaño y tipos por MIME detectado, crea nombres aleatorios y los guarda bajo `storage/`, fuera del directorio público. Las rutas privadas tienen `.htaccess` de denegación.
- La importación CSV autentica/autorizada limita tamaño y exige columnas. La validación por extensión no demuestra por sí sola que el contenido sea un CSV benigno; debe tratarse como importación de texto y no ejecutarse.
- No se encontró análisis antivirus de archivos subidos. Antes de producción hay que instalar/configurar un scanner o retirar la carga de documentos si el despliegue no puede inspeccionarlos.
- La configuración Apache y los permisos del sistema operativo dependen del despliegue. En XAMPP local se debe servir `public/` como document root; no exponer la raíz del proyecto. La carpeta de carga necesita permisos de escritura únicamente para el usuario del servicio web y no debe permitir ejecución.
- El seed contiene cuentas ficticias de demostración y una contraseña de prueba conocida (`database/02_seed.sql`); es exclusivamente para desarrollo y no se debe ejecutar ni distribuir como carga de producción.
- No se encontró configuración de repositorio Git en la raíz al revisar este workspace. El control de cambios, separación de ambientes, cuentas del sistema operativo y bóveda/rotación de secretos deben definirse en la infraestructura/equipo.
- El proyecto no demuestra cumplimiento certificado FIPS 140-2 ni una política institucional de llaves. No debe afirmarse ese cumplimiento sin validar runtime, sistema operativo y módulos criptográficos del entorno.

## Decisiones funcionales que faltan

1. Confirmar si el alcance aprobado es inventario físico solamente o si también se deben conservar los flujos de ciclo de vida de activos del proyecto actual.
2. Definir el formato y el mapeo real de la exportación de Iceberg, y distinguir una carga de referencia de una creación de activos.
3. Acordar catálogo de resultados, condición física, usuario observado, sububicación, placa temporal y flujo para placas cambiadas.
4. Definir trabajo simultáneo, manejo de desconexiones, prevención de duplicados, permisos por sede y criterios para cerrar/reabrir ubicaciones.
5. Definir los archivos de salida y el procedimiento humano de conciliación/actualización en Iceberg e ISOLUCION.
6. Acordar tratamiento de datos personales, retención, perfiles autorizados, despliegue y soporte; estas decisiones requieren validación institucional.

## Archivos revisados

`config/database.php`, `public/index.php`, `controller/DocumentoController.php`, `controller/ImportacionController.php`, `controller/InventarioController.php`, `controller/InventarioDetalleController.php`, `model/dao/`, `model/vo/`, `view/`, `public/assets/js/app.js`, `database/01_schema.sql`, `database/02_seed.sql`, `docs/requerimientos.md`, `docs/rutas.md` y `docs/vistas.md`.
