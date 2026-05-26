/**
 * Perfil Municipal - Lógica Frontend
 * Maneja la renderización de ECharts, mapas y scrollspy para la ficha municipal.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (!window.FichaConfig) return;

    // Cargar mapa y renderizar todo
    fetch(window.FichaConfig.geojsonUrl)
        .then(response => response.json())
        .then(pueblaJson => {
            // Estandarizar nombres para ECharts
            pueblaJson.features.forEach(f => {
                f.properties.name = f.properties.nomgeo.toUpperCase().trim();
            });
            echarts.registerMap('puebla', pueblaJson);

            initHeroMap();
            renderAllCharts();
            setupScrollSpy();
            initPopovers();
        });
});

function renderAllCharts() {
    if (!window.FichaConfig || !window.FichaConfig.perfilData) return;

    const perfilData = window.FichaConfig.perfilData;

    // perfilData viene agrupado por seccion desde PHP
    Object.values(perfilData).forEach(items => {
        items.forEach(item => {
            if (item.config.tipo_visualizacion !== 'kpi' && item.datos) {
                renderMainChart(item);
            }

            if (item.config.tipo_visualizacion === 'kpi' && item.datos && item.datos.tendencia && item.datos.tendencia.length > 1) {
                renderSparkline(item);
            }
        });
    });
}

function renderMainChart(itemData) {
    var chartDom = document.getElementById('chart-' + itemData.config.id);
    if (!chartDom) return;

    var myChart = echarts.init(chartDom);
    var option = {};

    if (itemData.config.tipo_visualizacion === 'piramide') {
        var d = itemData.datos;
        var hombres = d.series && d.series[0] ? d.series[0].data : (d.hombres || []);
        var mujeres = d.series && d.series[1] ? d.series[1].data : (d.mujeres || []);
        var categorias = d.eje_x ? d.eje_x.categorias : (d.categorias || []);

        option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: (p) => p[0].name + '<br/>' + p.map(x => x.marker + x.seriesName + ': ' + Math.abs(x.value).toLocaleString()).join('<br/>')
            },
            legend: { data: ['Hombres', 'Mujeres'], bottom: 0 },
            grid: { left: '3%', right: '4%', bottom: '10%', containLabel: true },
            xAxis: [{ type: 'value', axisLabel: { formatter: (v) => Math.abs(v).toLocaleString() } }],
            yAxis: [{ type: 'category', data: categorias, axisTick: { show: false }, inverse: true }],
            series: [{
                name: 'Hombres', type: 'bar', stack: 'total', data: hombres.map(v => -Math.abs(v)), itemStyle: { color: '#0a192f' }
            }, {
                name: 'Mujeres', type: 'bar', stack: 'total', data: mujeres.map(v => Math.abs(v)), itemStyle: { color: '#c79b66' }
            }]
        };
    } else if (itemData.datos && itemData.datos.echarts) {
        var echartsData = itemData.datos.echarts;
        var tVis = itemData.config.tipo_visualizacion.toLowerCase();

        var typeMap = {
            'lineas': 'line', 'líneas': 'line', 'line': 'line', 'area': 'line',
            'barras': 'bar', 'bar': 'bar',
            'pie': 'pie', 'torta': 'pie', 'pastel': 'pie', 'donut': 'pie', 'dona': 'pie',
            'mapa': 'map', 'map': 'map', 'treemap': 'treemap'
        };

        var chartType = echartsData.type || typeMap[tVis] || 'bar';

        if (chartType === 'map') {
            var currentMunName = window.FichaConfig.municipioNombre.trim();
            var cleanData = (echartsData.data || []).map(d => {
                var nameUpper = d.name.toUpperCase().trim();
                var isCurrent = (nameUpper === currentMunName);
                var item = { name: nameUpper, value: d.value };
                if (isCurrent) {
                    item.selected = true;
                    item.itemStyle = {
                        borderColor: '#c79b66', borderWidth: 3, shadowBlur: 10, shadowColor: 'rgba(199, 155, 102, 0.8)', areaColor: '#861e34'
                    };
                }
                return item;
            });

            option = {
                tooltip: { trigger: 'item', formatter: '{b}: {c}' },
                visualMap: {
                    min: echartsData.min || 0, max: echartsData.max || 100, text: ['Alto', 'Bajo'], realtime: false, calculable: true,
                    inRange: { color: ['#fdf2f2', '#861e34', '#5f1b2d'] }, bottom: 20, left: 20
                },
                series: [{
                    name: 'Datos', type: 'map', map: 'puebla', roam: true, zoom: 1.2,
                    emphasis: { label: { show: true }, itemStyle: { areaColor: '#c79b66' } },
                    data: cleanData
                }]
            };
        } else {
            option = {
                tooltip: { trigger: (['line', 'bar', 'area'].includes(chartType) || (echartsData.eje_x && echartsData.eje_x.categorias)) ? 'axis' : 'item' },
                legend: { bottom: 0 },
                grid: { top: 40, bottom: 60, left: 60, right: 30 },
                color: ['#861e34', '#c79b66', '#0a192f', '#444444', '#7a7a7a'],
                series: (echartsData.series || []).map(s => {
                    s.type = chartType;
                    if (chartType === 'line' || tVis.includes('area')) {
                        s.smooth = true; s.symbol = 'circle'; s.symbolSize = 8;
                        if (tVis.includes('area')) s.areaStyle = { opacity: 0.2 };
                    }
                    return s;
                })
            };

            if (['bar', 'line', 'area'].includes(chartType)) {
                option.xAxis = {
                    type: 'category',
                    data: (echartsData.eje_x && echartsData.eje_x.categorias) ? echartsData.eje_x.categorias : [],
                    axisLabel: { rotate: (echartsData.eje_x && echartsData.eje_x.categorias && echartsData.eje_x.categorias.length > 5 ? 30 : 0) }
                };
                option.yAxis = { type: 'value' };
            }
        }
    } else {
        var rawData = itemData.datos || {};
        var vars = [];
        if (rawData.variables) {
            vars = rawData.variables;
        } else if (typeof rawData === 'object' && !Array.isArray(rawData)) {
            vars = Object.keys(rawData).map(k => ({ nombre: k, valor: rawData[k] }));
        }

        option = {
            tooltip: { trigger: 'axis' },
            grid: { top: 40, bottom: 80, left: 60, right: 30 },
            xAxis: { type: 'category', data: vars.map(v => v.nombre || v.name || ''), axisLabel: { rotate: 30 } },
            yAxis: { type: 'value' },
            series: [{ data: vars.map(v => v.valor || v.value || 0), type: 'bar', itemStyle: { color: '#861e34' } }]
        };
    }

    if (itemData.config.ajustes_visuales) {
        try {
            var extra = typeof itemData.config.ajustes_visuales === 'string' ? JSON.parse(itemData.config.ajustes_visuales) : itemData.config.ajustes_visuales;
            Object.assign(option, extra);
        } catch (e) { }
    }

    myChart.setOption(option);
    window.addEventListener('resize', () => myChart.resize());
}

function renderSparkline(itemData) {
    var sparkDom = document.getElementById('sparkline-' + itemData.config.id);
    if (!sparkDom) return;

    var sparkChart = echarts.init(sparkDom);
    var trendData = itemData.datos.tendencia;

    var option = {
        grid: { left: 0, right: 0, top: 5, bottom: 5 },
        xAxis: { type: 'category', show: false, data: trendData.map(t => t.anio) },
        yAxis: { type: 'value', show: false, min: 'dataMin' },
        series: [{
            data: trendData.map(t => t.valor),
            type: 'line',
            smooth: true,
            symbol: 'none',
            lineStyle: { color: '#861e34', width: 2 },
            areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: 'rgba(134, 30, 52, 0.3)' },
                    { offset: 1, color: 'rgba(134, 30, 52, 0)' }
                ])
            }
        }]
    };

    sparkChart.setOption(option);
    window.addEventListener('resize', () => sparkChart.resize());
}

function initPopovers() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

function initHeroMap() {
    var dom = document.getElementById('hero-map');
    if (!dom) return;

    var myChart = echarts.init(dom);
    var cvegeo = window.FichaConfig.cvegeo;
    var geo = echarts.getMap('puebla').geoJSON;
    var feature = geo.features.find(f => String(f.properties.cvegeo) == String(cvegeo));

    myChart.setOption({
        backgroundColor: 'transparent',
        animation: false,
        series: [{
            type: 'map',
            map: 'puebla',
            silent: true,
            roam: false,
            aspectScale: 1.0,
            layoutCenter: ['50%', '50%'],
            layoutSize: '100%',
            label: { show: false },
            itemStyle: {
                areaColor: 'rgba(255, 255, 255, 0.3)',
                borderColor: 'rgba(255, 255, 255, 0.6)',
                borderWidth: 1.5
            },
            data: [{
                name: feature ? feature.properties.name : '',
                selected: true,
                itemStyle: {
                    areaColor: '#c79b66',
                    shadowBlur: 30,
                    shadowColor: 'rgba(0,0,0,0.5)',
                    borderColor: '#fff',
                    borderWidth: 2
                }
            }]
        }]
    });
}

function setupScrollSpy() {
    const sections = document.querySelectorAll('.section-perfil');
    const navLinks = document.querySelectorAll('.nav-premium-link');

    window.onscroll = () => {
        let current = "";
        sections.forEach(s => {
            const top = s.offsetTop;
            if (pageYOffset >= top - 150) {
                current = s.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    };
}
