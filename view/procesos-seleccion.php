<?php require __DIR__ . '/partials/app-start.php'; ?>
<div class="selection-page" data-selection-page>
    <section class="page-intro selection-intro"><div><span class="eyebrow">GESTIÓN DE PERSONAS</span><h2>Procesos de selección</h2><p>Convocatorias, postulaciones y documentos organizados en un solo lugar.</p></div><button class="button button--primary" type="button" data-selection-toggle-process data-requires-permission="seleccion.proceso.administrar">＋ Crear proceso</button></section>

    <section class="selection-summary" aria-label="Resumen de postulaciones">
        <article class="selection-stat"><span class="selection-stat-icon">♙</span><span><small>Postulaciones</small><strong data-selection-stat="postulaciones">0</strong></span></article>
        <article class="selection-stat"><span class="selection-stat-icon selection-stat-icon--lime">▤</span><span><small>Documentos cargados</small><strong data-selection-stat="documentos">0</strong></span></article>
        <article class="selection-stat"><span class="selection-stat-icon selection-stat-icon--green">✓</span><span><small>Aprobados</small><strong data-selection-stat="aprobados">0</strong></span></article>
        <article class="selection-stat"><span class="selection-stat-icon selection-stat-icon--gold">◷</span><span><small>Pendientes de revisión</small><strong data-selection-stat="pendientes">0</strong></span></article>
    </section>

    <section class="panel selection-create-panel" data-selection-create-panel data-requires-permission="seleccion.proceso.administrar" hidden aria-labelledby="selection-create-title">
        <div class="panel-heading"><div><span class="eyebrow">NUEVA CONVOCATORIA</span><h2 id="selection-create-title">Crear proceso</h2></div></div>
        <form class="form-grid" data-selection-process-form>
            <div class="field field--span-2"><label for="selection-process-name">Nombre del proceso</label><input id="selection-process-name" name="nombre" maxlength="180" required></div>
            <div class="field"><label for="selection-process-unit">Unidad</label><select id="selection-process-unit" name="dependenciaId" required><option value="">Selecciona una unidad</option></select></div>
            <div class="field"><label for="selection-process-status">Estado inicial</label><select id="selection-process-status" name="estado"><option value="BORRADOR">Borrador</option><option value="ABIERTO">Abierto</option></select></div>
            <div class="field"><label for="selection-open-date">Fecha de apertura</label><input id="selection-open-date" name="fechaApertura" type="date"></div>
            <div class="field"><label for="selection-close-date">Fecha de cierre</label><input id="selection-close-date" name="fechaCierre" type="date"></div>
            <div class="form-message field--span-2" data-form-message role="alert"></div>
            <div class="form-actions field--span-2"><button class="button button--primary" type="submit">Guardar proceso</button><button class="button button--quiet" type="button" data-selection-cancel-process>Cancelar</button></div>
        </form>
    </section>

    <section class="panel selection-create-panel" data-selection-application-panel data-requires-permission="seleccion.proceso.administrar" hidden aria-labelledby="selection-application-title">
        <div class="panel-heading"><div><span class="eyebrow">POSTULACIÓN</span><h2 id="selection-application-title">Registrar usuario en <span data-selection-current-process></span></h2></div></div>
        <form class="form-grid" data-selection-application-form>
            <div class="field"><label for="selection-candidate-name">Nombre</label><input id="selection-candidate-name" name="nombre" maxlength="150" required></div>
            <div class="field"><label for="selection-candidate-email">Correo</label><input id="selection-candidate-email" name="correo" type="email" maxlength="190" required></div>
            <div class="form-message field--span-2" data-form-message role="alert"></div>
            <div class="form-actions field--span-2"><button class="button button--primary" type="submit">Registrar postulación</button><button class="button button--quiet" type="button" data-selection-cancel-application>Cancelar</button></div>
        </form>
    </section>

    <section class="panel selection-process-panel" aria-labelledby="selection-list-title">
        <div class="selection-panel-heading"><div><span class="eyebrow eyebrow--light">SEGUIMIENTO</span><h2 id="selection-list-title">Mis documentos por proceso</h2></div><span class="selection-heading-mark" aria-hidden="true">▤</span></div>
        <div class="selection-filters">
            <label><span class="sr-only">Proceso</span><select data-selection-process-filter><option value="">Selecciona un proceso</option></select></label>
            <label><span class="sr-only">Unidad</span><select data-selection-unit-filter><option value="">Todas las unidades</option></select></label>
            <label><span class="sr-only">Estado del proceso</span><select data-selection-state-filter><option value="">Todos los estados</option><option value="BORRADOR">Borrador</option><option value="ABIERTO">Abierto</option><option value="EN_REVISION">En revisión</option><option value="CERRADO">Cerrado</option><option value="FINALIZADO">Finalizado</option></select></label>
            <label class="selection-search"><span class="sr-only">Buscar usuario o proceso</span><input type="search" placeholder="Buscar usuario o proceso…" data-selection-search></label>
            <button type="button" class="button button--primary" data-selection-toggle-application data-requires-permission="seleccion.proceso.administrar" disabled>＋ Postulante</button>
        </div>
        <p class="selection-feedback" data-selection-feedback role="status" aria-live="polite"></p>
        <div class="table-wrap selection-table-preview"><table><thead><tr><th scope="col">Usuario</th><th scope="col">Proceso</th><th scope="col">Documentos cargados</th><th scope="col">Aprobados</th><th scope="col">Pendientes por revisión</th><th scope="col">Rechazados</th><th scope="col">Acción</th></tr></thead><tbody data-selection-table><tr><td colspan="7"><div class="selection-empty-state"><span class="selection-empty-icon" aria-hidden="true">⌕</span><strong>Cargando procesos…</strong></div></td></tr></tbody></table></div>
    </section>
</div>

<dialog class="detail-dialog selection-doc-dialog" data-selection-doc-dialog aria-labelledby="selection-doc-title">
    <div class="dialog-head"><div><span class="eyebrow">ARCHIVO PRIVADO</span><h2 id="selection-doc-title" data-selection-doc-title>Documentos de postulación</h2></div><button class="icon-button" type="button" aria-label="Cerrar" data-selection-close-docs>×</button></div>
    <div class="dialog-content"><div data-selection-doc-list></div>
        <form class="form-stack selection-upload-form" data-selection-document-form data-requires-permission="seleccion.proceso.administrar" enctype="multipart/form-data">
            <div class="field"><label for="selection-doc-type">Tipo de documento de identidad</label><select id="selection-doc-type" name="tipoDocumento" required><option value="">Selecciona un tipo de documento</option><option value="CEDULA_CIUDADANIA">Cédula de Ciudadanía</option><option value="CEDULA_EXTRANJERIA">Cédula de Extranjería</option><option value="PASAPORTE">Pasaporte</option></select></div>
            <div class="field"><label for="selection-doc-file">Archivo (PDF, PNG o JPEG; máximo 10 MB)</label><input id="selection-doc-file" name="archivo" type="file" accept="application/pdf,image/png,image/jpeg" required></div>
            <div class="form-message" data-form-message role="alert"></div><button class="button button--primary" type="submit">Cargar documento</button>
        </form>
    </div>
</dialog>
<?php require __DIR__ . '/partials/app-end.php'; ?>
