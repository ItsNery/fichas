const profileRoot = document.querySelector('.municipio-v4');

if (profileRoot) {
    const sections = [...profileRoot.querySelectorAll('[data-section-url]')];
    const loadedSections = new Map();
    const chartLoader = { promise: null };

    const loadCharts = () => {
        if (window.echarts) return Promise.resolve(window.echarts);
        if (!chartLoader.promise) {
            chartLoader.promise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js';
                script.onload = () => resolve(window.echarts);
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        return chartLoader.promise;
    };

    const formatValue = (value) => {
        if (value === null || value === undefined || value === '') return 'N/D';
        if (typeof value === 'number') return value.toLocaleString('es-MX', { maximumFractionDigits: 2 });
        return String(value);
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));

    const chartOption = (chart) => {
        const aliases = { lineas: 'line', líneas: 'line', barras: 'bar', torta: 'pie', pastel: 'pie', donut: 'pie', dona: 'pie', mapa: 'map' };
        const chartType = aliases[chart.type || chart.tipo_grafico] || chart.type || chart.tipo_grafico || 'bar';

        if (chartType === 'piramide') {
            const categories = chart.eje_x?.categorias || chart.categorias || [];
            const men = chart.series?.[0]?.data || chart.hombres || [];
            const women = chart.series?.[1]?.data || chart.mujeres || [];
            return {
                animation: false,
                grid: { left: 12, right: 16, top: 20, bottom: 42, containLabel: true },
                tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, confine: true,
                    formatter: (points) => points.map((point) => `${point.marker}${point.seriesName}: ${Math.abs(point.value).toLocaleString('es-MX')}`).join('<br>') },
                legend: { bottom: 0 },
                xAxis: { type: 'value', name: chart.eje_y?.titulo || 'Habitantes', axisLabel: { formatter: (value) => Math.abs(value).toLocaleString('es-MX') } },
                yAxis: { type: 'category', data: categories, inverse: true, axisTick: { show: false } },
                series: [
                    { name: 'Hombres', type: 'bar', stack: 'total', data: men.map((value) => -Math.abs(value)), itemStyle: { color: '#0a192f' } },
                    { name: 'Mujeres', type: 'bar', stack: 'total', data: women.map((value) => Math.abs(value)), itemStyle: { color: '#c79b66' } },
                ],
            };
        }

        if (chartType === 'map') {
            return {
                tooltip: { trigger: 'item', confine: true },
                visualMap: { min: chart.min ?? 0, max: chart.max ?? 100, text: ['Alto', 'Bajo'], calculable: true, inRange: { color: ['#fdf2f2', '#861e34', '#5f1b2d'] } },
                series: [{ name: 'Datos', type: 'map', map: 'puebla', roam: true, data: chart.data || [], emphasis: { label: { show: true }, itemStyle: { areaColor: '#c79b66' } } }],
            };
        }

        if (chartType === 'bar-horizontal') {
            const categories = chart.eje_y?.categorias || [];
            const labelWidth = Math.min(220, Math.max(100, Math.max(...categories.map((category) => String(category).length), 0) * 6 + 20));
            const formatAxisNumber = (value) => {
                const absolute = Math.abs(value);
                if (absolute >= 1000000) return `${(value / 1000000).toLocaleString('es-MX', { maximumFractionDigits: 1 })} M`;
                if (absolute >= 1000) return `${(value / 1000).toLocaleString('es-MX', { maximumFractionDigits: 1 })} mil`;
                return value.toLocaleString('es-MX');
            };
            return {
                animation: false,
                tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, confine: true },
                grid: { left: labelWidth, right: 12, top: 20, bottom: 36, containLabel: false },
                xAxis: { type: 'value', splitNumber: 4, axisLabel: { hideOverlap: true, margin: 10, formatter: formatAxisNumber } },
                yAxis: { type: 'category', data: categories, inverse: true, axisLabel: { width: labelWidth - 12, overflow: 'truncate' } },
                series: (chart.series || []).map((series) => ({ ...series, type: 'bar' })),
            };
        }

        const isPie = chartType === 'pie';
        const isScatter = chartType === 'scatter';
        return {
            animation: false,
            grid: { left: 12, right: 16, top: 20, bottom: 42, containLabel: true },
            tooltip: { trigger: isPie ? 'item' : 'axis', confine: true },
            legend: { bottom: 0, type: 'scroll' },
            xAxis: isPie ? undefined : { type: isScatter ? 'value' : 'category', name: chart.eje_x?.titulo, nameLocation: 'middle', nameGap: 28, data: isScatter ? undefined : (chart.eje_x?.categorias || chart.categories || []) },
            yAxis: isPie ? undefined : { type: 'value', name: isScatter ? chart.eje_y?.titulo : undefined, nameLocation: 'middle', nameGap: 40 },
            series: (chart.series || []).map((series) => ({ ...series, type: chartType })),
        };
    };

    const renderChart = async (card, chart) => {
        const chartType = { mapa: 'map' }[chart?.type || chart?.tipo_grafico] || chart?.type || chart?.tipo_grafico;
        if (!chart || (chartType === 'map' ? !chart.data?.length : !chart.series?.length)) {
            const element = card.querySelector('[data-chart]');
            if (element) element.textContent = 'Visualización no disponible.';
            return;
        }
        try {
            const echarts = await loadCharts();
            if (chartType === 'map') {
                const response = await fetch(profileRoot.dataset.geojsonUrl);
                const geojson = await response.json();
                geojson.features.forEach((feature) => { feature.properties.name = feature.properties.nomgeo; });
                echarts.registerMap('puebla', geojson);
            }
            const element = card.querySelector('[data-chart]');
            if (!element) return;
            const instance = echarts.init(element);
            instance.setOption(chartOption(chart));
            window.addEventListener('resize', () => instance.resize(), { passive: true });
        } catch (error) {
            const element = card.querySelector('[data-chart]');
            if (element) element.textContent = 'No fue posible cargar la visualización.';
            console.error('Error cargando gráfica v4:', error);
        }
    };

    const cardTemplate = (item) => {
        const card = document.createElement('article');
        const isKpi = item.visualization === 'kpi';
        card.className = `municipio-v4__card${isKpi ? ' municipio-v4__card--kpi' : ''}`;
        card.innerHTML = `
            <div class="municipio-v4__card-head">
                <div>
                    <h3>${escapeHtml(item.title)}</h3>
                    ${item.subtitle ? `<p class="text-muted small mb-0 mt-1">${escapeHtml(item.subtitle)}</p>` : ''}
                </div>
                <span class="municipio-v4__quality">${escapeHtml(item.quality?.label || 'Disponible')}</span>
            </div>
            <div class="municipio-v4__card-value">${escapeHtml(formatValue(item.value))}</div>
            <div class="municipio-v4__card-meta">
                ${item.unit ? `<span>${escapeHtml(item.unit)}</span>` : ''}
                ${item.year ? `<span>Año ${escapeHtml(item.year)}</span>` : ''}
                ${item.quality?.year ? `<span>Referencia ${escapeHtml(item.quality.year)}</span>` : ''}
                ${item.quality?.coverage !== undefined ? `<span>Cobertura ${escapeHtml(item.quality.coverage)}/${escapeHtml(item.quality.expected)} (${escapeHtml(item.quality.coverage_percent)}%)</span>` : ''}
            </div>
            ${item.narrative ? `<div class="municipio-v4__card-narrative">${item.narrative}</div>` : ''}
            ${isKpi ? '' : `<div class="municipio-v4__chart" data-chart aria-label="Visualización de ${item.title}"></div>`}
            <details class="municipio-v4__details">
                <summary>Fuente y metodología</summary>
                <dl class="row mt-2 mb-0">
                    <dt class="col-sm-4">Fuente</dt><dd class="col-sm-8">${escapeHtml(item.source || 'No especificada')}</dd>
                    <dt class="col-sm-4">Definición</dt><dd class="col-sm-8">${escapeHtml(item.definition || 'No especificada')}</dd>
                    <dt class="col-sm-4">Método</dt><dd class="col-sm-8">${escapeHtml(item.method || 'No especificado')}</dd>
                </dl>
            </details>`;
        return card;
    };

    const loadSection = async (section) => {
        if (loadedSections.has(section)) return loadedSections.get(section);
        const request = fetch(section.dataset.sectionUrl, { headers: { Accept: 'application/json' } })
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((payload) => {
                const cards = section.querySelector('[data-section-cards]');
                const status = section.querySelector('[data-section-status]');
                cards.replaceChildren();
                payload.items.forEach((item) => {
                    const card = cardTemplate(item);
                    cards.appendChild(card);
                    if (item.visualization !== 'kpi') renderChart(card, item.data?.echarts || item.data);
                });
                status.hidden = true;
                section.dataset.sectionState = 'loaded';
            })
            .catch((error) => {
                const status = section.querySelector('[data-section-status]');
                status.innerHTML = 'No fue posible cargar esta dimensión. <button type="button" class="btn btn-link btn-sm p-0" data-retry-section>Reintentar</button>';
                section.dataset.sectionState = 'error';
                console.error('Error cargando sección v4:', error);
            });
        loadedSections.set(section, request);
        return request;
    };

    const observer = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            loadSection(entry.target);
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '300px 0px' });
    sections.forEach((section) => observer.observe(section));

    profileRoot.addEventListener('click', (event) => {
        if (!event.target.closest('[data-retry-section]')) return;
        const section = event.target.closest('[data-section-url]');
        loadedSections.delete(section);
        loadSection(section);
    });

    const search = profileRoot.querySelector('[data-peer-search]');
    const results = profileRoot.querySelector('[data-peer-results]');
    let searchTimer;
    if (search && results) {
        search.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const query = search.value.trim();
            if (query.length < 2) {
                results.hidden = true;
                results.replaceChildren();
                return;
            }
            searchTimer = setTimeout(async () => {
                const response = await fetch(`/ficha/municipio/v4/api/municipios?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
                const municipalities = await response.json();
                results.replaceChildren();
                municipalities.forEach((municipality) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = municipality.text;
                    button.setAttribute('role', 'option');
                    button.addEventListener('click', () => {
                        window.location.href = `/ficha/municipio/comparar/${profileRoot.dataset.municipioSlug}/${municipality.id}`;
                    });
                    results.appendChild(button);
                });
                results.hidden = municipalities.length === 0;
            }, 250);
        });
    }
}
