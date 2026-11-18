<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controller\ApiException;
use App\Model\DAO\AutorizacionDAO;

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Config\\' => [__DIR__ . '/../config/', true],
        'App\\Controller\\' => [__DIR__ . '/../controller/', false],
        'App\\Model\\VO\\' => [__DIR__ . '/../model/vo/', false],
        'App\\Model\\DAO\\' => [__DIR__ . '/../model/dao/', false],
    ];
    foreach ($prefixes as $prefix => [$directory, $lowercase]) {
        if (!str_starts_with($class, $prefix)) continue;
        $name = substr($class, strlen($prefix));
        if (str_contains($name, '\\')) return;
        $file = $directory . ($lowercase ? strtolower($name) : $name) . '.php';
        if (is_file($file)) require_once $file;
        return;
    }
});

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
ini_set('display_errors', '0');
error_reporting(E_ALL);

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
$assetBase = $basePath . '/assets';
$apiBase = $basePath . '/api/v1';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}
if ($requestPath === '') $requestPath = '/';
$requestPath = rtrim($requestPath, '/') ?: '/';

$pageRoutes = [
    '/' => ['home.php', false, 'Inicio'],
    '/login' => ['login.php', false, 'Iniciar sesión'],
    '/app' => ['dashboard.php', true, 'Panel principal'],
    '/app/activos' => ['activos.php', true, 'Activos'],
    '/app/operaciones' => ['operaciones.php', true, 'Operaciones'],
    '/app/inventarios' => ['inventarios.php', true, 'Inventarios'],
    '/app/procesos-seleccion' => ['procesos-seleccion.php', true, 'Procesos de selección'],
    '/app/administracion' => ['administracion.php', true, 'Administración'],
    '/app/reportes' => ['reportes.php', true, 'Reportes y documentos'],
];

if (!str_starts_with($requestPath, '/api/v1')) {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        echo 'Método no permitido';
        exit;
    }
    if (!isset($pageRoutes[$requestPath])) {
        http_response_code(404);
        $pageTitle = 'Página no encontrada';
        require __DIR__ . '/../view/not-found.php';
        exit;
    }
    [$viewFile, $requiresSession, $pageTitle] = $pageRoutes[$requestPath];
    if ($requiresSession && (!isset($_SESSION['user_id']) || !is_int($_SESSION['user_id']))) {
        header('Location: ' . $basePath . '/login', true, 302);
        exit;
    }
    if ($requestPath === '/login' && isset($_SESSION['user_id']) && is_int($_SESSION['user_id'])) {
        header('Location: ' . $basePath . '/app', true, 302);
        exit;
    }
    $pagePermissionGroups = [
        '/app/activos' => [['activos', 'ficha', 'leer']],
        '/app/operaciones' => [['activos', 'ficha', 'leer']],
        '/app/inventarios' => [['inventarios', 'jornada', 'crear']],
        '/app/procesos-seleccion' => [['seleccion', 'proceso', 'leer']],
        '/app/administracion' => [['usuarios', 'cuenta', 'administrar']],
        '/app/reportes' => [['reportes', 'informe', 'exportar']],
    ];
    if (isset($pagePermissionGroups[$requestPath])) {
        try {
            $authorization = new AutorizacionDAO((new Database())->connect());
            $hasPagePermission = false;
            foreach ($pagePermissionGroups[$requestPath] as $permission) {
                if ($authorization->tienePermiso((int) $_SESSION['user_id'], ...$permission)) {
                    $hasPagePermission = true;
                    break;
                }
            }
        } catch (Throwable $error) {
            error_log('Page authorization failure: ' . $error->getMessage());
            http_response_code(500);
            $pageTitle = 'Error del sistema';
            require __DIR__ . '/../view/page-error.php';
            exit;
        }
        if (!$hasPagePermission) {
            http_response_code(403);
            $pageTitle = 'Acceso restringido';
            $activePage = $requestPath;
            require __DIR__ . '/../view/forbidden.php';
            exit;
        }
    }
    $activePage = $requestPath;
    $bodyClass = $requestPath === '/' ? 'public-body public-body--landing' : 'public-body';
    require __DIR__ . '/../view/' . $viewFile;
    exit;
}

$routes = [
    ['POST','/api/v1/sesion','SesionController','crear',null],
    ['GET','/api/v1/sesion','SesionController','mostrar',null],
    ['DELETE','/api/v1/sesion','SesionController','cerrar',null],
    ['GET','/api/v1/usuarios','UsuarioController','listar','usuarios.cuenta.administrar'],
    ['POST','/api/v1/usuarios','UsuarioController','crear','usuarios.cuenta.administrar'],
    ['GET','/api/v1/usuarios/(?P<id>\d+)','UsuarioController','mostrar','usuarios.cuenta.administrar'],
    ['PATCH','/api/v1/usuarios/(?P<id>\d+)/estado','UsuarioController','cambiarEstado','usuarios.cuenta.administrar'],
    ['PUT','/api/v1/usuarios/(?P<id>\d+)/roles','UsuarioRolController','reemplazar','usuarios.cuenta.administrar'],
    ['GET','/api/v1/roles','RolController','listar','usuarios.cuenta.administrar'],
    ['GET','/api/v1/sedes','SedeController','listar','activos.ficha.leer'],
    ['POST','/api/v1/sedes','SedeController','crear','usuarios.cuenta.administrar'],
    ['GET','/api/v1/dependencias','DependenciaController','listar','activos.ficha.leer'],
    ['POST','/api/v1/dependencias','DependenciaController','crear','usuarios.cuenta.administrar'],
    ['GET','/api/v1/ubicaciones','UbicacionController','listar','activos.ficha.leer'],
    ['POST','/api/v1/ubicaciones','UbicacionController','crear','usuarios.cuenta.administrar'],
    ['GET','/api/v1/categorias','CategoriaActivoController','listar','activos.ficha.leer'],
    ['GET','/api/v1/activos','ActivoController','listar','activos.ficha.leer'],
    ['POST','/api/v1/activos','ActivoController','crear','activos.ficha.crear'],
    ['GET','/api/v1/activos/placa/(?P<placa>[A-Za-z0-9._-]+)','ActivoController','buscarPorPlaca','activos.ficha.leer'],
    ['GET','/api/v1/activos/(?P<id>\d+)','ActivoController','mostrar','activos.ficha.leer'],
    ['PATCH','/api/v1/activos/(?P<id>\d+)/estado','ActivoController','cambiarEstado','activos.ficha.actualizar'],
    ['GET','/api/v1/activos/(?P<id>\d+)/asignacion-vigente','AsignacionController','vigente','activos.ficha.leer'],
    ['POST','/api/v1/activos/(?P<id>\d+)/asignaciones','AsignacionController','crear','asignaciones.asignacion.crear'],
    ['POST','/api/v1/asignaciones/(?P<id>\d+)/devolucion','DevolucionController','desdeAsignacion','asignaciones.asignacion.crear'],
    ['POST','/api/v1/traslados','SolicitudTrasladoController','crear','activos.ficha.actualizar'],
    ['GET','/api/v1/traslados/(?P<id>\d+)','SolicitudTrasladoController','mostrar','activos.ficha.leer'],
    ['POST','/api/v1/traslados/(?P<id>\d+)/decision','SolicitudTrasladoController','resolver','traslados.solicitud.aprobar'],
    ['POST','/api/v1/traslados/(?P<id>\d+)/completar','MovimientoActivoController','completarTraslado','traslados.solicitud.aprobar'],
    ['POST','/api/v1/prestamos','PrestamoController','crear','activos.ficha.actualizar'],
    ['GET','/api/v1/prestamos/(?P<id>\d+)','PrestamoController','mostrar','activos.ficha.leer'],
    ['POST','/api/v1/prestamos/(?P<id>\d+)/devolucion','DevolucionController','desdePrestamo','activos.ficha.actualizar'],
    ['POST','/api/v1/novedades','NovedadController','crear','activos.ficha.actualizar'],
    ['POST','/api/v1/activos/(?P<id>\d+)/mantenimientos','MantenimientoController','crear','activos.ficha.actualizar'],
    ['POST','/api/v1/inventarios','InventarioController','crear','inventarios.jornada.crear'],
    ['GET','/api/v1/inventarios/(?P<id>\d+)','InventarioController','mostrar','inventarios.jornada.crear'],
    ['POST','/api/v1/inventarios/(?P<id>\d+)/iniciar','InventarioController','iniciar','inventarios.jornada.crear'],
    ['POST','/api/v1/inventarios/(?P<id>\d+)/detalles','InventarioDetalleController','crear','inventarios.detalle.verificar'],
    ['POST','/api/v1/inventarios/(?P<id>\d+)/detalles/(?P<detalleId>\d+)/diferencias','DiferenciaInventarioController','crear','inventarios.detalle.verificar'],
    ['POST','/api/v1/inventarios/(?P<id>\d+)/cerrar','InventarioController','cerrar','inventarios.jornada.crear'],
    ['POST','/api/v1/activos/(?P<id>\d+)/bajas','BajaActivoController','solicitar','activos.ficha.dar_baja'],
    ['GET','/api/v1/notificaciones','NotificacionController','listar',null],
    ['PATCH','/api/v1/notificaciones/(?P<id>\d+)/leida','NotificacionController','marcarLeida',null],
    ['GET','/api/v1/reportes/activos','ReporteController','activos','reportes.informe.exportar'],
    ['GET','/api/v1/procesos-seleccion/unidades','ProcesoSeleccionController','unidades','seleccion.proceso.leer'],
    ['GET','/api/v1/procesos-seleccion','ProcesoSeleccionController','listar','seleccion.proceso.leer'],
    ['POST','/api/v1/procesos-seleccion','ProcesoSeleccionController','crear','seleccion.proceso.administrar'],
    ['PATCH','/api/v1/procesos-seleccion/(?P<id>\\d+)/estado','ProcesoSeleccionController','cambiarEstado','seleccion.proceso.administrar'],
    ['GET','/api/v1/procesos-seleccion/(?P<id>\\d+)/postulaciones','ProcesoSeleccionController','postulaciones','seleccion.proceso.leer'],
    ['POST','/api/v1/procesos-seleccion/(?P<id>\\d+)/postulaciones','ProcesoSeleccionController','crearPostulacion','seleccion.proceso.administrar'],
    ['GET','/api/v1/postulaciones/(?P<postulacionId>\\d+)/documentos','DocumentoPostulacionController','listar','seleccion.proceso.leer'],
    ['POST','/api/v1/procesos-seleccion/(?P<id>\\d+)/postulaciones/(?P<postulacionId>\\d+)/documentos','DocumentoPostulacionController','subir','seleccion.proceso.administrar'],
    ['PATCH','/api/v1/documentos-postulacion/(?P<id>\\d+)/revision','DocumentoPostulacionController','revisar','seleccion.postulacion.revisar'],
    ['GET','/api/v1/documentos-postulacion/(?P<id>\\d+)','DocumentoPostulacionController','descargar','seleccion.proceso.leer'],
    ['POST','/api/v1/documentos','DocumentoController','subir','activos.ficha.actualizar'],
    ['GET','/api/v1/documentos/(?P<id>\d+)','DocumentoController','descargar','activos.ficha.leer'],
    ['POST','/api/v1/importaciones','ImportacionController','crear','reportes.informe.exportar'],
    ['GET','/api/v1/importaciones/(?P<id>\d+)','ImportacionController','mostrar','reportes.informe.exportar'],
    ['POST','/api/v1/importaciones-planilla','PlanillaImportacionController','crear','reportes.informe.exportar'],
    ['GET','/api/v1/importaciones-planilla/(?P<id>\d+)','PlanillaImportacionController','mostrar','reportes.informe.exportar'],
    ['GET','/api/v1/auditoria','AuditoriaController','listar','usuarios.cuenta.administrar'],
];

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = $requestPath;
$selected = null;
$params = [];
$allowedMethods = [];
foreach ($routes as [$routeMethod, $pattern, $controller, $action, $permission]) {
    if (preg_match('#^' . $pattern . '/?$#u', $path, $matches) !== 1) continue;
    $allowedMethods[] = $routeMethod;
    if ($method === $routeMethod) {
        $selected = [$controller, $action, $permission];
        foreach ($matches as $key => $value) if (is_string($key)) $params[$key] = rawurldecode($value);
        break;
    }
}

if ($selected === null) {
    http_response_code($allowedMethods === [] ? 404 : 405);
    if ($allowedMethods !== []) header('Allow: ' . implode(', ', array_unique($allowedMethods)));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => $allowedMethods === [] ? 'NOT_FOUND' : 'METHOD_NOT_ALLOWED', 'message' => $allowedMethods === [] ? 'Ruta no encontrada.' : 'Método HTTP no permitido.']], JSON_UNESCAPED_UNICODE);
    exit;
}

[$controllerName, $action, $permission] = $selected;
try {
    if ($controllerName !== 'SesionController' || $action !== 'crear') {
        if (!isset($_SESSION['user_id']) || !is_int($_SESSION['user_id'])) {
            throw new ApiException(401, 'AUTH_REQUIRED', 'Debes iniciar sesión.');
        }
    }
    if ($method !== 'GET' && !($controllerName === 'SesionController' && $action === 'crear')) {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';
        if (!is_string($provided) || !is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
            throw new ApiException(403, 'CSRF_INVALID', 'Token CSRF ausente o inválido.');
        }
    }
    $pdo = (new Database())->connect();
    if ($permission !== null && !(new AutorizacionDAO($pdo))->tienePermiso((int) $_SESSION['user_id'], ...explode('.', $permission))) {
        throw new ApiException(403, 'PERMISSION_DENIED', 'No tienes permiso para esta operación.');
    }
    $class = 'App\\Controller\\' . $controllerName;
    if (!class_exists($class)) throw new RuntimeException('Controller class missing.');
    $instance = new $class($pdo);
    if (!is_callable([$instance, $action])) throw new RuntimeException('Controller action missing.');
    $instance->{$action}($params);
} catch (ApiException $error) {
    http_response_code($error->status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => $error->errorCode, 'message' => $error->getMessage()]], JSON_UNESCAPED_UNICODE);
} catch (\PDOException $error) {
    error_log('API database failure: ' . $error->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Ocurrió un error interno.']], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $error) {
    error_log('API failure: ' . $error->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Ocurrió un error interno.']], JSON_UNESCAPED_UNICODE);
}
