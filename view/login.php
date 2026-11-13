<?php
$basePath = isset($basePath) && is_string($basePath) ? $basePath : '';
require __DIR__ . '/partials/public-start.php';
?>
<main class="login-layout" id="contenido">
    <section class="login-panel" aria-labelledby="login-title">
        <a class="back-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/" aria-label="Volver al inicio">← Volver al inicio</a>
        <div class="login-intro"><span class="eyebrow">ACCESO SEGURO</span><h1 id="login-title">Bienvenido de nuevo</h1><p>Ingresa con tu cuenta institucional para continuar.</p></div>
        <form class="form-stack" data-login-form novalidate>
            <div class="field"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" autocomplete="username" required maxlength="150" placeholder="nombre@institucion.edu.co"><small class="field-hint">Usa el correo asociado a tu cuenta.</small></div>
            <div class="field"><label for="contrasena">Contraseña</label><input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required maxlength="255" placeholder="Ingresa tu contraseña"></div>
            <div class="form-message" data-form-message role="alert" aria-live="assertive"></div>
            <button class="button button--primary button--wide" type="submit"><span>Iniciar sesión</span><span aria-hidden="true">→</span></button>
        </form>
        <p class="login-footnote"><span aria-hidden="true">▣</span> Tu acceso está protegido mediante sesión segura.</p>
    </section>
    <aside class="login-aside" aria-label="Información del sistema"><div class="aside-mark" aria-hidden="true"><span class="brand-mark brand-mark--large"><span></span></span></div><span class="eyebrow eyebrow--light">GESTIÓN RESPONSABLE</span><h2>Información confiable.<br>Decisiones oportunas.</h2><p>Consulta los bienes y sigue sus movimientos desde un mismo sistema.</p><div class="aside-decoration" aria-hidden="true"><i></i><i></i><i></i></div></aside>
</main>
<?php require __DIR__ . '/partials/public-end.php'; ?>
