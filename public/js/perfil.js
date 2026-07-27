/**
 * Perfil Municipal - Lógica Frontend
 * Maneja la renderización de ECharts, mapas y scrollspy para la ficha municipal con carga diferida.
 */

const chartFontFamily = '"Gilroy-Regular", sans-serif';

document.addEventListener("DOMContentLoaded", function () {
    if (!window.FichaConfig) return;

    const chartFontsReady = document.fonts
        ? Promise.all([
              document.fonts.load('12px "Gilroy-Regular"'),
              document.fonts.ready,
          ])
        : Promise.resolve();

    const startProfile = () => {
        chartFontsReady.then(() => {
            initHeroMap();
            setupLazyCharts();
            setupScrollSpy();
            initPopovers();
        });
    };

    // Cargar mapa y renderizar todo
    fetch(window.FichaConfig.geojsonUrl)
        .then((response) => {
            if (!response.ok) throw new Error("GeoJSON no disponible");
            return response.json();
        })
        .then((pueblaJson) => {
            // Estandarizar nombres para ECharts
            pueblaJson.features.forEach((f) => {
                f.properties.name = f.properties.nomgeo.toUpperCase().trim();
            });
            echarts.registerMap("puebla", pueblaJson);

            startProfile();
        })
        .catch(() => startProfile());
});

const renderQueue = [];
let processingQueue = false;

function formatChartValue(value, maximumFractionDigits = 2) {
    const numericValue = Number(value);
    return Number.isFinite(numericValue)
        ? numericValue.toLocaleString("en-US", { maximumFractionDigits })
        : value;
}

function processNextInQueue() {
    if (renderQueue.length === 0) {
        processingQueue = false;
        if (window.isPdfExport) {
            window.__pdfReady = true;
        }
        return;
    }
    processingQueue = true;

    const task = renderQueue.shift();
    
    // Renderizar gráfico principal
    renderMainChart(task.itemData);
    task.chartElement.style.opacity = "1";

    // Remover skeleton loader
    const skeleton = document.getElementById("skeleton-" + task.chartId);
    if (skeleton) {
        skeleton.style.transition = "opacity 0.3s ease";
        skeleton.style.opacity = "0";
        setTimeout(() => skeleton.remove(), 300);
    }

    // Esperar un momento (retraso controlado de 40ms) antes de renderizar el siguiente gráfico
    setTimeout(() => {
        requestAnimationFrame(processNextInQueue);
    }, 40);
}

function setupLazyCharts() {
    if (!window.FichaConfig || !window.FichaConfig.perfilData) return;

    // 1. Gráficos principales
    const lazyCharts = document.querySelectorAll(".lazy-chart");

    if (window.isPdfExport) {
        lazyCharts.forEach((chartElement) => {
            const chartId = chartElement.getAttribute("data-chart-id");
            const itemData = findChartDataById(chartId);
            if (itemData) {
                renderQueue.push({ itemData, chartElement, chartId });
            }
        });
        if (!processingQueue) {
            processNextInQueue();
        }
    } else {
        const chartObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const chartElement = entry.target;
                        const chartId = chartElement.getAttribute("data-chart-id");
                        const itemData = findChartDataById(chartId);
                        if (itemData) {
                            renderQueue.push({ itemData, chartElement, chartId });
                            if (!processingQueue) {
                                processNextInQueue();
                            }
                        }
                        observer.unobserve(chartElement);
                    }
                });
            },
            {
                root: null,
                rootMargin: "150px 0px 150px 0px",
                threshold: 0.05,
            },
        );
        lazyCharts.forEach((chart) => chartObserver.observe(chart));
    }

    // 2. Micrográficas (Sparklines)
    const lazySparklines = document.querySelectorAll(".perfil-tarjeta__sparkline");

    if (window.isPdfExport) {
        lazySparklines.forEach((sparkElement) => {
            const chartId = sparkElement.getAttribute("data-chart-id");
            const itemData = findChartDataById(chartId);
            if (itemData) {
                renderSparkline(itemData);
                sparkElement.style.opacity = "1";
            }
        });
    } else {
        const sparkObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const sparkElement = entry.target;
                        const chartId = sparkElement.getAttribute("data-chart-id");
                        const itemData = findChartDataById(chartId);
                        if (itemData) {
                            renderSparkline(itemData);
                            sparkElement.style.opacity = "1";
                        }
                        observer.unobserve(sparkElement);
                    }
                });
            },
            {
                root: null,
                rootMargin: "50px 0px 50px 0px",
                threshold: 0.05,
            },
        );
        lazySparklines.forEach((spark) => sparkObserver.observe(spark));
    }

    setupStateRankings();
}

function normalizeSearchText(value) {
    return String(value || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
}

function setupStateRankings() {
    document.querySelectorAll(".state-ranking-search").forEach((input) => {
        input.addEventListener("input", () => {
            const table = document.getElementById(input.dataset.rankingTarget);
            if (!table) return;

            const query = normalizeSearchText(input.value);
            const rows = [...table.querySelectorAll("tbody tr")];
            let visible = 0;
            rows.forEach((row) => {
                const matches = !query || normalizeSearchText(row.dataset.rankingName).includes(query);
                row.hidden = !matches;
                if (matches) visible++;
            });

            const count = document.querySelector(`[data-ranking-count="${input.dataset.rankingTarget}"]`);
            if (count) {
                count.textContent = `${visible} de ${rows.length} municipios`;
            }

            let empty = table.parentElement.querySelector(".state-ranking-empty");
            if (!visible) {
                if (!empty) {
                    empty = document.createElement("p");
                    empty.className = "state-ranking-empty text-muted small py-3 mb-0";
                    empty.textContent = "No hay municipios que coincidan con la búsqueda.";
                    table.parentElement.appendChild(empty);
                }
            } else if (empty) {
                empty.remove();
            }
        });
    });
}

function findChartDataById(id) {
    if (!window.FichaConfig || !window.FichaConfig.perfilData) return null;
    const perfilData = window.FichaConfig.perfilData;
    let found = null;
    Object.values(perfilData).forEach((items) => {
        const match = items.find(
            (item) => String(item.config.id) === String(id),
        );
        if (match) {
            found = match;
        }
    });
    return found;
}

function renderMainChart(itemData) {
    var chartDom = document.getElementById("chart-" + itemData.config.id);
    if (!chartDom) return;

    var myChart = echarts.getInstanceByDom(chartDom);
    var isNew = false;
    if (!myChart) {
        myChart = echarts.init(chartDom, null, { renderer: "svg" });
        isNew = true;
    }
    var option = {};

    if (itemData.config.tipo_visualizacion === "piramide") {
        var d = itemData.datos;
        var hombres =
            d.series && d.series[0] ? d.series[0].data : d.hombres || [];
        var mujeres =
            d.series && d.series[1] ? d.series[1].data : d.mujeres || [];
        var categorias = d.eje_x ? d.eje_x.categorias : d.categorias || [];

        option = {
            tooltip: {
                trigger: "axis",
                axisPointer: { type: "shadow" },
                confine: true,
                formatter: (p) =>
                    p[0].name +
                    "<br/>" +
                    p
                        .map(
                            (x) =>
                                x.marker +
                                x.seriesName +
                                ": " +
                                formatChartValue(Math.abs(x.value), 0),
                        )
                        .join("<br/>"),
            },
            legend: { data: ["Hombres", "Mujeres"], bottom: 0 },
            grid: {
                left: "3%",
                right: "4%",
                bottom: "10%",
                containLabel: true,
            },
            xAxis: [
                {
                    type: "value",
                    axisLabel: {
                        formatter: (v) => formatChartValue(Math.abs(v), 0),
                    },
                },
            ],
            yAxis: [
                {
                    type: "category",
                    data: categorias,
                    axisTick: { show: false },
                    inverse: true,
                },
            ],
            series: [
                {
                    name: "Hombres",
                    type: "bar",
                    stack: "total",
                    data: hombres.map((v) => -Math.abs(v)),
                    itemStyle: { color: "#0a192f" },
                },
                {
                    name: "Mujeres",
                    type: "bar",
                    stack: "total",
                    data: mujeres.map((v) => Math.abs(v)),
                    itemStyle: { color: "#c79b66" },
                },
            ],
        };
    } else if (itemData.datos && itemData.datos.echarts) {
        var echartsData = itemData.datos.echarts;
        var tVis = itemData.config.tipo_visualizacion.toLowerCase();

        var typeMap = {
            lineas: "line",
            líneas: "line",
            line: "line",
            area: "line",
            barras: "bar",
            bar: "bar",
            pie: "pie",
            torta: "pie",
            pastel: "pie",
            donut: "pie",
            dona: "pie",
            mapa: "map",
            map: "map",
            treemap: "treemap",
            scatter: "scatter",
        };

        var chartType = echartsData.type || typeMap[tVis] || "bar";

        if (chartType === "map") {
            var currentMunName = window.FichaConfig.municipioNombre.trim();
            var cleanData = (echartsData.data || (echartsData.series && echartsData.series[0] && echartsData.series[0].data) || []).map((d) => {
                var nameUpper = d.name.toUpperCase().trim();
                var isCurrent = nameUpper === currentMunName;
                var item = { name: nameUpper, value: d.value };
                if (isCurrent) {
                    item.selected = true;
                    item.itemStyle = {
                        borderColor: "#c79b66",
                        borderWidth: 3,
                        shadowBlur: 10,
                        shadowColor: "rgba(199, 155, 102, 0.8)",
                        areaColor: "#861e34",
                    };
                }
                return item;
            });

            option = {
                tooltip: {
                    trigger: "item",
                    formatter: (params) => `${params.name}: ${params.value == null ? 'Sin dato' : formatChartValue(params.value)}`,
                    confine: true,
                },
                visualMap: {
                    min: echartsData.min || 0,
                    max: echartsData.max || 100,
                    text: ["Alto", "Bajo"],
                    realtime: false,
                    calculable: true,
                    inRange: { color: ["#fdf2f2", "#861e34", "#5f1b2d"] },
                    bottom: 20,
                    left: 20,
                },
                series: [
                    {
                        name: "Datos",
                        type: "map",
                        map: "puebla",
                        roam: true,
                        zoom: 1.2,
                        emphasis: {
                            label: { show: true },
                            itemStyle: { areaColor: "#c79b66" },
                        },
                        data: cleanData,
                    },
                ],
            };
        } else if (chartType === "scatter") {
            var extraScatter = null;
            if (itemData.config.ajustes_visuales) {
                try {
                    extraScatter = typeof itemData.config.ajustes_visuales === "string"
                        ? JSON.parse(itemData.config.ajustes_visuales)
                        : itemData.config.ajustes_visuales;
                } catch (e) {}
            }
            var normalColor = (extraScatter && extraScatter.otros_color) || '#c79b66';
            var highlightColor = (extraScatter && extraScatter.municipio_color) || '#861e34';
            var regionalContext = !window.FichaConfig.municipioNombre
                || window.FichaConfig.municipioNombre.toLowerCase() === 'regional';

            option = {
                tooltip: {
                    trigger: "item",
                    confine: true,
                    formatter: function (params) {
                        if (params.data && params.data.length >= 3) {
                            var xVal = params.data[0];
                            var yVal = params.data[1];
                            var munName = params.data[2];
                            var xTitle =
                                echartsData.eje_x && echartsData.eje_x.titulo
                                    ? echartsData.eje_x.titulo
                                    : "Variable X";
                            var yTitle =
                                echartsData.eje_y && echartsData.eje_y.titulo
                                    ? echartsData.eje_y.titulo
                                    : "Variable Y";

                            return (
                                `<div style="font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #ccc; padding-bottom: 3px;">${munName}</div>` +
                        `<span style="color:${highlightColor}; font-size: 14px;">●</span> ${xTitle}: <strong>${formatChartValue(xVal)}</strong><br/>` +
                        `<span style="color:${normalColor}; font-size: 14px;">●</span> ${yTitle}: <strong>${formatChartValue(yVal)}</strong>`
                            );
                        }
                        return params.value;
                    },
                },
                legend: { bottom: 0 },
                color: [normalColor, highlightColor],
                grid: { top: 40, bottom: 60, left: 60, right: 40 },
                xAxis: {
                    type: "value",
                    name:
                        echartsData.eje_x && echartsData.eje_x.titulo
                            ? echartsData.eje_x.titulo
                            : "Eje X",
                    nameLocation: "middle",
                    nameGap: 30,
                    splitLine: { show: true, lineStyle: { type: "dashed" } },
                },
                yAxis: {
                    type: "value",
                    name:
                        echartsData.eje_y && echartsData.eje_y.titulo
                            ? echartsData.eje_y.titulo
                            : "Eje Y",
                    nameLocation: "middle",
                    nameGap: 40,
                    splitLine: { show: true, lineStyle: { type: "dashed" } },
                },
                series: (echartsData.series || []).map((s) => {
                    const series = { ...s, type: "scatter", symbolSize: s.symbolSize || 10 };
                    if (!regionalContext) {
                        series.itemStyle = {
                            ...(s.itemStyle || {}),
                            color: function(params) {
                                var currentMunName = window.FichaConfig && window.FichaConfig.municipioNombre ? window.FichaConfig.municipioNombre.toUpperCase().trim() : '';
                                if (params.data && params.data[2] && params.data[2].toUpperCase().trim() === currentMunName) {
                                    return highlightColor;
                                }
                                return normalColor;
                            }
                        };
                    }
                    return series;
                }),
            };
        } else if (chartType === "bar-horizontal") {
            const compactChart = chartDom.clientWidth < 600;
            const yAxisLabelWidth = compactChart ? 120 : 165;
            option = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: { type: "shadow" },
                    confine: true,
                    formatter: function (params) {
                        if (!params || params.length === 0) return "";
                        let p = params[0];
                        let val = p.value;
                        if (typeof val === "object" && val !== null) {
                            val = val.value;
                        }
                        let formattedVal = formatChartValue(val);
                        let unidad = echartsData.unidad || "";
                        return `${p.name}<br/>${p.marker}${p.seriesName}: <strong>${formattedVal} ${unidad}</strong>`;
                    },
                },
                grid: {
                    top: 30,
                    bottom: 35,
                    left: compactChart ? 128 : 180,
                    right: compactChart ? 12 : 30,
                    containLabel: false,
                },
                animation: false,
                xAxis: {
                    type: "value",
                    splitLine: { show: true, lineStyle: { type: "dashed" } },
                    axisLabel: {
                        width: compactChart ? 82 : 135,
                        overflow: "truncate",
                        formatter: function (value) {
                            return formatChartValue(value);
                        },
                    },
                },
                yAxis: {
                    type: "category",
                    data: echartsData.eje_y ? echartsData.eje_y.categorias : [],
                    inverse: true,
                    axisTick: { show: false },
                    axisLabel: {
                        width: yAxisLabelWidth,
                        overflow: "truncate",
                        ellipsis: "...",
                        align: "right",
                        margin: 8,
                        formatter: function (value) {
                            if (!value) return "";
                            // Match pattern: "Name of Municipality (Rank°)"
                            const match = value.match(/^(.*?)\s*\(([^)]+)\)$/);
                            if (match) {
                                const name = match[1];
                                const rank = match[2];
                                const maxNameLength = compactChart ? 13 : 17;
                                if (name.length > maxNameLength) {
                                    return (
                                        name.substring(0, maxNameLength) +
                                        "... (" +
                                        rank +
                                        ")"
                                    );
                                }
                            } else {
                                const maxLabelLength = compactChart ? 16 : 20;
                                if (value.length > maxLabelLength) {
                                    return value.substring(0, maxLabelLength) + "...";
                                }
                            }
                            return value;
                        },
                    },
                },
                series: (echartsData.series || []).map((s) => {
                    s.type = "bar";
                    return s;
                }),
            };
        } else {
            option = {
                tooltip: {
                    trigger:
                        ["line", "bar", "area"].includes(chartType) ||
                        (echartsData.eje_x && echartsData.eje_x.categorias)
                            ? "axis"
                            : "item",
                    confine: true,
                    formatter: ["line", "bar"].includes(chartType)
                        ? function (params) {
                              const points = Array.isArray(params) ? params : [params];
                              const title = points[0]?.axisValue ?? points[0]?.name ?? "";
                              return title + "<br/>" + points
                                  .map((point) => `${point.marker}${point.seriesName}: ${formatChartValue(point.value)}`)
                                  .join("<br/>");
                          }
                        : chartType === "pie"
                            ? (params) => `${params.marker}${params.name}: <strong>${formatChartValue(params.value)}</strong> (${params.percent}%)`
                            : chartType === "treemap"
                                ? (params) => `${params.name}<br><strong>${formatChartValue(params.value)}</strong>${params.data?.percent == null ? '' : ` (${formatChartValue(params.data.percent)}%)`}`
                                : undefined,
                },
                legend: { bottom: 0 },
                grid: { top: 40, bottom: 60, left: 60, right: 30 },
                color: ["#861e34", "#c79b66", "#0a192f", "#444444", "#7a7a7a"],
                series: (echartsData.series || []).map((s) => {
                    s.type = chartType;
                    if (chartType === "line" || tVis.includes("area")) {
                        s.smooth = true;
                        s.symbol = "circle";
                        s.symbolSize = 8;
                        if (tVis.includes("area"))
                            s.areaStyle = { opacity: 0.2 };
                    }
                    return s;
                }),
            };

            if (["bar", "line", "area"].includes(chartType)) {
                const isNumericAxis = (echartsData.eje_x && echartsData.eje_x.type === "numeric")
                    || (echartsData.eje_y && echartsData.eje_y.type === "numeric");
                option.xAxis = {
                    type: isNumericAxis ? "value" : "category",
                    data:
                        !isNumericAxis && echartsData.eje_x && echartsData.eje_x.categorias
                            ? echartsData.eje_x.categorias
                            : [],
                    minInterval: isNumericAxis ? 1 : undefined,
                    boundaryGap: chartType === "line" ? false : undefined,
                    axisLabel: {
                        rotate:
                            echartsData.eje_x &&
                            echartsData.eje_x.categorias &&
                            echartsData.eje_x.categorias.length > 5
                                ? 30
                                : 0,
                    },
                };
                option.yAxis = {
                    type: "value",
                    axisLabel: { formatter: (value) => formatChartValue(value) },
                };
            }
        }
    } else {
        var rawData = itemData.datos || {};
        var vars = [];
        if (rawData.variables) {
            vars = rawData.variables;
        } else if (typeof rawData === "object" && !Array.isArray(rawData)) {
            vars = Object.keys(rawData).map((k) => ({
                nombre: k,
                valor: rawData[k],
            }));
        }

        option = {
            tooltip: { trigger: "axis", confine: true },
            grid: { top: 40, bottom: 80, left: 60, right: 30 },
            xAxis: {
                type: "category",
                data: vars.map((v) => v.nombre || v.name || ""),
                axisLabel: { rotate: 30 },
            },
            yAxis: { type: "value" },
            series: [
                {
                    data: vars.map((v) => v.valor || v.value || 0),
                    type: "bar",
                    itemStyle: { color: "#861e34" },
                },
            ],
        };
    }

    if (itemData.config.ajustes_visuales) {
        try {
            var extra =
                typeof itemData.config.ajustes_visuales === "string"
                    ? JSON.parse(itemData.config.ajustes_visuales)
                    : itemData.config.ajustes_visuales;
            Object.assign(option, extra);
        } catch (e) {}
    }

    option.textStyle = {
        ...(option.textStyle || {}),
        fontFamily: chartFontFamily,
    };

    myChart.setOption(option, true);
    if (isNew) {
        window.addEventListener("resize", () => myChart.resize());
    }
}

function renderSparkline(itemData) {
    var sparkDom = document.getElementById("sparkline-" + itemData.config.id);
    if (!sparkDom) return;

    var sparkChart = echarts.init(sparkDom, null, { renderer: "svg" });
    var trendData = itemData.datos.tendencia;

    var option = {
        grid: { left: 0, right: 0, top: 5, bottom: 5 },
        xAxis: {
            type: "category",
            show: false,
            data: trendData.map((t) => t.anio),
        },
        yAxis: { type: "value", show: false, min: "dataMin" },
        series: [
            {
                data: trendData.map((t) => t.valor),
                type: "line",
                smooth: true,
                symbol: "none",
                lineStyle: { color: "#861e34", width: 2 },
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: "rgba(134, 30, 52, 0.3)" },
                        { offset: 1, color: "rgba(134, 30, 52, 0)" },
                    ]),
                },
            },
        ],
    };

    sparkChart.setOption(option);
    window.addEventListener("resize", () => sparkChart.resize());
}

function initPopovers() {
    var popoverTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="popover"]'),
    );
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        if (popoverTriggerEl.classList.contains("similarity-popover-trigger")) {
            const popover = new bootstrap.Popover(popoverTriggerEl, {
                sanitize: false,
            });

            popoverTriggerEl.addEventListener(
                "inserted.bs.popover",
                function () {
                    const muniId =
                        popoverTriggerEl.getAttribute("data-municipio-id");
                    const configId =
                        popoverTriggerEl.getAttribute("data-config-id");
                    const popoverId =
                        popoverTriggerEl.getAttribute("aria-describedby");
                    const popoverBody = document.querySelector(
                        `#${popoverId} .popover-body`,
                    );

                    if (
                        popoverBody &&
                        !popoverTriggerEl.getAttribute("data-loaded")
                    ) {
                        fetch(
                            `/ficha/municipio/api/similitud-indicador/${muniId}/${configId}`,
                        )
                            .then((r) => r.json())
                            .then((data) => {
                                if (data.success) {
                                    let html = `<div class="similarity-popover text-start" style="min-width: 200px;">`;
                                    html += `<p class="mb-1 border-bottom pb-1 small" style="font-size: 11px;">Variable: <strong class="text-vino">${data.variable}</strong>${data.anio ? ` (${data.anio})` : ''}</p>`;
                                    html += `<p class="mb-2 small" style="font-size: 11px;">Valor actual: <strong class="text-gold" style="color: #c79b66;">${data.valor_actual}</strong></p>`;
                                    html += `<ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="padding-left: 0;">`;

                                    if (
                                        data.similares &&
                                        data.similares.length > 0
                                    ) {
                                        data.similares.forEach((sim) => {
                                            html += `<li class="d-flex justify-content-between align-items-center gap-3 border-bottom pb-1" style="font-size: 11px;">`;
                                            html += `  <div>`;
                                            html += `    <span class="fw-bold d-block" style="font-size: 11px; color: #861e34;">${sim.nombre}</span>`;
                                            html += `    <span class="text-muted" style="font-size: 10px;">Valor: ${sim.valor}</span>`;
                                            html += `  </div>`;
                                            html += `  <div class="d-flex gap-1">`;
                                            html += `    <a href="/ficha/municipio/${sim.slug}/perfil" class="btn btn-outline-secondary btn-xs rounded-pill" style="font-size: 9px; padding: 1px 6px;">Ver</a>`;
                                            html += `    <a href="/ficha/municipio/comparar/${window.FichaConfig.municipioSlug}/${sim.slug}" class="btn btn-vino btn-xs text-white rounded-pill" style="font-size: 9px; padding: 1px 6px; background-color: #861e34; border-color: #861e34;">Vs</a>`;
                                            html += `  </div>`;
                                            html += `</li>`;
                                        });
                                    } else {
                                        html += `<li class="text-muted small">No se encontraron similares</li>`;
                                    }

                                    html += `</ul></div>`;
                                    popoverBody.innerHTML = html;
                                    popoverTriggerEl.setAttribute(
                                        "data-loaded",
                                        "true",
                                    );
                                } else {
                                    popoverBody.innerHTML = `<div class="text-danger small">${data.message || "Error al cargar"}</div>`;
                                }
                            })
                            .catch((err) => {
                                console.error(err);
                                popoverBody.innerHTML = `<div class="text-danger small">Error de conexión</div>`;
                            });
                    }
                },
            );

            return popover;
        } else {
            return new bootstrap.Popover(popoverTriggerEl);
        }
    });
}

function initHeroMap() {
    var dom = document.getElementById("hero-map");
    if (!dom) return;

    var myChart = echarts.init(dom, null, { renderer: "svg" });
    var cvegeo = window.FichaConfig.cvegeo;
    var geo = echarts.getMap("puebla").geoJSON;
    var feature = geo.features.find(
        (f) => String(f.properties.cvegeo) == String(cvegeo),
    );

    myChart.setOption({
        backgroundColor: "transparent",
        animation: false,
        series: [
            {
                type: "map",
                map: "puebla",
                silent: true,
                roam: false,
                aspectScale: 1.0,
                layoutCenter: ["50%", "50%"],
                layoutSize: "100%",
                 label: { show: false },
                 emphasis: { label: { show: false } },
                 select: { label: { show: false } },
                 blur: { label: { show: false } },
                itemStyle: {
                    areaColor: "rgba(255, 255, 255, 0.3)",
                    borderColor: "rgba(255, 255, 255, 0.6)",
                    borderWidth: 1.5,
                },
                data: [
                    {
                        name: feature ? feature.properties.name : "",
                        selected: true,
                        itemStyle: {
                            areaColor: "#c79b66",
                            shadowBlur: 30,
                            shadowColor: "rgba(0,0,0,0.5)",
                            borderColor: "#fff",
                            borderWidth: 2,
                        },
                    },
                ],
            },
        ],
    });
}

function setupScrollSpy() {
    const sections = document.querySelectorAll(".section-perfil");
    const navLinks = document.querySelectorAll(".sticky-nav__link");

    window.onscroll = () => {
        let current = "";
        sections.forEach((s) => {
            const top = s.offsetTop;
            if (pageYOffset >= top - 150) {
                current = s.getAttribute("id");
            }
        });

        navLinks.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href").includes(current)) {
                link.classList.add("active");
            }
        });
    };
}

// Manejo del selector de navegación por años
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-year-selector");
    if (!btn) return;

    e.preventDefault();

    // Si ya está activo, no hacemos nada
    if (btn.classList.contains("active")) return;

    const year = btn.getAttribute("data-year");
    const configId = btn.getAttribute("data-config-id");
    const muniSlug = btn.getAttribute("data-muni-slug");

    // Encontrar todos los botones del mismo contenedor
    const container = btn.closest(".perfil-tarjeta__years-container");
    if (!container) return;
    const siblingBtns = container.querySelectorAll(".btn-year-selector");

    // Encontrar el elemento de la tarjeta padre
    const cardEl = btn.closest(".perfil-tarjeta");
    if (!cardEl) return;
    const chartDom = cardEl.querySelector(".perfil-tarjeta__chart-box");
    const narrativeEl = cardEl.querySelector(
        ".perfil-tarjeta__narrativa-texto",
    );
    const anioBadge = cardEl.querySelector(".perfil-tarjeta__anio-badge");
    const skeleton = cardEl.querySelector(".perfil-tarjeta__skeleton");

    // 1. Mostrar estado de carga (loading)
    if (skeleton) {
        skeleton.style.display = "flex";
    }
    const myChart = chartDom ? echarts.getInstanceByDom(chartDom) : null;
    if (myChart) {
        myChart.showLoading({
            text: "Cargando datos...",
            color: "#861e34",
            textColor: "#861e34",
            maskColor: "rgba(255, 255, 255, 0.7)",
            zlevel: 0,
        });
    }

    // 2. Realizar petición AJAX para obtener datos del año seleccionado
    fetch(`/ficha/municipio/api/grafico-datos/${muniSlug}/${configId}/${year}`)
        .then((response) => {
            if (!response.ok)
                throw new Error("Error en la respuesta del servidor");
            return response.json();
        })
        .then((res) => {
            if (res.success) {
                // 3. Cambiar botón activo visualmente
                siblingBtns.forEach((b) => {
                    b.classList.remove(
                        "active",
                        "btn-vino",
                        "text-white",
                        "border-vino",
                    );
                    b.classList.add("btn-outline-secondary");
                });
                btn.classList.remove("btn-outline-secondary");
                btn.classList.add(
                    "active",
                    "btn-vino",
                    "text-white",
                    "border-vino",
                );

                // 4. Actualizar Badge de año en el título
                if (anioBadge) {
                    anioBadge.textContent = year;
                }

                // 5. Actualizar Narrativa de forma fluida
                if (narrativeEl && res.narrativa) {
                    narrativeEl.style.opacity = 0;
                    setTimeout(() => {
                        narrativeEl.innerHTML = res.narrativa;
                        narrativeEl.style.opacity = 1;
                    }, 150);
                }

                // 6. Actualizar Gráfico
                if (myChart && res.datos) {
                    // Volver a renderizar llamando a renderMainChart pasándole la estructura actualizada
                    renderMainChart({
                        config: {
                            id: configId,
                            tipo_visualizacion: res.datos.echarts
                                ? res.datos.echarts.type
                                : "bar",
                        },
                        datos: res.datos,
                    });
                }
            }
        })
        .catch((error) => {
            console.error("Error al actualizar datos del año:", error);
        })
        .finally(() => {
            // Ocultar cargando
            if (skeleton) {
                skeleton.style.display = "none";
            }
            if (myChart) {
                myChart.hideLoading();
            }
        });
});
