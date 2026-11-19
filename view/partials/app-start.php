<?php
declare(strict_types=1);
/** @var string $pageTitle @var string $assetBase @var string $apiBase @var string $basePath @var string $activePage */
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2f1b">
    <title><?= $e($pageTitle) ?> · Gestión de activos</title>
    <link rel="stylesheet" href="<?= $e($assetBase) ?>/css/app.css?v=<?= (string) (filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?: 1) ?>">
    <script defer src="<?= $e($assetBase) ?>/js/app.js?v=<?= (string) (filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?: 1) ?>"></script>
</head>
<body class="app-body" data-app-root data-api-base="<?= $e($apiBase) ?>" data-base-path="<?= $e($basePath) ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="app-layout">
    <aside class="sidebar" id="sidebar" aria-label="Navegación principal">
        <a class="brand brand--sidebar" href="<?= $e($basePath) ?>/app" aria-label="CECAR, inicio">
            <img class="brand-logo brand-logo--sidebar" src="<?= $e($assetBase) ?>/images/logo-cecar.webp" alt="CECAR - Corporación Universitaria del Caribe">
        </a>
        <p class="sidebar-label">MENÚ PRINCIPAL</p>
        <nav class="side-nav">
            <?php
            $items = [
                ['/app', 'Dashboard', '⌂'],
                ['/app/activos', 'Activos', '▦'],
                ['/app/operaciones', 'Operaciones', '⇄'],
                ['/app/inventarios', 'Inventarios', '☷'],
                ['/app/procesos-seleccion', 'Procesos de selección', '▤'],
                ['/app/administracion', 'Administración', '⚙'],
                ['/app/reportes', 'Reportes y archivos', '▤'],
            ];
            foreach ($items as [$href, $label, $icon]):
                $current = $activePage === $href;
            ?>
                <a class="side-link<?= $current ? ' is-active' : '' ?>" href="<?= $e($basePath . $href) ?>"<?= $current ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><?= $e($icon) ?></span><span><?= $e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-profile"><span class="avatar" aria-hidden="true">U</span><span><strong data-user-name>Usuario</strong><small data-user-role>Sesión activa</small></span></div>
            <button class="side-link side-button" type="button" data-logout><span class="nav-icon" aria-hidden="true">↪</span><span>Cerrar sesión</span></button>
        </div>
    </aside>
    <div class="app-main-wrap">
        <header class="topbar">
            <button class="icon-button menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar" data-menu-toggle><span aria-hidden="true">☰</span></button>
            <div class="topbar-title"><span class="eyebrow">SISTEMA ADMINISTRATIVO</span><h1><?= $e($pageTitle) ?></h1></div>
            <div class="topbar-right"><span class="status-dot" aria-hidden="true"></span><span class="online-label">Conexión segura</span><span class="avatar avatar--top" aria-hidden="true">U</span></div>
        </header>
        <main class="app-content" id="contenido">
