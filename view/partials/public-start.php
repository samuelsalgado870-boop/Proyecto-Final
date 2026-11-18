<?php
declare(strict_types=1);
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
<body class="<?= $e($bodyClass ?? 'public-body') ?>" data-api-base="<?= $e($apiBase) ?>" data-base-path="<?= $e($basePath) ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?php if (($showPublicHeader ?? true) !== false): ?>
<header class="public-header">
    <a class="brand" href="<?= $e($basePath) ?>/" aria-label="CECAR, inicio">
        <img class="brand-logo brand-logo--header" src="<?= $e($assetBase) ?>/images/logo-cecar.webp" alt="CECAR - Corporación Universitaria del Caribe">
    </a>
    <a class="button button--outline" href="<?= $e($basePath) ?>/login">Ingresar <span aria-hidden="true">→</span></a>
</header>
<?php endif; ?>
