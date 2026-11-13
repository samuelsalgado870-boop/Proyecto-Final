<?php require __DIR__ . '/partials/public-start.php'; ?>
<main class="landing" id="contenido">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-copy">
            <span class="pill pill--light"><span class="status-dot" aria-hidden="true"></span> Nueva plataforma</span>
            <h1 id="hero-title">Toda tu gestión<br><span>de activos</span><br>en un solo lugar</h1>
            <p>Una plataforma para organizar los bienes institucionales, registrar sus movimientos y consultar cada inventario con trazabilidad.</p>
            <div class="hero-actions">
                <a class="button button--lime" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login"><span aria-hidden="true">⇥</span> Iniciar sesión</a>
                <a class="button button--ghost" href="#funciones"><span class="play-icon" aria-hidden="true">▶</span> Ver cómo funciona</a>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="visual-window">
                <div class="visual-window-top"><i></i><i></i><i></i></div>
                <div class="visual-row"><span class="visual-icon">✓</span><span class="visual-line visual-line--long"></span><b>Aprobado</b></div>
                <div class="visual-row"><span class="visual-icon">◇</span><span class="visual-line visual-line--mid"></span><b>En proceso</b></div>
                <div class="visual-row"><span class="visual-icon">▤</span><span class="visual-line visual-line--long"></span></div>
                <div class="visual-row"><span class="visual-icon">↗</span><span class="visual-line visual-line--mid"></span><b>Registrado</b></div>
            </div>
        </div>
        <div class="hero-glow" aria-hidden="true"></div>
    </section>
    <section class="landing-features" id="funciones" aria-labelledby="features-title">
        <h2 id="features-title" class="sr-only">Funciones de la plataforma</h2>
        <div class="feature-item"><span aria-hidden="true">⌘</span><strong>Activos organizados</strong></div>
        <div class="feature-item"><span aria-hidden="true">✓</span><strong>Acceso por permisos</strong></div>
        <div class="feature-item"><span aria-hidden="true">◷</span><strong>Inventarios trazables</strong></div>
        <div class="feature-item"><span aria-hidden="true">↗</span><strong>Reportes y seguimiento</strong></div>
    </section>
</main>
<?php require __DIR__ . '/partials/public-end.php'; ?>
