# Vistas de Gestión de Activos

## Base visual

La referencia visual muestra una portada, un tablero con navegaciÃ³n lateral, tarjetas de resumen y tablas filtrables. Se adaptaron estos patrones a los mÃ³dulos de activos y al nuevo acceso de Procesos de selecciÃ³n.

Los siguientes valores son aproximaciones visuales de la captura y no códigos oficiales de CECAR:

| Token CSS | Valor | Uso |
|---|---|---|
| `--brand-deep` | `#0b2f1b` | Menú y fondos de énfasis |
| `--brand-forest` | `#246b36` | Navegación y encabezados |
| `--brand-leaf` | `#3e9348` | Acciones principales |
| `--brand-lime` | `#8dbb4a` | Acentos y estados positivos |
| `--color-bg` | `#f3f6f4` | Fondo de aplicación |
| `--color-surface` | `#ffffff` | Tarjetas y formularios |
| `--color-text` | `#18221b` | Texto principal |
| `--color-muted` | `#657168` | Texto auxiliar |
| `--color-border` | `#dce5de` | Bordes y divisores |
| `--color-warning` | `#d5a23a` | Advertencias |
| `--color-danger` | `#b94a48` | Errores y bajas |

La captura no permite identificar una familia tipográfica con certeza. La implementación usa una pila sans serif del sistema (`Inter`, `Segoe UI`, `Roboto`, `Arial`). El logotipo de referencia no se reutiliza; la interfaz usa una marca geométrica provisional de CSS.

## Vistas y trazabilidad

| Ruta de vista | Propósito | Requisitos | Endpoints consumidos |
|---|---|---|---|
| `/` | Portada institucional y acceso | RF-001 | Enlace a `/login` |
| `/login` | Inicio de sesión y errores de autenticación | RF-001 | `POST /sesion` |
| `/app` | Accesos frecuentes, activos visibles y avisos de la cuenta | RF-002–003, RF-009, RF-025 | `GET /sesion`, `GET /activos`, `GET /notificaciones` |
| `/app/activos` | Consulta filtrada, búsqueda por placa, ficha, creación y cambio de estado | RF-007–009, RF-025 | `GET /activos`, `GET /activos/placa/{placa}`, `GET /activos/{id}`, `POST /activos`, `PATCH /activos/{id}/estado`, catálogos |
| `/app/operaciones` | Asignaciones, devoluciones, traslados, préstamos, novedades, mantenimiento y bajas | RF-010–020, RF-024 | Endpoints `POST /asignaciones`, `/traslados`, `/prestamos`, `/novedades`, `/activos/{id}/mantenimientos`, `/activos/{id}/bajas` y devoluciones |
| `/app/inventarios` | Crear y consultar una jornada por ID, iniciar/cerrar, verificar activos y registrar diferencias | RF-021–023 | `POST /inventarios`, `GET /inventarios/{id}`, inicio, cierre, detalles y diferencias |
| `/app/procesos-seleccion` | Convocatorias, postulaciones, documentos y revisión de estados | Nuevo módulo solicitado | `GET/POST/PATCH /procesos-seleccion`, postulaciones y documentos |
| `/app/administracion` | Crear usuarios y sedes/dependencias/ubicaciones, consultar usuarios y asignar roles | RF-002–006 | `GET/POST /usuarios`, estado, roles y catálogos |
| `/app/reportes` | Reporte de activos y exportación CSV, carga privada de documentos, importación CSV y consulta de auditoría | RF-025–028 | `GET /reportes/activos`, `POST /documentos`, `POST /importaciones`, `GET /importaciones/{id}`, `GET /auditoria` |

## Límites del API actual reflejados en la interfaz

- `/app/procesos-seleccion` ya está disponible en el menú y usa el API de selección. Requiere la migración aplicada y permisos asignados a los usuarios autorizados.


- El tablero cuenta los activos devueltos por una consulta de hasta 100 filas y las notificaciones devueltas por una consulta de hasta 20 filas. Las etiquetas indican el alcance de cada consulta y no presentan esos valores como totales del sistema.
- La API permite abrir una jornada de inventario por ID, pero no ofrece un endpoint de colección para listar jornadas. La vista solicita el ID para consultar una existente.
- La API no ofrece listados históricos de asignaciones, préstamos, traslados, novedades o mantenimientos. Sus formularios requieren el ID del activo u operación cuando corresponde.
- La API actual permite consultar roles y categorías, pero no editar esos catálogos desde la interfaz.
- Los documentos se guardan de forma privada; su relación con novedades y mantenimientos no está representada como relación genérica en el esquema.
- Los mensajes de permisos, referencias inexistentes y conflictos se presentan desde la respuesta del API. La autorización efectiva sigue siendo responsabilidad del servidor.

## Accesibilidad y adaptación

- Cada página tiene un enlace para saltar al contenido, encabezados jerárquicos, etiquetas de formulario, nombres accesibles para controles e indicadores de carga/error con regiones vivas.
- Los estados incluyen texto, no solo color. Los controles muestran foco de teclado y se respeta `prefers-reduced-motion`.
- En pantallas estrechas, el menú se vuelve plegable, los formularios pasan a una columna y las tablas conservan desplazamiento horizontal para mantener sus columnas legibles.

## Ejecución

Con Apache/XAMPP sirviendo la carpeta `htdocs`, abre `http://localhost/Proyecto_Final/public/`. El primer ingreso presenta la portada; las páginas `/app/...` exigen sesión. La API calcula el prefijo desde el directorio publicado, así que los mismos archivos funcionan también si `public/` es la raíz del servidor PHP.


