/**
 * Comparador Municipal - Lógica Frontend
 * Maneja la renderización de ECharts unificados, split-screen y lazy-loading para el comparador.
 */

document.addEventListener("DOMContentLoaded", function () {
    if (!window.ComparadorConfig) return;

    if (window.ComparadorConfig.geojsonUrl) {
        fetch(window.ComparadorConfig.geojsonUrl)
            .then((response) => response.json())
            .then((pueblaJson) => {
                // Estandarizar nombres para ECharts
                pueblaJson.features.forEach((f) => {
                    if (f.properties && f.properties.nomgeo) {
                        f.properties.name = f.properties.nomgeo
                            .toUpperCase()
                            .trim();
                    }
                });
                echarts.registerMap("puebla", pueblaJson);

                setupLazyCharts();
                setupScrollSpy();
                initPopovers();
                initCockpitForm();
            })
            .catch((err) => {
                console.error("Error loading GeoJSON map:", err);
                setupLazyCharts();
                setupScrollSpy();
                initPopovers();
                initCockpitForm();
            });
    } else {
        setupLazyCharts();
        setupScrollSpy();
        initPopovers();
        initCockpitForm();
    }
});

function initCockpitForm() {
    const form = document.getElementById("compare-selector-form");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const muni1 = document.getElementById("select-muni1").value;
            const muni2 = document.getElementById("select-muni2").value;
            if (muni1 && muni2) {
                window.location.href = `/ficha/municipio/comparar/${muni1}/${muni2}`;
            }
        });
    }
}

const renderQueue = [];
let processingQueue = false;

function processNextInQueue() {
    if (renderQueue.length === 0) {
        processingQueue = false;
        return;
    }
    processingQueue = true;

    const task = renderQueue.shift();
    
    if (task.type === 'combined') {
        renderCombinedChart(task.chartElement, task.itemData);
        task.chartElement.style.opacity = "1";

        const skeleton = document.getElementById("skeleton-combined-" + task.chartId);
        if (skeleton) {
            skeleton.style.transition = "opacity 0.3s ease";
            skeleton.style.opacity = "0";
            setTimeout(() => skeleton.remove(), 300);
        }
    } else if (task.type === 'separate') {
        renderSeparateChart(task.chartElement, task.itemData, task.muniNum);
        task.chartElement.style.opacity = "1";

        const skeleton = document.getElementById(`skeleton-sep${task.muniNum}-${task.chartId}`);
        if (skeleton) {
            skeleton.style.transition = "opacity 0.3s ease";
            skeleton.style.opacity = "0";
            setTimeout(() => skeleton.remove(), 300);
        }
    }

    setTimeout(() => {
        requestAnimationFrame(processNextInQueue);
    }, 40);
}

function setupLazyCharts() {
    // 1. Observer para gráficos combinados (Ambos municipios en el mismo lienzo)
    const combinedCharts = document.querySelectorAll(".lazy-chart-combined");
    const combinedObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const chartElement = entry.target;
                    const chartId = chartElement.getAttribute("data-chart-id");
                    const itemData = findChartDataById(chartId);

                    if (itemData) {
                        renderQueue.push({ type: 'combined', chartElement, chartId, itemData });
                        if (!processingQueue) {
                            processNextInQueue();
                        }
                    }
                    observer.unobserve(chartElement);
                }
            });
        },
        { rootMargin: "150px 0px" },
    );

    combinedCharts.forEach((chart) => combinedObserver.observe(chart));

    // 2. Observer para gráficos separados (Lado a Lado)
    const sepCharts = document.querySelectorAll(".lazy-chart-sep");
    const sepObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const chartElement = entry.target;
                    const chartId = chartElement.getAttribute("data-chart-id");
                    const muniNum = chartElement.getAttribute("data-muni");
                    const itemData = findChartDataById(chartId);

                    if (itemData) {
                        renderQueue.push({ type: 'separate', chartElement, chartId, itemData, muniNum });
                        if (!processingQueue) {
                            processNextInQueue();
                        }
                    }
                    observer.unobserve(chartElement);
                }
            });
        },
        { rootMargin: "150px 0px" },
    );

    sepCharts.forEach((chart) => sepObserver.observe(chart));

    // 3. Observer para sparklines de comparación
    const sparklines = document.querySelectorAll(".perfil-tarjeta__sparkline");
    const sparkObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const sparkElement = entry.target;
                    const chartId = sparkElement.getAttribute("data-chart-id");
                    const muniNum = sparkElement.getAttribute("data-muni");
                    const itemData = findChartDataById(chartId);

                    if (itemData) {
                        const trendData =
                            muniNum === "1"
                                ? itemData.datos1.tendencia
                                : itemData.datos2.tendencia;
                        if (trendData) {
                            renderSparkline(sparkElement, trendData, muniNum);
                            sparkElement.style.opacity = "1";
                        }
                    }
                    observer.unobserve(sparkElement);
                }
            });
        },
        { rootMargin: "50px 0px" },
    );

    sparklines.forEach((spark) => sparkObserver.observe(spark));
}

function findChartDataById(id) {
    if (!window.ComparadorConfig || !window.ComparadorConfig.comparativaData)
        return null;
    const comparativaData = window.ComparadorConfig.comparativaData;
    let found = null;
    Object.values(comparativaData).forEach((items) => {
        const match = items.find(
            (item) => String(item.config.id) === String(id),
        );
        if (match) {
            found = match;
        }
    });
    return found;
}

function renderCombinedChart(chartDom, itemData) {
    var myChart = echarts.init(chartDom);
    var echartsData = itemData.echarts_combinado;
    if (!echartsData) return;

    var chartType = echartsData.type || "bar";

    var option = {
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
        },
        legend: { bottom: 0 },
        grid: { top: 40, bottom: 60, left: 60, right: 30, containLabel: true },
        color: ["#861e34", "#6c757d", "#c79b66", "#0a192f"],
        xAxis: {
            type: "category",
            data:
                echartsData.eje_x && echartsData.eje_x.categorias
                    ? echartsData.eje_x.categorias
                    : [],
            axisLabel: {
                rotate:
                    echartsData.eje_x.categorias &&
                    echartsData.eje_x.categorias.length > 5
                        ? 30
                        : 0,
            },
        },
        yAxis: { type: "value" },
        series: (echartsData.series || []).map((s) => {
            s.type = chartType;
            if (
                chartType === "line" ||
                itemData.config.tipo_visualizacion
                    .toLowerCase()
                    .includes("area")
            ) {
                s.smooth = true;
                s.symbol = "circle";
                s.symbolSize = 8;
                if (
                    itemData.config.tipo_visualizacion
                        .toLowerCase()
                        .includes("area")
                ) {
                    s.areaStyle = { opacity: 0.2 };
                }
            }
            return s;
        }),
    };

    if (itemData.config.ajustes_visuales) {
        try {
            var extra =
                typeof itemData.config.ajustes_visuales === "string"
                    ? JSON.parse(itemData.config.ajustes_visuales)
                    : itemData.config.ajustes_visuales;
            Object.assign(option, extra);
        } catch (e) {}
    }

    myChart.setOption(option);
    window.addEventListener("resize", () => myChart.resize());
    chartDom.echartsInstance = myChart;
}

function renderSeparateChart(chartDom, itemData, muniNum) {
    var myChart = echarts.init(chartDom);
    var targetData = muniNum === "1" ? itemData.datos1 : itemData.datos2;
    if (!targetData) return;

    var tVis = itemData.config.tipo_visualizacion.toLowerCase();

    // Especial para pirámide poblacional
    if (tVis === "piramide") {
        var d = targetData;
        var hombres =
            d.series && d.series[0] ? d.series[0].data : d.hombres || [];
        var mujeres =
            d.series && d.series[1] ? d.series[1].data : d.mujeres || [];
        var categorias = d.eje_x ? d.eje_x.categorias : d.categorias || [];

        var option = {
            tooltip: {
                trigger: "axis",
                axisPointer: { type: "shadow" },
                formatter: (p) =>
                    p[0].name +
                    "<br/>" +
                    p
                        .map(
                            (x) =>
                                x.marker +
                                x.seriesName +
                                ": " +
                                Math.abs(x.value).toLocaleString(),
                        )
                        .join("<br/>"),
            },
            legend: { data: ["Hombres", "Mujeres"], bottom: 0 },
            grid: {
                left: "3%",
                right: "4%",
                bottom: "12%",
                containLabel: true,
            },
            xAxis: [
                {
                    type: "value",
                    axisLabel: {
                        formatter: (v) => Math.abs(v).toLocaleString(),
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
                    itemStyle: {
                        color: muniNum === "1" ? "#861e34" : "#495057",
                    },
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

        myChart.setOption(option);
        window.addEventListener("resize", () => myChart.resize());
        chartDom.echartsInstance = myChart;
        return;
    }

    var echartsData = targetData.echarts;
    if (!echartsData) {
        var rawData = targetData || {};
        var vars = rawData.variables || [];
        echartsData = {
            type: "bar",
            eje_x: { categorias: vars.map((v) => v.nombre) },
            series: [
                {
                    name:
                        muniNum === "1"
                            ? window.ComparadorConfig.municipio1.nombre
                            : window.ComparadorConfig.municipio2.nombre,
                    type: "bar",
                    data: vars.map((v) => v.valor),
                },
            ],
        };
    }

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
    var option = {};

    if (chartType === "map") {
        var currentMunName =
            muniNum === "1"
                ? window.ComparadorConfig.municipio1.nombre.trim()
                : window.ComparadorConfig.municipio2.nombre.trim();
        var cleanData = (echartsData.data || []).map((d) => {
            var nameUpper = d.name.toUpperCase().trim();
            var isCurrent = nameUpper === currentMunName;
            var item = { name: nameUpper, value: d.value };
            if (isCurrent) {
                item.selected = true;
                item.itemStyle = {
                    borderColor: muniNum === "1" ? "#c79b66" : "#6c757d",
                    borderWidth: 3,
                    shadowBlur: 10,
                    shadowColor: "rgba(0,0,0,0.3)",
                    areaColor: muniNum === "1" ? "#861e34" : "#495057",
                };
            }
            return item;
        });

        option = {
            tooltip: { trigger: "item", formatter: "{b}: {c}" },
            visualMap: {
                min: echartsData.min || 0,
                max: echartsData.max || 100,
                text: ["Alto", "Bajo"],
                calculable: true,
                inRange: {
                    color:
                        muniNum === "1"
                            ? ["#fdf2f2", "#861e34"]
                            : ["#f8f9fa", "#6c757d"],
                },
                bottom: 10,
                left: 10,
                itemHeight: 80,
            },
            series: [
                {
                    name: "Datos",
                    type: "map",
                    map: "puebla",
                    roam: true,
                    zoom: 1.2,
                    data: cleanData,
                },
            ],
        };
    } else if (chartType === "scatter") {
        option = {
            tooltip: {
                trigger: "item",
                formatter: function (params) {
                    if (params.data && params.data.length >= 3) {
                        var xVal = params.data[0];
                        var yVal = params.data[1];
                        var munName = params.data[2];
                        var xTitle =
                            echartsData.eje_x && echartsData.eje_x.titulo
                                ? echartsData.eje_x.titulo
                                : "Inversión";
                        var yTitle =
                            echartsData.eje_y && echartsData.eje_y.titulo
                                ? echartsData.eje_y.titulo
                                : "Indicador";

                        return (
                            `<div style="font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #ccc; padding-bottom: 3px;">${munName}</div>` +
                            `<span style="color:${muniNum === "1" ? "#861e34" : "#6c757d"}; font-size: 14px;">●</span> ${xTitle}: <strong>$${xVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong><br/>` +
                            `<span style="color:#c79b66; font-size: 14px;">●</span> ${yTitle}: <strong>${yVal.toLocaleString()}%</strong>`
                        );
                    }
                    return params.value;
                },
            },
            legend: { bottom: 0 },
            grid: {
                top: 40,
                bottom: 60,
                left: 60,
                right: 30,
                containLabel: true,
            },
            color: [muniNum === "1" ? "#861e34" : "#6c757d"],
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
                s.type = "scatter";
                s.symbolSize = s.symbolSize || 10;
                return s;
            }),
        };
    } else if (chartType === "treemap") {
        option = {
            tooltip: {
                trigger: "item",
                formatter: "{b}: <strong>{c}</strong>",
            },
            grid: { top: 15, bottom: 15, left: 15, right: 15 },
            color:
                muniNum === "1"
                    ? [
                          "#861e34",
                          "#9b2a41",
                          "#b1374e",
                          "#c7455d",
                          "#dd536c",
                          "#eb6981",
                      ]
                    : [
                          "#6c757d",
                          "#7b848c",
                          "#8b949c",
                          "#9ba4ad",
                          "#abb5be",
                          "#bcc6ce",
                      ],
            series: (echartsData.series || []).map((s) => {
                s.type = "treemap";
                s.roam = false;
                s.nodeClick = false;
                s.breadcrumb = { show: false };
                s.label = {
                    show: true,
                    formatter: "{b}\n{c}",
                };
                return s;
            }),
        };
    } else {
        var isAxis =
            ["bar", "line", "area"].includes(chartType) ||
            (echartsData.eje_x && echartsData.eje_x.categorias);
        option = {
            tooltip: {
                trigger: isAxis ? "axis" : "item",
            },
            legend: { bottom: 0 },
            grid: {
                top: 40,
                bottom: 60,
                left: 60,
                right: 30,
                containLabel: true,
            },
            color:
                muniNum === "1"
                    ? ["#861e34", "#c79b66", "#0a192f", "#495057", "#7a7a7a"]
                    : ["#6c757d", "#c79b66", "#0a192f", "#861e34", "#7a7a7a"],
            series: (echartsData.series || []).map((s) => {
                s.type = chartType;
                if (chartType === "line" || tVis.includes("area")) {
                    s.smooth = true;
                    s.symbol = "circle";
                    s.symbolSize = 8;
                    if (tVis.includes("area")) {
                        s.areaStyle = { opacity: 0.2 };
                    }
                }
                return s;
            }),
        };

        if (isAxis) {
            option.xAxis = {
                type: "category",
                data:
                    echartsData.eje_x && echartsData.eje_x.categorias
                        ? echartsData.eje_x.categorias
                        : [],
                axisLabel: {
                    rotate:
                        echartsData.eje_x &&
                        echartsData.eje_x.categorias &&
                        echartsData.eje_x.categorias.length > 5
                            ? 30
                            : 0,
                },
            };
            option.yAxis = { type: "value" };
        }
    }

    myChart.setOption(option);
    window.addEventListener("resize", () => myChart.resize());
    chartDom.echartsInstance = myChart;
}

function renderSparkline(sparkDom, trendData, muniNum) {
    var sparkChart = echarts.init(sparkDom);
    var color = muniNum === "1" ? "#861e34" : "#6c757d";
    var areaColorStart =
        muniNum === "1" ? "rgba(134, 30, 52, 0.3)" : "rgba(108, 117, 125, 0.3)";
    var areaColorEnd =
        muniNum === "1" ? "rgba(134, 30, 52, 0)" : "rgba(108, 117, 125, 0)";

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
                lineStyle: { color: color, width: 2 },
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: areaColorStart },
                        { offset: 1, color: areaColorEnd },
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
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
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
