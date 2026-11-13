<?php require __DIR__ . '/partials/app-start.php'; ?>
<section class="page-intro"><div><span class="eyebrow">REGISTRO INSTITUCIONAL</span><h2>Consulta y administra los bienes</h2><p>Filtra los activos visibles y revisa su ficha dentro de tu ámbito.</p></div><a class="button button--primary" href="#crear-activo"><span aria-hidden="true">＋</span> Registrar activo</a></section>
<section class="panel filter-panel" aria-labelledby="filters-title"><div class="panel-heading"><div><span class="eyebrow">BÚSQUEDA</span><h2 id="filters-title">Filtrar activos</h2></div><button type="button" class="button button--quiet" data-clear-asset-filters>Limpiar filtros</button></div>
    <form class="filter-grid" data-asset-filter-form><div class="field"><label for="filter-placa">Buscar por placa</label><input id="filter-placa" name="placa" type="search" maxlength="50" placeholder="Ej. AF-1001"></div>
        <div class="field"><label for="filter-estado">Estado</label><select id="filter-estado" name="estado"><option value="">Todos los estados</option><option>REGISTRADO</option><option>DISPONIBLE</option><option>ASIGNADO</option><option>TRASLADO_SOLICITADO</option><option>EN_TRASLADO</option><option>EN_MANTENIMIENTO</option><option>EN_PRESTAMO</option><option>DEVUELTO</option><option>EN_REVISION</option><option>DADO_DE_BAJA</option><option>INACTIVO</option></select></div>
        <div class="field"><label for="filter-categoria">Categoría</label><select id="filter-categoria" name="categoriaId"><option value="">Todas las categorías</option></select></div>
        <div class="field field--button"><button class="button button--primary" type="submit">Buscar activos</button></div>
    </form>
</section>
<section class="panel" aria-labelledby="assets-title"><div class="panel-heading"><div><span class="eyebrow">INVENTARIO</span><h2 id="assets-title">Activos</h2></div><span class="result-count" data-assets-count aria-live="polite">Preparando consulta…</span></div>
    <div class="table-wrap"><table><thead><tr><th scope="col">Placa</th><th scope="col">Descripción</th><th scope="col">Serial</th><th scope="col">Categoría</th><th scope="col">Estado</th><th scope="col">Ubicación</th><th scope="col">Acciones</th></tr></thead><tbody data-assets-table><tr><td colspan="7"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando…</div></td></tr></tbody></table></div>
</section>
<section class="panel form-panel" id="crear-activo" aria-labelledby="create-asset-title" data-requires-permission="activos.ficha.crear"><div class="panel-heading"><div><span class="eyebrow">NUEVO REGISTRO</span><h2 id="create-asset-title">Registrar activo</h2></div><span class="step-badge">01</span></div>
    <form class="form-grid" data-create-asset-form novalidate>
        <div class="field"><label for="asset-placa">Placa <span aria-hidden="true">*</span></label><input id="asset-placa" name="placa" required maxlength="50" autocomplete="off"></div>
        <div class="field"><label for="asset-fecha">Fecha de asignación de placa</label><input id="asset-fecha" name="fechaAsignacionPlaca" type="date"></div>
        <div class="field"><label for="asset-serial">Serial</label><input id="asset-serial" name="serial" maxlength="100"></div>
        <div class="field"><label for="asset-categoria">Categoría <span aria-hidden="true">*</span></label><select id="asset-categoria" name="categoriaId" required><option value="">Selecciona categoría</option></select></div>
        <div class="field field--span-2"><label for="asset-descripcion">Descripción <span aria-hidden="true">*</span></label><textarea id="asset-descripcion" name="descripcion" required maxlength="5000" rows="3"></textarea></div>
        <div class="field"><label for="asset-marca">Marca</label><input id="asset-marca" name="marca" maxlength="100"></div>
        <div class="field"><label for="asset-modelo">Modelo</label><input id="asset-modelo" name="modelo" maxlength="100"></div>
        <div class="field"><label for="asset-estado">Estado inicial <span aria-hidden="true">*</span></label><select id="asset-estado" name="estado" required><option value="REGISTRADO">Registrado</option><option value="DISPONIBLE">Disponible</option></select></div>
        <div class="field"><label for="asset-dependencia">Dependencia <span aria-hidden="true">*</span></label><select id="asset-dependencia" name="dependenciaId" required><option value="">Selecciona dependencia</option></select></div>
        <div class="field"><label for="asset-ubicacion">Ubicación <span aria-hidden="true">*</span></label><select id="asset-ubicacion" name="ubicacionId" required><option value="">Selecciona ubicación</option></select></div>
        <div class="form-actions field--span-2"><div class="form-message" data-form-message role="alert" aria-live="assertive"></div><button class="button button--primary" type="submit">Guardar activo <span aria-hidden="true">→</span></button></div>
    </form>
</section>
<dialog class="detail-dialog" data-asset-dialog aria-labelledby="asset-dialog-title"><div class="dialog-head"><div><span class="eyebrow">FICHA DEL BIEN</span><h2 id="asset-dialog-title">Detalle de activo</h2></div><button class="icon-button" type="button" aria-label="Cerrar detalle" data-close-dialog>×</button></div><div class="dialog-content" data-asset-detail><div class="loading-state">Cargando detalle…</div></div><div class="dialog-actions"><a class="button button--primary" data-asset-operations href="#">Ver operaciones <span aria-hidden="true">→</span></a><button class="button button--quiet" type="button" data-close-dialog>Cerrar</button></div></dialog>
<?php require __DIR__ . '/partials/app-end.php'; ?>
