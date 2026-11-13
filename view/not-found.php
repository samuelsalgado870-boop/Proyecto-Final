<?php require __DIR__ . '/partials/public-start.php'; ?>
<main class="not-found" id="contenido"><span class="eyebrow">ERROR 404</span><h1>No encontramos esta página</h1><p>Puede que el enlace haya cambiado o no esté disponible.</p><a class="button button--primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/">Volver al inicio</a></main>
<?php require __DIR__ . '/partials/public-end.php'; ?>
