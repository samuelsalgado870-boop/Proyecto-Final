<?php require __DIR__ . '/partials/app-start.php'; ?>
<section class="page-intro">
    <div>
        <span class="eyebrow">BASE INSTITUCIONAL CECAR</span>
        <h2>Activos de la planilla oficial</h2>
        <p>Consulta los datos conservados de la hoja Lectura. Las filas y sus valores de origen se muestran sin asignarles catálogos que no aparecen en el archivo.</p>
    </div>
</section>
<section class="panel filter-panel" aria-labelledby="filters-title">
    <div class="panel-heading">
        <div><span class="eyebrow">BÚSQUEDA</span><h2 id="filters-title">Buscar en la base oficial</h2></div>
        <button type="button" class="button button--quiet" data-clear-asset-filters>Limpiar</button>
    </div>
    <form class="filter-grid" data-asset-filter-form>
        <div class="field field--span-2"><label for="filter-source-query">Placa, descripción, lote, ubicación, centro de costo o estado</label><input id="filter-source-query" name="q" type="search" maxlength="100" placeholder="Escribe un valor para buscar"></div>
        <div class="field field--button"><button class="button button--primary" type="submit">Buscar</button></div>
    </form>
</section>
<section class="panel" aria-labelledby="assets-title">
    <div class="panel-heading">
        <div><span class="eyebrow">FUENTE: PLANILLA INSTITUCIONAL</span><h2 id="assets-title">Registros de activos</h2></div>
        <span class="result-count" data-assets-count aria-live="polite">Preparando consulta…</span>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th scope="col">ID activo</th><th scope="col">Fila</th><th scope="col">Placa</th><th scope="col">Descripción</th><th scope="col">Lote</th><th scope="col">Estado de origen</th><th scope="col">Centro de costo</th><th scope="col">Ubicación de origen</th><th scope="col">Resultado</th><th scope="col">Acci&#xF3;n</th></tr></thead>
        <tbody data-assets-table><tr><td colspan="10"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando base oficial…</div></td></tr></tbody>
    </table></div>
    <div class="inline-form" data-source-pagination aria-label="Paginación de registros"></div>
</section>
<dialog class="detail-dialog" data-source-detail-dialog aria-labelledby="source-detail-title">
    <div class="dialog-head"><div><span class="eyebrow">FILA ORIGINAL DEL EXCEL</span><h2 id="source-detail-title" data-source-detail-title>Detalle del activo</h2></div><button type="button" class="icon-button" data-source-detail-close aria-label="Cerrar detalle">×</button></div>
    <div class="dialog-content" data-source-detail-content><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando detalle…</div></div>
    <div class="dialog-actions"><a class="button button--primary" data-source-open-operations href="#" hidden>Usar en operaciones</a><button type="button" class="button button--outline" data-source-detail-close>Cerrar</button></div>
</dialog>
<?php require __DIR__ . '/partials/app-end.php'; ?>
