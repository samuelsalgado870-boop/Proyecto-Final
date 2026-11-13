(() => {
    'use strict';

    const root = document.querySelector('[data-app-root]') || document.body;
    const apiBase = root.dataset.apiBase || '/api/v1';
    const basePath = root.dataset.basePath || '';
    const $ = (selector, scope = document) => scope.querySelector(selector);
    const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
    let session = null;
    let sessionPromise = null;
    let csrfToken = '';
    let activeInventoryId = null;
    let currentReportRows = [];
    let activeOperationAsset = null;
    const catalogs = { categorias: [], dependencias: [], ubicaciones: [], sedes: [], roles: [] };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
    const asOptionalNumber = (value) => value === '' || value === null || value === undefined ? null : Number(value);
    const formValues = (form) => Object.fromEntries(new FormData(form).entries());
    const formatDate = (value) => {
        if (!value) return '—';
        const rawValue = typeof value === 'object' && value !== null ? value.date : value;
        if (typeof rawValue !== 'string' && typeof rawValue !== 'number') return '—';
        const rawDate = String(rawValue);
        const date = new Date(rawDate);
        if (Number.isNaN(date.getTime())) return rawDate;
        const options = /[T ]\d{2}:\d{2}/.test(rawDate)
            ? { dateStyle: 'medium', timeStyle: 'short' }
            : { dateStyle: 'medium' };
        return new Intl.DateTimeFormat('es-CO', options).format(date);
    };
    const notify = (message, isError = false) => {
        const region = $('[data-toast-region]');
        if (!region) return;
        const toast = document.createElement('div');
        toast.className = `toast${isError ? ' toast--error' : ''}`;
        toast.setAttribute('role', isError ? 'alert' : 'status');
        toast.textContent = message;
        region.append(toast);
        window.setTimeout(() => toast.remove(), 4800);
    };
    const setMessage = (form, message = '', isSuccess = false) => {
        const node = $('[data-form-message]', form);
        if (!node) return;
        node.textContent = message;
        node.classList.toggle('is-success', isSuccess);
    };
    const setLoading = (form, loading, label = 'Guardando…') => {
        const button = $('button[type="submit"]', form);
        if (!button) return;
        if (loading) {
            button.dataset.originalText = button.textContent.trim();
            button.disabled = true;
            button.textContent = label;
        } else {
            button.disabled = false;
            if (button.dataset.originalText) button.textContent = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    };

    async function ensureSession() {
        if (session) return session;
        if (!sessionPromise) {
            sessionPromise = fetch(`${apiBase}/sesion`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(async (response) => {
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw Object.assign(new Error(payload.error?.message || 'Tu sesión terminó. Inicia sesión nuevamente.'), { status: response.status });
                    session = payload.data;
                    csrfToken = session?.csrfToken || '';
                    return session;
                }).finally(() => { sessionPromise = null; });
        }
        try {
            return await sessionPromise;
        } catch (error) {
            if (error.status === 401 && root.hasAttribute('data-app-root')) window.location.assign(`${basePath}/login`);
            throw error;
        }
    }

    async function request(path, options = {}) {
        const method = String(options.method || 'GET').toUpperCase();
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');
        const requestOptions = { ...options, method, headers, credentials: 'same-origin' };
        if (options.body && !(options.body instanceof FormData) && typeof options.body !== 'string') {
            headers.set('Content-Type', 'application/json');
            requestOptions.body = JSON.stringify(options.body);
        }
        if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && !(options.skipCsrf)) {
            await ensureSession();
            headers.set('X-CSRF-Token', csrfToken);
        }
        delete requestOptions.skipCsrf;
        let response;
        try {
            response = await fetch(`${apiBase}${path}`, requestOptions);
        } catch (cause) {
            const error = new Error('No se pudo conectar con el servidor. Comprueba que Apache esté activo y que abriste la aplicación desde http://localhost/Proyecto_Final/public/.');
            error.cause = cause;
            throw error;
        }
        if (response.status === 204) return { data: null, status: 204 };
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(payload.error?.message || 'No fue posible completar la solicitud.');
            error.status = response.status;
            error.code = payload.error?.code;
            if (response.status === 401 && root.hasAttribute('data-app-root')) window.location.assign(`${basePath}/login`);
            throw error;
        }
        return { ...payload, status: response.status };
    }

    function hasPermission(module, resource, action) {
        return (session?.permisos || []).some((permission) => permission.modulo === module && permission.recurso === resource && permission.accion === action);
    }
    function applyPermissions() {
        const permissionsByPath = {
            '/app/activos': [['activos', 'ficha', 'leer']],
            '/app/operaciones': [['activos', 'ficha', 'leer']],
            '/app/inventarios': [['inventarios', 'jornada', 'crear']],
            '/app/procesos-seleccion': [['seleccion', 'proceso', 'leer']],
            '/app/administracion': [['usuarios', 'cuenta', 'administrar']],
            '/app/reportes': [['reportes', 'informe', 'exportar']],
        };
        $$('.side-link[href]').forEach((link) => {
            const page = Object.keys(permissionsByPath).find((path) => link.getAttribute('href')?.endsWith(path));
            if (page && !permissionsByPath[page].some((permission) => hasPermission(...permission))) link.hidden = true;
        });
        const user = session?.usuario || {};
        $$('[data-user-name]').forEach((node) => { node.textContent = user.nombre || 'Usuario'; });
        $$('[data-welcome-name]').forEach((node) => { node.textContent = user.nombre ? `, ${user.nombre.split(' ')[0]}` : ''; });
        $$('[data-user-role]').forEach((node) => { node.textContent = `ID ${user.id || ''} · Sesión activa`; });
        $$('[data-requires-permission]').forEach((node) => {
            const [module, resource, action] = node.dataset.requiresPermission.split('.');
            if (!hasPermission(module, resource, action)) node.hidden = true;
        });
    }

    function renderErrorRow(tbody, colspan, message, retryText = '') {
        if (!tbody) return;
        tbody.innerHTML = `<tr><td class="table-placeholder" colspan="${colspan}">${escapeHtml(message)}${retryText ? ` <button type="button" class="table-action" data-retry>${escapeHtml(retryText)}</button>` : ''}</td></tr>`;
    }
    const stateClass = (state) => {
        const value = String(state || '').toUpperCase();
        if (['DADO_DE_BAJA', 'INACTIVO', 'RECHAZADA', 'RECHAZADO'].includes(value)) return 'state-badge--danger';
        if (['EN_MANTENIMIENTO', 'EN_REVISION', 'TRASLADO_SOLICITADO', 'PENDIENTE', 'EN_CURSO', 'EN_CONCILIACION'].includes(value)) return 'state-badge--warning';
        if (['EN_PRESTAMO', 'EN_TRASLADO'].includes(value)) return 'state-badge--blue';
        return '';
    };
    const badge = (state) => `<span class="state-badge ${stateClass(state)}">${escapeHtml(state || 'Sin estado')}</span>`;
    const categoryName = (id) => catalogs.categorias.find((category) => Number(category.id) === Number(id))?.nombre || (id ? `Categoría #${id}` : '—');
    const dependencyName = (id) => catalogs.dependencias.find((dependency) => Number(dependency.id) === Number(id))?.nombre || (id ? `Dependencia #${id}` : '—');
    const locationName = (id) => catalogs.ubicaciones.find((location) => Number(location.id) === Number(id))?.nombre || (id ? `Ubicación #${id}` : '—');

    function renderAssets(rows, tbody, includeCategory = false) {
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="${includeCategory ? 7 : 5}" class="table-placeholder">No se encontraron activos con esos criterios.</td></tr>`;
            return;
        }
        tbody.innerHTML = rows.map((asset) => `<tr>
            <td><strong>${escapeHtml(asset.placa)}</strong></td><td>${escapeHtml(asset.descripcion)}</td>
            ${includeCategory ? `<td>${escapeHtml(asset.serial || '—')}</td><td>${escapeHtml(categoryName(asset.categoria_id))}</td>` : ''}
            <td>${badge(asset.estado)}</td><td>${escapeHtml(locationName(asset.ubicacion_id))}</td>
            <td><button type="button" class="table-action" data-asset-detail-id="${Number(asset.id)}">Ver ficha</button></td>
        </tr>`).join('');
    }

    function fillSelect(select, rows, labelFor, firstLabel) {
        if (!select) return;
        const previous = select.value;
        select.replaceChildren(new Option(firstLabel, ''));
        rows.forEach((row) => {
            const option = new Option(labelFor(row), String(row.id));
            select.add(option);
        });
        if (previous && rows.some((row) => String(row.id) === previous)) select.value = previous;
    }

    async function loadCatalog(name, endpoint) {
        try {
            const result = await request(endpoint);
            catalogs[name] = Array.isArray(result.data) ? result.data : [];
            return catalogs[name];
        } catch (error) {
            catalogs[name] = [];
            return [];
        }
    }

    async function loadReferenceData() {
        const jobs = [
            loadCatalog('categorias', '/categorias'),
            loadCatalog('dependencias', '/dependencias'),
            loadCatalog('ubicaciones', '/ubicaciones'),
            loadCatalog('sedes', '/sedes'),
        ];
        await Promise.all(jobs);
        const activeLocations = catalogs.ubicaciones.filter((row) => row.estado === 'ACTIVA');
        const activeDeps = catalogs.dependencias.filter((row) => row.estado === 'ACTIVA');
        const activeSites = catalogs.sedes.filter((row) => row.estado === 'ACTIVA');
        const activeCategories = catalogs.categorias.filter((row) => row.estado === 'ACTIVA');
        ['#filter-categoria', '#asset-categoria', '#inventory-category', '#report-category'].forEach((selector) => {
            const select = $(selector);
            if (select) fillSelect(select, activeCategories, (item) => `${item.nombre} (${item.codigo})`, selector === '#asset-categoria' ? 'Selecciona categoría' : selector === '#filter-categoria' ? 'Todas las categorías' : 'Todas');
        });
        ['#asset-dependencia', '#assign-dependency', '#transfer-origin-dependency', '#transfer-destination-dependency', '#inventory-dependency', '#report-dependency', '#dependency-site', '#location-dependency'].forEach((selector) => {
            const select = $(selector);
            if (select) fillSelect(select, activeDeps, (item) => `${item.codigo} · ${item.nombre}`, select.required ? 'Selecciona dependencia' : selector.includes('transfer') ? 'Sin especificar' : selector === '#inventory-dependency' || selector === '#report-dependency' ? 'Todas / no especificar' : 'Sin dependencia');
        });
        ['#asset-ubicacion', '#assign-location', '#transfer-origin-location', '#transfer-destination-location', '#inventory-location', '#report-location'].forEach((selector) => {
            const select = $(selector);
            if (select) fillSelect(select, activeLocations, (item) => `${item.codigo} · ${item.nombre}`, select.required ? 'Selecciona ubicación' : selector === '#inventory-location' || selector === '#report-location' ? 'Todas / no especificar' : 'Selecciona ubicación');
        });
        ['#inventory-sede', '#report-site', '#location-site'].forEach((selector) => {
            const select = $(selector);
            if (select) fillSelect(select, activeSites, (item) => `${item.codigo} · ${item.nombre}`, selector === '#location-site' ? 'Selecciona sede' : 'Todas / no especificar');
        });
        const catalogSummary = $('[data-catalog-summary]');
        if (catalogSummary) catalogSummary.innerHTML = [
            `Sedes: ${activeSites.length}`, `Dependencias: ${activeDeps.length}`, `Ubicaciones: ${activeLocations.length}`, `Categorías: ${activeCategories.length}`,
        ].map((label) => `<span class="catalog-chip">${escapeHtml(label)}</span>`).join('');
        return { activeLocations, activeDeps, activeSites, activeCategories };
    }

    async function bindDashboard() {
        const tbody = $('[data-dashboard-assets]');
        if (!tbody) return;
        await loadReferenceData();
        Promise.all([request('/activos?limite=100'), request('/notificaciones?limite=20')]).then(([assetResult, notificationResult]) => {
            const assets = Array.isArray(assetResult.data) ? assetResult.data : [];
            const notifications = Array.isArray(notificationResult.data) ? notificationResult.data : [];
            const assetStat = $('[data-stat-assets]');
            const notificationStat = $('[data-stat-notifications]');
            if (assetStat) assetStat.textContent = String(assets.length);
            if (notificationStat) notificationStat.textContent = String(notifications.length);
            renderAssets(assets.slice(0, 8), tbody, false);
        }).catch((error) => {
            renderErrorRow(tbody, 5, error.message, 'Reintentar');
            $$('[data-retry]', tbody).forEach((button) => button.addEventListener('click', () => window.location.reload()));
        });
    }

    function showAssetDetail(asset) {
        const dialog = $('[data-asset-dialog]');
        const content = $('[data-asset-detail]');
        if (!dialog || !content) return;
        const fields = [
            ['Placa', asset.placa], ['Estado', asset.estado], ['Descripción', asset.descripcion],
            ['Categoría', categoryName(asset.categoria_id)], ['Serial', asset.serial], ['Marca', asset.marca],
            ['Modelo', asset.modelo], ['Ubicación', locationName(asset.ubicacion_id)],
            ['Dependencia', dependencyName(asset.dependencia_id)], ['Registro', formatDate(asset.creado_en)],
        ];
        content.innerHTML = `<dl class="detail-list">${fields.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value || '—')}</dd></div>`).join('')}</dl>
            ${hasPermission('activos', 'ficha', 'actualizar') ? `<form class="inline-form" data-change-asset-state-form><div class="field"><label for="detail-new-state">Cambiar estado</label><select id="detail-new-state" name="estado"><option>REGISTRADO</option><option>DISPONIBLE</option><option>ASIGNADO</option><option>TRASLADO_SOLICITADO</option><option>EN_TRASLADO</option><option>EN_MANTENIMIENTO</option><option>EN_PRESTAMO</option><option>DEVUELTO</option><option>EN_REVISION</option><option>DADO_DE_BAJA</option><option>INACTIVO</option></select></div><button class="button button--outline" type="submit">Actualizar estado</button><div class="form-message" data-form-message role="alert"></div></form>` : ''}`;
        const changeForm = $('[data-change-asset-state-form]', content);
        if (changeForm) {
            $('[name="estado"]', changeForm).value = asset.estado;
            changeForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                setMessage(changeForm, '');
                try {
                    const payload = formValues(changeForm);
                    const result = await request(`/activos/${Number(asset.id)}/estado`, { method: 'PATCH', body: payload });
                    notify('El estado del activo se actualizó.');
                    showAssetDetail(result.data);
                    bindAssetListing();
                } catch (error) { setMessage(changeForm, error.message); }
            });
        }
        const operationsLink = $('[data-asset-operations]', dialog);
        if (operationsLink) operationsLink.href = `${basePath}/app/operaciones?activoId=${Number(asset.id)}`;
        if (!dialog.open) dialog.showModal();
    }

    async function openAssetDetail(id) {
        const content = $('[data-asset-detail]');
        if (content) content.innerHTML = '<div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando ficha…</div>';
        const dialog = $('[data-asset-dialog]');
        if (dialog && !dialog.open) dialog.showModal();
        try {
            await loadReferenceData();
            const result = await request(`/activos/${Number(id)}`);
            showAssetDetail(result.data);
        } catch (error) {
            if (content) content.innerHTML = `<div class="form-message" role="alert">${escapeHtml(error.message)}</div>`;
        }
    }

    function bindAssetListing() {
        const form = $('[data-asset-filter-form]');
        const tbody = $('[data-assets-table]');
        if (!form || !tbody) return;
        const count = $('[data-assets-count]');
        const search = async () => {
            const values = formValues(form);
            tbody.innerHTML = '<tr><td colspan="7"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Consultando…</div></td></tr>';
            if (count) count.textContent = 'Consultando…';
            try {
                let rows;
                if (values.placa?.trim()) {
                    try {
                        const item = await request(`/activos/placa/${encodeURIComponent(values.placa.trim())}`);
                        rows = [item.data];
                    } catch (error) {
                        if (error.status === 404) rows = [];
                        else throw error;
                    }
                } else {
                    const query = new URLSearchParams({ limite: '100' });
                    if (values.estado) query.set('estado', values.estado);
                    if (values.categoriaId) query.set('categoriaId', values.categoriaId);
                    const result = await request(`/activos?${query.toString()}`);
                    rows = Array.isArray(result.data) ? result.data : [];
                }
                renderAssets(rows, tbody, true);
                if (count) count.textContent = `${rows.length} en esta consulta`;
            } catch (error) {
                renderErrorRow(tbody, 7, error.message);
                if (count) count.textContent = 'No se pudo cargar';
            }
        };
        form.onsubmit = (event) => { event.preventDefault(); search(); };
        const clearButton = $('[data-clear-asset-filters]');
        if (clearButton) clearButton.onclick = () => { form.reset(); search(); };
        tbody.onclick = (event) => {
            const button = event.target.closest('[data-asset-detail-id]');
            if (button) openAssetDetail(button.dataset.assetDetailId);
        };
        search();
    }

    function bindCreateAsset() {
        const form = $('[data-create-asset-form]');
        if (!form) return;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setMessage(form, '');
            if (!form.reportValidity()) return;
            const values = formValues(form);
            const payload = {
                placa: values.placa.trim(), fechaAsignacionPlaca: values.fechaAsignacionPlaca || null,
                serial: values.serial.trim() || null, descripcion: values.descripcion.trim(), categoriaId: Number(values.categoriaId),
                marca: values.marca.trim() || null, modelo: values.modelo.trim() || null, estado: values.estado,
                ubicacionId: Number(values.ubicacionId), dependenciaId: Number(values.dependenciaId),
            };
            setLoading(form, true);
            try {
                const result = await request('/activos', { method: 'POST', body: payload });
                form.reset();
                setMessage(form, `Activo registrado con ID ${result.data.id}.`, true);
                notify(`Activo ${payload.placa} registrado.`);
                bindAssetListing();
                window.location.hash = 'crear-activo';
            } catch (error) { setMessage(form, error.message); }
            finally { setLoading(form, false); }
        });
    }

    function bindAssetDialog() {
        const dialog = $('[data-asset-dialog]');
        if (!dialog) return;
        $$('[data-close-dialog]', dialog).forEach((button) => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('cancel', () => dialog.close());
    }

    async function bindAssetsPage() {
        if (!$('[data-assets-table]')) return;
        await loadReferenceData();
        bindAssetListing();
        bindCreateAsset();
        bindAssetDialog();
    }

    function bindOperationContext() {
        const input = $('[data-operation-asset-id]');
        const resultNode = $('[data-operation-asset]');
        const loadButton = $('[data-load-operation-asset]');
        if (!input || !loadButton) return;
        const load = async () => {
            const id = Number(input.value);
            if (!Number.isInteger(id) || id < 1) {
                resultNode.textContent = 'Escribe un ID de activo válido.';
                activeOperationAsset = null;
                return;
            }
            resultNode.innerHTML = '<span class="spinner" aria-hidden="true"></span> Consultando ficha…';
            try {
                const response = await request(`/activos/${id}`);
                activeOperationAsset = response.data;
                const assignmentResponse = await request(`/activos/${id}/asignacion-vigente`).catch(() => ({ data: null }));
                const assignment = assignmentResponse.data;
                const assignmentLabel = assignment ? ` · Asignación vigente #${Number(assignment.id)}${assignment.custodio_id ? ` · Custodio #${Number(assignment.custodio_id)}` : ''}` : ' · Sin asignación vigente';
                resultNode.innerHTML = `<strong>${escapeHtml(activeOperationAsset.placa)}</strong> · ${escapeHtml(activeOperationAsset.descripcion)} · ${badge(activeOperationAsset.estado)}<br><small>${escapeHtml(assignmentLabel)}</small>`;
                const originLocation = $('[name="origenUbicacionId"]', $('[data-operation-form="transfer"]'));
                const originDependency = $('[name="origenDependenciaId"]', $('[data-operation-form="transfer"]'));
                if (originLocation) originLocation.value = String(activeOperationAsset.ubicacion_id || '');
                if (originDependency) originDependency.value = String(activeOperationAsset.dependencia_id || '');
            } catch (error) {
                activeOperationAsset = null;
                resultNode.textContent = error.message;
            }
        };
        loadButton.addEventListener('click', load);
        const params = new URLSearchParams(window.location.search);
        const assetId = params.get('activoId');
        if (assetId) { input.value = assetId; load(); }
    }

    function bindOperationForms() {
        const forms = $$('[data-operation-form]');
        if (!forms.length) return;
        const needsAsset = ['assignment', 'transfer', 'loan', 'novelty', 'maintenance', 'retirement'];
        forms.forEach((form) => form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setMessage(form, '');
            if (!form.reportValidity()) return;
            const type = form.dataset.operationForm;
            const values = formValues(form);
            let path = '';
            let body = {};
            if (needsAsset.includes(type) && !activeOperationAsset) {
                setMessage(form, 'Primero consulta y selecciona un activo.');
                return;
            }
            const assetId = activeOperationAsset?.id;
            if (type === 'assignment') {
                path = `/activos/${assetId}/asignaciones`;
                body = { custodioId: asOptionalNumber(values.custodioId), dependenciaId: Number(values.dependenciaId), ubicacionId: Number(values.ubicacionId), observacion: values.observacion.trim() || null };
            } else if (type === 'transfer') {
                path = '/traslados';
                body = { activoId: Number(assetId), origenUbicacionId: Number(values.origenUbicacionId), destinoUbicacionId: Number(values.destinoUbicacionId), origenDependenciaId: asOptionalNumber(values.origenDependenciaId), destinoDependenciaId: asOptionalNumber(values.destinoDependenciaId), fechaPropuesta: values.fechaPropuesta || null, motivo: values.motivo.trim() };
            } else if (type === 'loan') {
                path = '/prestamos';
                body = { activoId: Number(assetId), responsableId: Number(values.responsableId), destino: values.destino.trim() || null, fechaSalida: values.fechaSalida, fechaLimite: values.fechaLimite, condicionesRetorno: values.condicionesRetorno.trim() || null };
            } else if (type === 'novelty') {
                path = '/novedades';
                body = { activoId: Number(assetId), tipo: values.tipo.trim(), descripcion: values.descripcion.trim() };
            } else if (type === 'maintenance') {
                path = `/activos/${assetId}/mantenimientos`;
                body = { diagnostico: values.diagnostico.trim(), acciones: values.acciones.trim() || null, tecnicoProveedor: values.tecnicoProveedor.trim() || null, fechaInicio: values.fechaInicio };
            } else if (type === 'retirement') {
                path = `/activos/${assetId}/bajas`;
                body = { causal: values.causal.trim(), solicitud: values.solicitud.trim() };
            } else if (type === 'assignment-return' || type === 'loan-return') {
                const endpoint = type === 'assignment-return' ? 'asignaciones' : 'prestamos';
                path = `/${endpoint}/${Number(values.id)}/devolucion`;
                body = { estadoFisico: values.estadoFisico.trim(), destinoPosterior: values.destinoPosterior.trim() || null, excepcion: values.excepcion.trim() || null };
            }
            if (!path) return;
            setLoading(form, true, 'Procesando…');
            try {
                const response = await request(path, { method: 'POST', body });
                form.reset();
                setMessage(form, `Operación registrada. ID: ${response.data?.id ?? 'confirmado'}.`, true);
                notify('La operación se registró correctamente.');
            } catch (error) { setMessage(form, error.message); }
            finally { setLoading(form, false); }
        }));
    }

    function bindTransferResolution() {
        const decisionForm = $('[data-transfer-decision-form]');
        if (decisionForm) decisionForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!decisionForm.reportValidity()) return;
            const values = formValues(decisionForm);
            try {
                await request(`/traslados/${Number(values.id)}/decision`, { method: 'POST', body: { decision: values.decision, observacion: values.observacion.trim() || null } });
                setMessage(decisionForm, 'Decisión registrada.', true);
                notify('La solicitud de traslado fue actualizada.');
                decisionForm.reset();
            } catch (error) { setMessage(decisionForm, error.message); }
        });
        const completeForm = $('[data-transfer-complete-form]');
        if (completeForm) completeForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!completeForm.reportValidity()) return;
            const values = formValues(completeForm);
            try {
                const response = await request(`/traslados/${Number(values.id)}/completar`, { method: 'POST', body: { observaciones: values.observaciones.trim() || null } });
                setMessage(completeForm, `Traslado completado. Movimiento ID ${response.data.id}.`, true);
                notify('El traslado fue completado.');
                completeForm.reset();
            } catch (error) { setMessage(completeForm, error.message); }
        });
    }

    async function bindOperationsPage() {
        if (!$('[data-operation-form]')) return;
        await loadReferenceData();
        bindOperationContext();
        bindOperationForms();
        bindTransferResolution();
    }

    function renderInventory(data) {
        const container = $('[data-inventory-detail]');
        const actionBar = $('[data-inventory-actions]');
        const entrySection = $('[data-inventory-entry-section]');
        const differenceSection = $('[data-difference-section]');
        const stateChip = $('[data-inventory-state]');
        if (!container || !data?.inventario) return;
        const inventory = data.inventario;
        const details = Array.isArray(data.detalles) ? data.detalles : [];
        activeInventoryId = Number(inventory.id);
        const state = String(inventory.estado || '').toUpperCase();
        if (stateChip) {
            stateChip.textContent = state;
            stateChip.className = `status-chip ${['EN_CURSO', 'EN_CONCILIACION'].includes(state) ? 'status-chip--active' : state === 'PLANIFICADO' ? 'status-chip--warning' : 'status-chip--closed'}`;
        }
        container.innerHTML = `<div class="inventory-summary">
            <div><small>Jornada</small><strong>${escapeHtml(inventory.nombre)}</strong></div>
            <div><small>Estado</small><strong>${badge(state)}</strong></div>
            <div><small>Alcance</small><strong>${escapeHtml(inventory.alcance)}</strong></div>
            <div><small>Inicio</small><strong>${escapeHtml(formatDate(inventory.fecha_inicio))}</strong></div>
        </div>
        ${details.length ? `<div class="table-wrap inventory-detail-table"><table><thead><tr><th scope="col">ID</th><th scope="col">Activo</th><th scope="col">Resultado</th><th scope="col">Placa observada</th><th scope="col">Fecha</th><th scope="col">Acción</th></tr></thead><tbody>${details.map((item) => `<tr><td>${Number(item.id)}</td><td>${item.activo_id ? `#${Number(item.activo_id)}` : 'Hallazgo nuevo'}</td><td>${badge(item.resultado)}</td><td>${escapeHtml(item.placa_observada || '—')}</td><td>${escapeHtml(formatDate(item.fecha_verificacion))}</td><td>${['EN_CURSO', 'EN_CONCILIACION'].includes(state) ? `<button type="button" class="table-action" data-difference-detail-id="${Number(item.id)}">Reportar diferencia</button>` : '—'}</td></tr>`).join('')}</tbody></table></div>` : '<div class="empty-state"><span class="empty-icon" aria-hidden="true">✓</span><strong>Sin verificaciones aún</strong><p>Cuando la jornada esté en curso, registra aquí los hallazgos.</p></div>'}`;
        if (actionBar) actionBar.hidden = !['PLANIFICADO', 'EN_CURSO', 'EN_CONCILIACION'].includes(state);
        const startButton = $('[data-inventory-start]');
        const closeButton = $('[data-inventory-close]');
        if (startButton) startButton.hidden = state !== 'PLANIFICADO';
        if (closeButton) closeButton.hidden = !['EN_CURSO', 'EN_CONCILIACION'].includes(state);
        if (entrySection) entrySection.hidden = !['EN_CURSO', 'EN_CONCILIACION'].includes(state);
        if (differenceSection) differenceSection.hidden = !['EN_CURSO', 'EN_CONCILIACION'].includes(state);
    }

    async function loadInventory(id) {
        const container = $('[data-inventory-detail]');
        if (container) container.innerHTML = '<div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando jornada…</div>';
        try {
            const result = await request(`/inventarios/${Number(id)}`);
            renderInventory(result.data);
        } catch (error) {
            activeInventoryId = null;
            if (container) container.innerHTML = `<div class="form-message" role="alert">${escapeHtml(error.message)}</div>`;
            const entry = $('[data-inventory-entry-section]');
            const difference = $('[data-difference-section]');
            if (entry) entry.hidden = true;
            if (difference) difference.hidden = true;
        }
    }

    async function bindInventoryPage() {
        if (!$('[data-create-inventory-form]')) return;
        await loadReferenceData();
        const dateInput = $('#inventory-date');
        if (dateInput && !dateInput.value) dateInput.value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        const loadForm = $('[data-load-inventory-form]');
        const idInput = $('[data-inventory-id]');
        loadForm.addEventListener('submit', (event) => { event.preventDefault(); loadInventory(idInput.value); });
        const params = new URLSearchParams(window.location.search);
        const inventoryId = params.get('inventarioId');
        if (inventoryId) { idInput.value = inventoryId; loadInventory(inventoryId); }

        $('[data-create-inventory-form]').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            setMessage(form, '');
            if (!form.reportValidity()) return;
            const value = formValues(form);
            const payload = { nombre: value.nombre.trim(), alcance: value.alcance.trim(), sedeId: asOptionalNumber(value.sedeId), dependenciaId: asOptionalNumber(value.dependenciaId), ubicacionId: asOptionalNumber(value.ubicacionId), categoriaId: asOptionalNumber(value.categoriaId), fechaInicio: value.fechaInicio };
            setLoading(form, true);
            try {
                const response = await request('/inventarios', { method: 'POST', body: payload });
                idInput.value = String(response.data.id);
                setMessage(form, `Jornada creada con ID ${response.data.id}.`, true);
                notify('Jornada de inventario creada.');
                await loadInventory(response.data.id);
                window.location.hash = 'consultar-inventario';
            } catch (error) { setMessage(form, error.message); }
            finally { setLoading(form, false); }
        });

        $('[data-inventory-start]').addEventListener('click', async () => {
            if (!activeInventoryId) return;
            try { const result = await request(`/inventarios/${activeInventoryId}/iniciar`, { method: 'POST', body: {} }); await loadInventory(activeInventoryId); notify('Jornada iniciada.'); }
            catch (error) { notify(error.message, true); }
        });
        $('[data-inventory-close]').addEventListener('click', async () => {
            if (!activeInventoryId || !window.confirm('¿Cerrar la jornada de inventario? Ya no admitirá nuevas verificaciones.')) return;
            try { await request(`/inventarios/${activeInventoryId}/cerrar`, { method: 'POST', body: {} }); await loadInventory(activeInventoryId); notify('Jornada cerrada.'); }
            catch (error) { notify(error.message, true); }
        });
        $('[data-inventory-detail]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-difference-detail-id]');
            if (!button) return;
            const input = $('#difference-detail-id');
            if (input) input.value = button.dataset.differenceDetailId;
            $('[data-difference-section]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            $('#difference-description')?.focus({ preventScroll: true });
        });
        $('[data-inventory-detail-form]').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!activeInventoryId || !form.reportValidity()) return;
            const value = formValues(form);
            const body = { activoId: asOptionalNumber(value.activoId), resultado: value.resultado.trim(), placaObservada: value.placaObservada.trim() || null, ubicacionObservada: value.ubicacionObservada.trim() || null, condicionFisica: value.condicionFisica.trim() || null, observacion: value.observacion.trim() || null };
            try { await request(`/inventarios/${activeInventoryId}/detalles`, { method: 'POST', body }); form.reset(); setMessage(form, 'Verificación registrada.', true); await loadInventory(activeInventoryId); notify('Hallazgo agregado al inventario.'); }
            catch (error) { setMessage(form, error.message); }
        });
        $('[data-difference-form]').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!activeInventoryId || !form.reportValidity()) return;
            const value = formValues(form);
            const body = { descripcion: value.descripcion.trim(), responsableId: asOptionalNumber(value.responsableId) };
            try { await request(`/inventarios/${activeInventoryId}/detalles/${Number(value.detalleId)}/diferencias`, { method: 'POST', body }); form.reset(); setMessage(form, 'Diferencia registrada.', true); await loadInventory(activeInventoryId); notify('Diferencia de inventario registrada.'); }
            catch (error) { setMessage(form, error.message); }
        });
    }

    async function loadUsers() {
        const tbody = $('[data-users-table]');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="4"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando usuarios…</div></td></tr>';
        try {
            const result = await request('/usuarios?limite=100');
            const users = Array.isArray(result.data) ? result.data : [];
            if (!users.length) { renderErrorRow(tbody, 4, 'No hay usuarios para mostrar.'); return; }
            tbody.innerHTML = users.map((user) => `<tr><td><strong>${escapeHtml(user.nombre)}</strong></td><td>${escapeHtml(user.correo)}</td><td>${badge(user.estado)}</td><td>${Number(user.id)}</td><td><button class="table-action" type="button" data-user-state-id="${Number(user.id)}" data-user-state="${escapeHtml(user.estado)}">${user.estado === 'ACTIVO' ? 'Inactivar' : 'Activar'}</button></td></tr>`).join('');
        } catch (error) { renderErrorRow(tbody, 5, error.message); }
    }

    async function bindAdminPage() {
        if (!$('[data-create-user-form]')) return;
        await loadReferenceData();
        const roleOptions = $('[data-role-options]');
        try {
            const result = await request('/roles');
            catalogs.roles = Array.isArray(result.data) ? result.data : [];
            if (roleOptions) roleOptions.innerHTML = catalogs.roles.map((role) => `<label class="check-option"><input type="checkbox" name="roles" value="${Number(role.id)}"><span>${escapeHtml(role.nombre)}</span></label>`).join('') || '<span class="field-hint">No hay roles disponibles.</span>';
        } catch (error) { if (roleOptions) roleOptions.innerHTML = `<span class="form-message">${escapeHtml(error.message)}</span>`; }

        const bindJsonForm = (selector, urlFor, method, payloadFor, successMessage, after) => {
            const form = $(selector);
            if (!form) return;
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!form.reportValidity()) return;
                const values = formValues(form);
                const { path, body } = payloadFor(values);
                setMessage(form, ''); setLoading(form, true);
                try {
                    const response = await request(path || urlFor(values), { method, body });
                    form.reset(); setMessage(form, `${successMessage}${response.data?.id ? ` ID ${response.data.id}.` : ''}`, true); notify(successMessage);
                    if (after) await after(response, values);
                } catch (error) { setMessage(form, error.message); }
                finally { setLoading(form, false); }
            });
        };

        bindJsonForm('[data-create-user-form]', () => '/usuarios', 'POST', (v) => ({ path: '/usuarios', body: { identificacion: v.identificacion.trim(), nombre: v.nombre.trim(), correo: v.correo.trim(), contrasena: v.contrasena } }), 'Usuario creado.', async (response) => {
            const idInput = $('#role-user-id'); if (idInput) idInput.value = String(response.data.id);
            await loadUsers();
        });
        bindJsonForm('[data-create-site-form]', () => '/sedes', 'POST', (v) => ({ path: '/sedes', body: { codigo: v.codigo.trim(), nombre: v.nombre.trim(), direccion: v.direccion.trim() || null } }), 'Sede registrada.', async () => loadReferenceData());
        bindJsonForm('[data-create-dependency-form]', () => '/dependencias', 'POST', (v) => ({ path: '/dependencias', body: { sedeId: asOptionalNumber(v.sedeId), codigo: v.codigo.trim(), nombre: v.nombre.trim(), tipo: v.tipo.trim() || null, dependenciaPadreId: asOptionalNumber(v.dependenciaPadreId) } }), 'Dependencia registrada.', async () => loadReferenceData());
        bindJsonForm('[data-create-location-form]', () => '/ubicaciones', 'POST', (v) => ({ path: '/ubicaciones', body: { sedeId: Number(v.sedeId), dependenciaId: asOptionalNumber(v.dependenciaId), codigo: v.codigo.trim(), nombre: v.nombre.trim(), tipo: v.tipo.trim() || null, ubicacionPadreId: asOptionalNumber(v.ubicacionPadreId) } }), 'Ubicación registrada.', async () => loadReferenceData());

        $('[data-load-users]')?.addEventListener('click', loadUsers);
        $('[data-users-table]')?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-user-state-id]');
            if (!button) return;
            const nextState = button.dataset.userState === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
            try { await request(`/usuarios/${Number(button.dataset.userStateId)}/estado`, { method: 'PATCH', body: { estado: nextState } }); notify(`Usuario ${nextState === 'ACTIVO' ? 'activado' : 'inactivado'}.`); await loadUsers(); }
            catch (error) { notify(error.message, true); }
        });
        $('[data-assign-roles-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!form.reportValidity()) return;
            const userId = Number($('[name="id"]', form).value);
            const roles = $$('input[name="roles"]:checked', form).map((input) => Number(input.value));
            try { const response = await request(`/usuarios/${userId}/roles`, { method: 'PUT', body: { roles } }); setMessage(form, `${response.data.length} asignación(es) de rol guardadas.`, true); notify('Roles actualizados.'); }
            catch (error) { setMessage(form, error.message); }
        });
    }

    function renderReport(rows) {
        const tbody = $('[data-report-table]');
        const summary = $('[data-report-summary]');
        if (!tbody) return;
        currentReportRows = rows;
        const reportStats = {
            total: rows.length,
            disponibles: rows.filter((row) => String(row.estado).toUpperCase() === 'DISPONIBLE').length,
            asignados: rows.filter((row) => String(row.estado).toUpperCase() === 'ASIGNADO').length,
            revision: rows.filter((row) => ['EN_REVISION', 'EN_MANTENIMIENTO'].includes(String(row.estado).toUpperCase())).length,
        };
        $$('[data-report-stat]').forEach((node) => {
            node.textContent = String(reportStats[node.dataset.reportStat] ?? 0);
        });
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="table-placeholder">No hay activos que coincidan con los filtros.</td></tr>';
            if (summary) summary.textContent = 'La consulta no devolvió activos.';
        } else {
            tbody.innerHTML = rows.map((row) => `<tr><td><strong>${escapeHtml(row.placa)}</strong></td><td>${escapeHtml(row.descripcion)}</td><td>${badge(row.estado)}</td><td>${escapeHtml(row.dependencia)}</td><td>${escapeHtml(row.ubicacion)}</td></tr>`).join('');
            if (summary) summary.textContent = `${rows.length} activo(s) dentro de tu ámbito autorizado.`;
        }
        const exportButton = $('[data-export-report]');
        if (exportButton) exportButton.disabled = rows.length === 0;
    }

    function csvCell(value) {
        let text = String(value ?? '');
        if (/^[=+\-@\t\r]/.test(text)) text = `'${text}`;
        return `"${text.replace(/"/g, '""')}"`;
    }

    async function bindReportsPage() {
        if (!$('[data-report-form]')) return;
        await loadReferenceData();
        $('[data-report-form]').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const values = formValues(form);
            const query = new URLSearchParams();
            Object.entries(values).forEach(([key, value]) => { if (value) query.set(key, value); });
            const tbody = $('[data-report-table]');
            if (tbody) tbody.innerHTML = '<tr><td colspan="5"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Generando reporte…</div></td></tr>';
            try { const result = await request(`/reportes/activos${query.size ? `?${query}` : ''}`); renderReport(Array.isArray(result.data) ? result.data : []); }
            catch (error) {
                renderErrorRow(tbody, 5, error.message);
                currentReportRows = [];
                $$('[data-report-stat]').forEach((node) => { node.textContent = '—'; });
            }
        });
        $('[data-export-report]')?.addEventListener('click', () => {
            if (!currentReportRows.length) return;
            const header = ['placa', 'descripcion', 'estado', 'sede', 'dependencia', 'ubicacion', 'categoria'];
            const rows = currentReportRows.map((row) => [row.placa, row.descripcion, row.estado, row.sede, row.dependencia, row.ubicacion, row.categoria]);
            const csv = '\ufeff' + [header, ...rows].map((row) => row.map(csvCell).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            link.download = 'reporte-activos.csv'; link.click(); URL.revokeObjectURL(link.href);
        });
        $('[data-import-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!form.reportValidity()) return;
            setMessage(form, ''); setLoading(form, true, 'Validando…');
            try {
                const result = await request('/importaciones', { method: 'POST', body: new FormData(form) });
                const idInput = $('[data-import-id]'); if (idInput) idInput.value = String(result.data.id);
                setMessage(form, `Importación procesada: ${result.data.registrosAceptados} aceptado(s), ${result.data.registrosRechazados} rechazado(s).`, true);
                notify(`Importación ${result.data.estado.toLowerCase().replaceAll('_', ' ')}.`);
                await loadImport(result.data.id);
            } catch (error) { setMessage(form, error.message); }
            finally { setLoading(form, false); }
        });
        $('[data-load-import]')?.addEventListener('click', () => loadImport($('[data-import-id]').value));
        $('[data-document-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!form.reportValidity()) return;
            setMessage(form, ''); setLoading(form, true, 'Cargando…');
            try {
                const result = await request('/documentos', { method: 'POST', body: new FormData(form) });
                const link = `${apiBase}/documentos/${Number(result.data.id)}`;
                $('[data-document-result]').innerHTML = `<div class="document-result"><span aria-hidden="true">✓</span><span>Documento cargado: <a class="text-link" href="${escapeHtml(link)}" target="_blank" rel="noopener">${escapeHtml(result.data.nombre)} · Descargar</a></span></div>`;
                form.reset(); notify('Documento guardado en el almacenamiento privado.');
            } catch (error) { setMessage(form, error.message); }
            finally { setLoading(form, false); }
        });
        $('[data-load-audit]')?.addEventListener('click', loadAudit);
    }

    async function bindSelectionPage() {
        const page = $('[data-selection-page]');
        if (!page) return;
        const processSelect = $('[data-selection-process-filter]');
        const unitSelect = $('[data-selection-unit-filter]');
        const stateSelect = $('[data-selection-state-filter]');
        const searchInput = $('[data-selection-search]');
        const table = $('[data-selection-table]');
        const feedback = $('[data-selection-feedback]');
        const processPanel = $('[data-selection-create-panel]');
        const applicationPanel = $('[data-selection-application-panel]');
        const processForm = $('[data-selection-process-form]');
        const applicationForm = $('[data-selection-application-form]');
        const documentDialog = $('[data-selection-doc-dialog]');
        const documentList = $('[data-selection-doc-list]');
        const documentForm = $('[data-selection-document-form]');
        let processes = [];
        let dependencies = [];
        let currentApplicationId = null;
        let currentProcessId = null;

        const stat = (key, value) => {
            const node = $(`[data-selection-stat="${key}"]`);
            if (node) node.textContent = String(value);
        };
        const selectedProcess = () => processes.find((item) => Number(item.id) === Number(processSelect?.value)) || null;
        const renderProcessOptions = () => {
            if (!processSelect) return;
            const previous = processSelect.value;
            const unitId = unitSelect?.value || '';
            const state = stateSelect?.value || '';
            const visible = processes.filter((item) => (!unitId || String(item.dependencia_id) === unitId) && (!state || item.estado === state));
            processSelect.replaceChildren(new Option('Selecciona un proceso', ''));
            visible.forEach((item) => processSelect.add(new Option(`${item.nombre} · ${item.unidad}`, String(item.id))));
            if (visible.some((item) => String(item.id) === previous)) processSelect.value = previous;
            else if (visible.length) processSelect.value = String(visible[0].id);
            if (!visible.length) processSelect.value = '';
        };
        const renderProcessStats = () => {
            stat('postulaciones', processes.reduce((sum, item) => sum + Number(item.postulaciones || 0), 0));
            stat('documentos', processes.reduce((sum, item) => sum + Number(item.documentos || 0), 0));
            stat('aprobados', processes.reduce((sum, item) => sum + Number(item.aprobados || 0), 0));
            stat('pendientes', processes.reduce((sum, item) => sum + Number(item.pendientes || 0), 0));
        };
        const loadApplications = async () => {
            const item = selectedProcess();
            if (!item || !table) {
                renderErrorRow(table, 7, 'No hay procesos que coincidan con los filtros.');
                $('[data-selection-toggle-application]').disabled = true;
                return;
            }
            currentProcessId = Number(item.id);
            const processLabel = $('[data-selection-current-process]');
            if (processLabel) processLabel.textContent = item.nombre;
            const mayManage = hasPermission('seleccion', 'proceso', 'administrar');
            const addCandidate = $('[data-selection-toggle-application]');
            if (addCandidate) addCandidate.disabled = !mayManage || item.estado !== 'ABIERTO';
            if (feedback) feedback.textContent = `Unidad: ${item.unidad} · Estado: ${item.estado} · ${Number(item.postulaciones || 0)} postulaciones`;
            table.innerHTML = '<tr><td colspan="7"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando postulaciones…</div></td></tr>';
            try {
                const query = searchInput?.value.trim() ? `?q=${encodeURIComponent(searchInput.value.trim())}` : '';
                const result = await request(`/procesos-seleccion/${Number(item.id)}/postulaciones${query}`);
                const rows = Array.isArray(result.data) ? result.data : [];
                if (!rows.length) {
                    renderErrorRow(table, 7, item.estado === 'ABIERTO' ? 'Todavía no hay postulaciones en este proceso.' : 'No hay postulaciones para mostrar.');
                    return;
                }
                table.innerHTML = rows.map((row) => `<tr>
                    <td><strong>${escapeHtml(row.usuario)}</strong><small class="selection-table-subtitle">${escapeHtml(row.correo)}</small></td>
                    <td>${escapeHtml(item.nombre)}<small class="selection-table-subtitle">${escapeHtml(item.estado)}</small></td>
                    <td><span class="selection-count-chip">${Number(row.documentos_cargados)}</span></td>
                    <td><span class="selection-count-chip selection-count-chip--green">${Number(row.aprobados)}</span></td>
                    <td><span class="selection-count-chip selection-count-chip--gold">${Number(row.pendientes)}</span></td>
                    <td><span class="selection-count-chip selection-count-chip--red">${Number(row.rechazados)}</span></td>
                    <td><button class="table-action" type="button" data-selection-documents="${Number(row.postulacion_id)}">Documentos</button></td>
                </tr>`).join('');
            } catch (error) { renderErrorRow(table, 7, error.message); }
        };
        const loadProcesses = async () => {
            if (feedback) feedback.textContent = 'Cargando procesos…';
            const result = await request('/procesos-seleccion?limite=100');
            processes = Array.isArray(result.data) ? result.data : [];
            renderProcessStats();
            renderProcessOptions();
            if (processes.length) await loadApplications();
            else {
                renderErrorRow(table, 7, 'Aún no hay procesos. Crea una convocatoria para comenzar.');
                if (feedback) feedback.textContent = 'Crea el primer proceso de selección para registrar postulaciones.';
                const addCandidate = $('[data-selection-toggle-application]');
                if (addCandidate) addCandidate.disabled = true;
            }
        };
        const loadDocuments = async (applicationId) => {
            const result = await request(`/postulaciones/${Number(applicationId)}/documentos`);
            const rows = Array.isArray(result.data) ? result.data : [];
            const canReview = hasPermission('seleccion', 'postulacion', 'revisar');
            const canUpload = hasPermission('seleccion', 'proceso', 'administrar');
            documentList.innerHTML = rows.length ? rows.map((doc) => `<form class="selection-document-row" data-selection-review-form data-document-id="${Number(doc.id)}">
                <div class="selection-document-info"><strong>${escapeHtml(doc.tipo_documento)}</strong><small>${escapeHtml(doc.nombre_original)} · ${escapeHtml(formatDate(doc.subido_en))}</small><span class="status-chip">${escapeHtml(doc.estado_revision)}</span></div>
                <a class="button button--quiet" href="${apiBase}/documentos-postulacion/${Number(doc.id)}" target="_blank" rel="noopener">Descargar</a>
                ${canReview ? `<label class="field"><span class="sr-only">Estado de revisión</span><select name="estado">${['PENDIENTE','APROBADO','RECHAZADO','SUBSANADO'].map((value) => `<option${doc.estado_revision === value ? ' selected' : ''}>${value}</option>`).join('')}</select></label><label class="field"><span class="sr-only">Observación de revisión</span><input name="observacion" maxlength="1000" placeholder="Observación (opcional)" value="${escapeHtml(doc.observacion_revision || '')}"></label><button class="button button--outline" type="submit">Guardar revisión</button>` : ''}
            </form>`).join('') : '<div class="empty-state"><span class="empty-icon" aria-hidden="true">▤</span><strong>Sin documentos cargados</strong><p>Cuando se carguen documentos aparecerán aquí para su consulta y revisión.</p></div>';
            if (documentForm) documentForm.hidden = !canUpload;
        };

        try {
            const unitResult = await request('/procesos-seleccion/unidades');
            dependencies = Array.isArray(unitResult.data) ? unitResult.data : [];
            dependencies.forEach((item) => {
                unitSelect?.add(new Option(`${item.codigo} · ${item.nombre}`, String(item.id)));
                const formUnit = $('#selection-process-unit');
                formUnit?.add(new Option(`${item.codigo} · ${item.nombre}`, String(item.id)));
            });
            await loadProcesses();
        } catch (error) {
            if (feedback) feedback.textContent = error.message;
            renderErrorRow(table, 7, error.message);
        }

        $('[data-selection-toggle-process]')?.addEventListener('click', () => { processPanel.hidden = !processPanel.hidden; applicationPanel.hidden = true; });
        $('[data-selection-cancel-process]')?.addEventListener('click', () => { processPanel.hidden = true; processForm.reset(); setMessage(processForm, ''); });
        processForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!processForm.reportValidity()) return;
            const values = formValues(processForm);
            values.dependenciaId = Number(values.dependenciaId);
            Object.keys(values).forEach((key) => { if (values[key] === '') values[key] = null; });
            setMessage(processForm, '');
            try {
                await request('/procesos-seleccion', { method: 'POST', body: values });
                processForm.reset(); processPanel.hidden = true; notify('Proceso de selección creado.'); await loadProcesses();
            } catch (error) { setMessage(processForm, error.message); }
        });
        unitSelect?.addEventListener('change', () => { renderProcessOptions(); loadApplications(); });
        stateSelect?.addEventListener('change', () => { renderProcessOptions(); loadApplications(); });
        processSelect?.addEventListener('change', () => { applicationPanel.hidden = true; loadApplications(); });
        let searchTimer = 0;
        searchInput?.addEventListener('input', () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(loadApplications, 250); });
        $('[data-selection-toggle-application]')?.addEventListener('click', () => { applicationPanel.hidden = !applicationPanel.hidden; processPanel.hidden = true; });
        $('[data-selection-cancel-application]')?.addEventListener('click', () => { applicationPanel.hidden = true; applicationForm.reset(); setMessage(applicationForm, ''); });
        applicationForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!applicationForm.reportValidity() || !currentProcessId) return;
            const values = formValues(applicationForm);
            setMessage(applicationForm, '');
            try {
                await request(`/procesos-seleccion/${currentProcessId}/postulaciones`, { method: 'POST', body: values });
                applicationForm.reset(); applicationPanel.hidden = true; notify('Postulación registrada.'); await loadProcesses();
            } catch (error) { setMessage(applicationForm, error.message); }
        });
        table?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-selection-documents]');
            if (!button) return;
            currentApplicationId = Number(button.dataset.selectionDocuments);
            $('[data-selection-doc-title]')?.replaceChildren(document.createTextNode(`Documentos de ${button.closest('tr')?.querySelector('td strong')?.textContent || 'postulación'}`));
            try { await loadDocuments(currentApplicationId); documentDialog.showModal(); }
            catch (error) { notify(error.message, true); }
        });
        $('[data-selection-close-docs]')?.addEventListener('click', () => documentDialog.close());
        documentDialog?.addEventListener('click', (event) => { if (event.target === documentDialog) documentDialog.close(); });
        documentList?.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-selection-review-form]');
            if (!form) return;
            event.preventDefault();
            const values = formValues(form);
            try {
                await request(`/documentos-postulacion/${Number(form.dataset.documentId)}/revision`, { method: 'PATCH', body: values });
                notify('Revisión actualizada.'); await loadDocuments(currentApplicationId); await loadProcesses();
            } catch (error) { notify(error.message, true); }
        });
        documentForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!documentForm.reportValidity() || !currentProcessId || !currentApplicationId) return;
            try {
                await request(`/procesos-seleccion/${currentProcessId}/postulaciones/${currentApplicationId}/documentos`, { method: 'POST', body: new FormData(documentForm) });
                documentForm.reset(); setMessage(documentForm, ''); notify('Documento cargado.'); await loadDocuments(currentApplicationId); await loadProcesses();
            } catch (error) { setMessage(documentForm, error.status ? `${error.message} (HTTP ${error.status})` : error.message); }
        });
    }

    async function loadImport(id) {
        const target = $('[data-import-result]');
        if (!target) return;
        const parsed = Number(id);
        if (!Number.isInteger(parsed) || parsed < 1) { target.innerHTML = '<div class="form-message" role="alert">Escribe un ID de importación válido.</div>'; return; }
        target.innerHTML = '<div class="loading-state"><span class="spinner" aria-hidden="true"></span>Consultando importación…</div>';
        try {
            const result = await request(`/importaciones/${parsed}`);
            const item = result.data.importacion;
            const details = result.data.detalle || [];
            target.innerHTML = `<div class="import-result"><strong>${escapeHtml(item.nombre_archivo || `Importación #${item.id}`)}</strong><p>${escapeHtml(item.estado)} · ${Number(item.registros_aceptados || 0)} aceptados · ${Number(item.registros_rechazados || 0)} rechazados</p>${details.length ? `<ul>${details.slice(0, 12).map((line) => `<li>Fila ${Number(line.numero_fila)} · ${escapeHtml(line.resultado)}${line.error ? `: ${escapeHtml(line.error)}` : ''}</li>`).join('')}</ul>` : '<small>Sin detalle de filas.</small>'}</div>`;
        } catch (error) { target.innerHTML = `<div class="form-message" role="alert">${escapeHtml(error.message)}</div>`; }
    }

    async function loadAudit() {
        const tbody = $('[data-audit-table]');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5"><div class="loading-state"><span class="spinner" aria-hidden="true"></span>Cargando eventos…</div></td></tr>';
        try {
            const result = await request('/auditoria?limite=100');
            const rows = result.data || [];
            if (!rows.length) { renderErrorRow(tbody, 5, 'No hay eventos de auditoría disponibles.'); return; }
            tbody.innerHTML = rows.map((row) => `<tr><td>${escapeHtml(formatDate(row.fecha_hora))}</td><td>${row.usuario_id ? `#${Number(row.usuario_id)}` : 'Sistema'}</td><td>${escapeHtml(row.entidad)}${row.entidad_id ? ` #${Number(row.entidad_id)}` : ''}</td><td>${escapeHtml(row.accion)}</td><td>${escapeHtml(row.motivo || '—')}</td></tr>`).join('');
        } catch (error) { renderErrorRow(tbody, 5, error.message); }
    }

    function bindGlobalControls() {
        const appLayout = $('.app-layout');
        const menuButton = $('[data-menu-toggle]');
        const sidebar = $('#sidebar');
        const closeMenu = () => {
            appLayout?.classList.remove('menu-open');
            sidebar?.classList.remove('is-open');
            menuButton?.setAttribute('aria-expanded', 'false');
        };
        menuButton?.addEventListener('click', () => {
            const open = !sidebar.classList.contains('is-open');
            sidebar.classList.toggle('is-open', open);
            appLayout?.classList.toggle('menu-open', open);
            menuButton.setAttribute('aria-expanded', String(open));
        });
        appLayout?.addEventListener('click', (event) => { if (event.target === appLayout) closeMenu(); });
        sidebar?.addEventListener('click', (event) => { if (event.target.closest('a.side-link')) closeMenu(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeMenu(); });
        $$('[data-logout]').forEach((button) => button.addEventListener('click', async () => {
            button.disabled = true;
            try { await request('/sesion', { method: 'DELETE', body: {} }); window.location.assign(`${basePath}/`); }
            catch (error) { button.disabled = false; notify(error.message, true); }
        }));
    }

    function bindLogin() {
        const form = $('[data-login-form]');
        if (!form) return;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setMessage(form, '');
            if (!form.reportValidity()) return;
            const values = formValues(form);
            setLoading(form, true, 'Verificando…');
            try {
                await request('/sesion', { method: 'POST', body: { correo: values.correo.trim(), contrasena: values.contrasena }, skipCsrf: true });
                window.location.assign(`${basePath}/app`);
            } catch (error) { setMessage(form, error.message); }
            finally { setLoading(form, false); }
        });
    }

    async function boot() {
        bindGlobalControls();
        bindLogin();
        if (!root.hasAttribute('data-app-root')) return;
        try {
            await ensureSession();
            applyPermissions();
        } catch (error) {
            if (error.status !== 401) notify(error.message, true);
            return;
        }
        await Promise.all([
            bindDashboard(), bindAssetsPage(), bindOperationsPage(), bindInventoryPage(), bindAdminPage(), bindReportsPage(), bindSelectionPage(),
        ]);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
