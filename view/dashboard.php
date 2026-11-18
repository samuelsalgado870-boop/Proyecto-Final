<?php
/** @var string $basePath */
require __DIR__ . '/partials/app-start.php';
?>
<div class="dashboard-page">
    <section class="welcome-banner" aria-labelledby="dashboard-welcome">
        <div class="welcome-copy">
            <span class="eyebrow eyebrow--light">SISTEMA DE GESTIÓN</span>
            <h2 id="dashboard-welcome">¡Bienvenido<span data-welcome-name></span>!</h2>
            <p>Accede a los módulos desde el menú lateral. Tu sesión está activa y segura.</p>
            <div class="welcome-tags" aria-label="Módulos disponibles">
                <span><i aria-hidden="true">▦</i> Activos</span>
                <span><i aria-hidden="true">⌖</i> Inventarios</span>
                <span><i aria-hidden="true">♙</i> Reportes</span>
            </div>
        </div>
        <div class="dashboard-user-mark" aria-hidden="true"><span>♙</span></div>
    </section>

    <section class="metric-grid dashboard-module-grid" aria-label="Módulos principales">
        <a class="metric-card dashboard-module-card" data-requires-permission="usuarios.cuenta.administrar" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/administracion">
            <span class="metric-icon metric-icon--green" aria-hidden="true">♙</span><span class="metric-label">Gestión completa</span><strong>Usuarios</strong><small>Cuentas y permisos</small>
        </a>
        <a class="metric-card dashboard-module-card" data-requires-permission="activos.ficha.leer" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/activos">
            <span class="metric-icon metric-icon--green" aria-hidden="true">▦</span><span class="metric-label">Control institucional</span><strong>Activos</strong><small>Consulta y seguimiento</small>
        </a>
        <a class="metric-card dashboard-module-card" data-requires-permission="inventarios.jornada.crear" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/inventarios">
            <span class="metric-icon metric-icon--green" aria-hidden="true">☷</span><span class="metric-label">Conteos y novedades</span><strong>Inventarios</strong><small>Jornadas y diferencias</small>
        </a>
        <a class="metric-card dashboard-module-card" data-requires-permission="reportes.informe.exportar" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/reportes">
            <span class="metric-icon metric-icon--green" aria-hidden="true">⌁</span><span class="metric-label">Consulta y análisis</span><strong>Reportes</strong><small>Resultados y archivos</small>
        </a>
    </section>

    <section class="panel dashboard-quick" aria-labelledby="quick-title">
        <div class="panel-heading"><div><span class="eyebrow">ATAJOS</span><h2 id="quick-title">Acciones rápidas</h2></div></div>
        <div class="quick-grid dashboard-action-grid">
            <a class="dashboard-action dashboard-action--green" data-requires-permission="inventarios.jornada.crear" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/inventarios#crear-inventario"><span aria-hidden="true">⊕</span> Iniciar inventario</a>
            <a class="dashboard-action dashboard-action--forest" data-requires-permission="activos.ficha.crear" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/activos#crear-activo"><span aria-hidden="true">▣</span> Registrar activo</a>
            <a class="dashboard-action dashboard-action--blue" data-requires-permission="reportes.informe.exportar" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/reportes"><span aria-hidden="true">▤</span> Generar reporte</a>
        </div>
    </section>

    <section class="panel dashboard-assets" aria-labelledby="recent-assets-title">
        <div class="panel-heading"><div><span class="eyebrow">CONSULTA RÁPIDA</span><h2 id="recent-assets-title">Activos visibles</h2></div><a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/app/activos">Ver inventario <span aria-hidden="true">→</span></a></div>
        <div class="table-wrap"><table><thead><tr><th scope="col">Placa</th><th scope="col">Descripción</th><th scope="col">Estado</th><th scope="col">Ubicación</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead><tbody data-dashboard-assets><tr><td colspan="5"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando activos…</div></td></tr></tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/partials/app-end.php'; ?>
