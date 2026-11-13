<?php require __DIR__ . '/partials/app-start.php'; ?>
<div class="report-page">
    <section class="page-intro"><div><span class="eyebrow">CONSULTA Y TRAZABILIDAD</span><h2>Reportes y archivos</h2><p>Filtra los activos autorizados, importa datos y consulta la bitácora.</p></div></section>

    <section class="report-stat-grid" aria-label="Resumen de activos del reporte">
        <article class="report-stat"><span class="report-stat-icon report-stat-icon--green" aria-hidden="true">▦</span><span><small>Activos consultados</small><strong data-report-stat="total">—</strong></span></article>
        <article class="report-stat"><span class="report-stat-icon report-stat-icon--lime" aria-hidden="true">✓</span><span><small>Disponibles</small><strong data-report-stat="disponibles">—</strong></span></article>
        <article class="report-stat"><span class="report-stat-icon report-stat-icon--blue" aria-hidden="true">♙</span><span><small>Asignados</small><strong data-report-stat="asignados">—</strong></span></article>
        <article class="report-stat"><span class="report-stat-icon report-stat-icon--gold" aria-hidden="true">◷</span><span><small>En revisión / mant.</small><strong data-report-stat="revision">—</strong></span></article>
    </section>

    <section class="panel report-main-panel" aria-labelledby="report-title">
        <div class="panel-heading report-panel-heading"><div><span class="eyebrow eyebrow--light">INFORME DE ACTIVOS</span><h2 id="report-title">Consulta por filtros</h2></div><button class="button button--outline" type="button" data-export-report disabled>Descargar CSV</button></div>
        <form class="filter-grid filter-grid--reports report-filter-grid" data-report-form>
            <div class="field"><label for="report-site">Sede</label><select id="report-site" name="sedeId"><option value="">Todas</option></select></div>
            <div class="field"><label for="report-dependency">Dependencia</label><select id="report-dependency" name="dependenciaId"><option value="">Todas</option></select></div>
            <div class="field"><label for="report-location">Ubicación</label><select id="report-location" name="ubicacionId"><option value="">Todas</option></select></div>
            <div class="field"><label for="report-category">Categoría</label><select id="report-category" name="categoriaId"><option value="">Todas</option></select></div>
            <div class="field"><label for="report-state">Estado</label><select id="report-state" name="estado"><option value="">Todos</option><option>REGISTRADO</option><option>DISPONIBLE</option><option>ASIGNADO</option><option>EN_MANTENIMIENTO</option><option>EN_PRESTAMO</option><option>EN_REVISION</option><option>DADO_DE_BAJA</option><option>INACTIVO</option></select></div>
            <div class="field field--button"><button class="button button--primary" type="submit">Aplicar filtros</button></div>
        </form>
        <div class="report-results-bar"><span data-report-summary aria-live="polite">Aplica filtros para generar el reporte.</span><span class="report-limit">Máximo 500 registros por consulta</span></div>
        <div class="table-wrap"><table><thead><tr><th scope="col">Placa</th><th scope="col">Descripción</th><th scope="col">Estado</th><th scope="col">Dependencia</th><th scope="col">Ubicación</th></tr></thead><tbody data-report-table><tr><td colspan="5" class="table-placeholder">Todavía no hay resultados.</td></tr></tbody></table></div>
    </section>

    <div class="report-tools-grid">
        <section class="panel" aria-labelledby="import-title" data-requires-permission="reportes.informe.exportar"><div class="panel-heading"><div><span class="eyebrow">CARGA CONTROLADA</span><h2 id="import-title">Importar activos desde CSV</h2></div></div><p>El archivo debe incluir <code>placa, descripcion, categoria_id, ubicacion_id, dependencia_id</code>. También admite serial, marca, modelo y estado.</p><form class="form-stack" data-import-form enctype="multipart/form-data"><div class="field"><label for="csv-file">Archivo CSV</label><input id="csv-file" name="archivo" type="file" accept=".csv,text/csv" required><small class="field-hint">Tamaño máximo: 20 MB.</small></div><div class="form-message" data-form-message role="alert"></div><button class="button button--primary" type="submit">Validar e importar</button></form><div class="inline-form import-lookup"><div class="field"><label for="import-id">Consultar importación por ID</label><input id="import-id" type="number" min="1" data-import-id></div><button type="button" class="button button--outline" data-load-import>Consultar</button></div><div data-import-result aria-live="polite"></div></section>
        <section class="panel" aria-labelledby="document-title" data-requires-permission="activos.ficha.actualizar"><div class="panel-heading"><div><span class="eyebrow">ARCHIVO PRIVADO</span><h2 id="document-title">Cargar documento</h2></div></div><p>Archivos PDF, PNG o JPEG hasta 10 MB. La relación con entidades se limita a la estructura disponible en la base.</p><form class="form-stack" data-document-form enctype="multipart/form-data"><div class="field"><label for="document-file">Seleccionar archivo</label><input id="document-file" name="archivo" type="file" accept="application/pdf,image/png,image/jpeg" required><small class="field-hint">PDF, PNG o JPG · máximo 10 MB.</small></div><div class="form-message" data-form-message role="alert"></div><button class="button button--outline" type="submit">Cargar documento</button></form><div data-document-result aria-live="polite"></div></section>
    </div>
    <section class="panel" aria-labelledby="audit-title" data-requires-permission="usuarios.cuenta.administrar"><div class="panel-heading"><div><span class="eyebrow">TRAZABILIDAD</span><h2 id="audit-title">Eventos de auditoría</h2></div><button type="button" class="button button--quiet" data-load-audit>Cargar eventos</button></div><div class="table-wrap"><table><thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Entidad</th><th scope="col">Acción</th><th scope="col">Detalle</th></tr></thead><tbody data-audit-table><tr><td colspan="5" class="table-placeholder">La bitácora se carga al solicitarla.</td></tr></tbody></table></div></section>
</div>
<?php require __DIR__ . '/partials/app-end.php'; ?>
