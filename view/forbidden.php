<?php require __DIR__ . '/partials/app-start.php'; ?>
<section class="empty-state empty-state--page" id="contenido"><span class="empty-icon" aria-hidden="true">⌑</span><strong>Tu cuenta no tiene permiso para esta sección</strong><p>Solicita acceso al administrador del sistema o vuelve al panel principal.</p><a class="button button--outline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app">Volver al panel</a></section>
<?php require __DIR__ . '/partials/app-end.php'; ?>
