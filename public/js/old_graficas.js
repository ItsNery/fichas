// En este el total estatal no jalaba
document.addEventListener("DOMContentLoaded", function () {
    // --- 1. REFERENCIAS A ELEMENTOS DEL DOM ---
    const municipioSelector = $("#municipio-selector");
    const indicatorLinks = document.querySelectorAll(".indicador-link");
    const chartContainer = document.getElementById("chart-container");
    const chartTitle = document.getElementById("chart-title");
    const metadataContainer = document.getElementById("metadata-container");
    const descriptionElement = document.getElementById("indicator-description");
    const sourceElement = document.getElementById("indicator-source");
    const availableYearsElement = document.getElementById(
        "indicator-available-years"
    );
    const yearSelectorContainer = document.getElementById(
        "year-selector-container"
    );
    const yearSelector = document.getElementById("year-selector");
    const resumenBtn = document.getElementById("resumen-btn");
    let chart;

    // --- 2. EL "CEREBRO" O ESTADO CENTRAL ---
    const appState = {
        indicatorId: null,
        municipioIds: [],
        year: null,
        isLoading: false,
    };

    // --- INICIALIZACIÓN DE SELECT2 ---
    municipioSelector.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona municipio(s)",
        maximumSelectionLength: 2,
        language: "es",
    });

    // --- Funciones de ayuda (sin cambios) ---
    function expandirAcordeonHacia(linkElemento) {
        if (!linkElemento) return;
        const dimensionTargetId = linkElemento.dataset.dimensionTarget;
        const tematicaTargetId = linkElemento.dataset.tematicaTarget;
        if (dimensionTargetId) {
            const bsCollapse = new bootstrap.Collapse(
                document.querySelector(dimensionTargetId),
                {
                    toggle: false,
                }
            );
            bsCollapse.show();
        }
        if (tematicaTargetId) {
            const bsCollapse = new bootstrap.Collapse(
                document.querySelector(tematicaTargetId),
                {
                    toggle: false,
                }
            );
            bsCollapse.show();
        }
    }
    
    function renderizarGrafico(datosParaGrafico) {
        chartContainer.innerHTML = "";
        chartTitle.innerText = datosParaGrafico.titulo;
        metadataContainer.style.display = "block";
        descriptionElement.innerText =
            datosParaGrafico.descripcion || "No disponible.";
        sourceElement.innerText = datosParaGrafico.fuente || "No disponible.";

        // --- Lógica del selector de año (sin cambios) ---
        if (
            datosParaGrafico.available_years &&
            datosParaGrafico.available_years.length > 0
        ) {
            yearSelectorContainer.style.display = "block";
            availableYearsElement.innerText =
                datosParaGrafico.available_years.join(", ");
            yearSelector.innerHTML = "";
            datosParaGrafico.available_years.forEach((year) =>
                yearSelector.add(new Option(year, year))
            );
            const yearInTitle = datosParaGrafico.titulo.match(
                /\(Comparación Año: (\d{4})\)/
            );
            if (yearInTitle) yearSelector.value = yearInTitle[1];
        } else {
            yearSelectorContainer.style.display = "none";
            availableYearsElement.innerText = "N/A";
        }

        let options = {};
        if (datosParaGrafico.tipo_grafico === "piramide") {
            options = {
                series: datosParaGrafico.series,
                chart: {
                    type: "bar",
                    height: 440,
                    stacked: true,
                },
                colors: ["#008FFB", "#FF4560"],
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: "80%",
                    },
                },
                xaxis: {
                    categories: datosParaGrafico.eje_x.categorias,
                    title: {
                        text: "Número de Habitantes",
                    },
                    labels: {
                        formatter: (value) => Math.abs(value),
                    },
                },
                yaxis: {
                    title: {
                        text: "Grupos de Edad",
                    },
                },
                tooltip: {
                    shared: false,
                    y: {
                        formatter: (value) => Math.abs(value) + " personas",
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    width: 1,
                    colors: ["#fff"],
                },
                grid: {
                    xaxis: {
                        lines: {
                            show: false,
                        },
                    },
                },
                noData: {
                    text: "No hay datos disponibles para esta selección.",
                },
            };
        } else {
            // 1. Construimos la configuración del eje X
            let xaxisOptions = {
                type: "category",
                categories: datosParaGrafico.eje_x.categorias || [],
            };

            // Si es un gráfico de líneas, sobreescribimos la configuración
            if (datosParaGrafico.tipo_grafico === "line") {
                xaxisOptions = {
                    type: "numeric",
                    title: {
                        text: datosParaGrafico.eje_x.titulo,
                    },
                };
            }
            // Para gráficos de LÍNEAS y BARRAS
            options = {
                series: datosParaGrafico.series,
                chart: {
                    type: datosParaGrafico.tipo_grafico,
                    height: 450,
                    toolbar: {
                        show: true,
                    },
                },
                xaxis: xaxisOptions,
                yaxis: {
                    title: {
                        text: datosParaGrafico.eje_y.titulo,
                    },
                    labels: {
                        formatter: (value) =>
                            new Intl.NumberFormat("es-MX").format(value),
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    curve: "smooth",
                    width: 2,
                },
                noData: {
                    text: "No hay datos disponibles para esta selección.",
                },
            };
        }

        if (chart) chart.destroy();
        chart = new ApexCharts(chartContainer, options);
        chart.render();
    }

    /**
     * 3. FUNCIÓN PRINCIPAL QUE ORQUESTA TODO
     */
    function updateDashboard() {
        if (
            appState.isLoading ||
            !appState.indicatorId ||
            appState.municipioIds.length === 0
        ) {
            return;
        }

        appState.isLoading = true;
        chartTitle.innerText = "Cargando...";
        chartContainer.innerHTML =
            '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

        fetch("{{ route('api.data') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
            },
            body: JSON.stringify({
                indicador_id: appState.indicatorId,
                municipio_ids: appState.municipioIds,
                anio: appState.year,
            }),
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response)
            )
            .then((data) => renderizarGrafico(data))
            .catch((error) => {
                console.error("Error al cargar datos:", error);
                chartContainer.innerHTML =
                    '<p class="text-danger text-center pt-5">Hubo un error al cargar la información.</p>';
            })
            .finally(() => (appState.isLoading = false));
    }

    // --- 4. EVENT LISTENERS SIMPLIFICADOS ---

    indicatorLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            appState.indicatorId = e.target.dataset.indicatorId; // Actualiza el estado
            indicatorLinks.forEach((el) =>
                el.classList.remove("fw-bold", "text-primary")
            );
            e.target.classList.add("fw-bold", "text-primary");
            updateDashboard(); // Llama a la función principal
        });
    });

    municipioSelector.on("change", function () {
        const selection = $(this).val() || [];

        if (selection.length > 1 && selection.includes("estatal")) {
            appState.municipioIds = selection.filter((id) => id !== "estatal");
            $(this).val(appState.municipioIds).trigger("change.select2");
            return;
        }

        if (selection.length === 0) {
            appState.municipioIds = ["estatal"];
            $(this).val(appState.municipioIds).trigger("change.select2");
            return;
        }

        appState.municipioIds = selection;
        appState.year = null;
        updateDashboard();
    });

    yearSelector.addEventListener("change", function () {
        appState.year = this.value;
        updateDashboard();
    });

    // --- 5. CARGA INICIAL CONTROLADA ---
    function CargaInicial() {
        if (municipioSelector.find("option[value='estatal']").length === 0) {
            const estatalOption = new Option("-- Total Estatal --", "estatal");
            municipioSelector.prepend(estatalOption);
        }

        appState.municipioIds = ["estatal"];
        municipioSelector.val(appState.municipioIds).trigger("change.select2");

        const firstIndicatorLink = document.querySelector(".indicador-link");
        if (firstIndicatorLink) {
            appState.indicatorId = firstIndicatorLink.dataset.indicatorId;
            firstIndicatorLink.classList.add("fw-bold", "text-primary");
            expandirAcordeonHacia(firstIndicatorLink);
            updateDashboard();
        }
    }

    CargaInicial();
});
