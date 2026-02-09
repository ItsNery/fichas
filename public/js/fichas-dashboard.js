document.addEventListener("DOMContentLoaded", function () {
    // --- 0. SETUP INICIAL ---
    const appContainer = document.querySelector("[data-api-url]");
    const API_URL = appContainer.dataset.apiUrl;
    const EXPORT_URL = appContainer.dataset.exportUrl;
    const CSRF_TOKEN = appContainer.dataset.csrfToken;
    let opcionEstatalElement = null;

    // --- 1. REFERENCIAS A ELEMENTOS DEL DOM (COMPLETO) ---
    const exportBtn = document.getElementById("export-btn");
    const exportBtnRegions = document.getElementById("export-btn-regions");
    const municipioSelector = $("#municipio-selector");
    const microrregionSelector = $("#microrregion-selector");
    const macrorregionSelector = $("#macrorregion-selector");
    const yearSelectorEl = $("#year-selector");

    const microrregionContainer = document.getElementById(
        "microrregion-selector-container"
    );
    const macrorregionContainer = document.getElementById(
        "macrorregion-selector-container"
    );
    const nivelTabs = document.querySelectorAll("#pills-tab-nivel .nav-link");
    const accordionMunicipal = document.getElementById("accordionDimensions");
    const accordionRegionsContainer = document.getElementById(
        "accordionDimensionsRegions"
    );

    const chartContainer = document.getElementById("chart-container");
    const chartTitle = document.getElementById("chart-title");
    const resumenBtn = document.getElementById("resumen-btn");
    const resumenUrlPrototype = resumenBtn ? resumenBtn.href : "";

    const chartContainerRegions = document.getElementById(
        "chart-container-regions"
    );
    const chartTitleRegions = document.getElementById("chart-title-regions");

    const metadataContainer = document.getElementById("metadata-container");
    const descriptionElement = document.getElementById("indicator-description");
    const sourceElement = document.getElementById("indicator-source");
    const methodElement = document.getElementById("indicator-method");
    const availableYearsElement = document.getElementById(
        "indicator-available-years"
    );
    const yearSelectorContainer = document.getElementById(
        "year-selector-container"
    );
    const yearSelector = document.getElementById("year-selector");
    const chartNoteContainer = document.getElementById("chart-note-container");
    // Selectores de años en regiones
    const yearSelectorElRegions = $("#year-selector-regions");
    const metadataContainerRegions = document.getElementById(
        "metadata-container-regions"
    );
    const descriptionElementRegions = document.getElementById(
        "indicator-description-regions"
    );
    const sourceElementRegions = document.getElementById(
        "indicator-source-regions"
    );
    const methodElementRegions = document.getElementById(
        "indicator-method-regions"
    );
    const yearSelectorContainerRegions = document.getElementById(
        "year-selector-container-regions"
    );
    const chartNoteContainerRegions = document.getElementById(
        "chart-note-container-regions"
    );
    const availableYearsElementRegions = document.getElementById(
        "indicator-available-years-regions"
    );
    let chart;

    // Variables para el mapa
    let map = null;
    let geojsonLayer = null;
    let pueblaGeoJSON = null; // Almacenaremos el GeoJSON aquí para no recargarlo
    const mapContainer = document.getElementById("map-container");
    const mapLegend = document.getElementById("map-legend");

    // --- 2. PALETA DE COLORES ---
    const PALETA_COLORES = [
        "#264653",
        "#2a9d8f",
        "#e9c46a",
        "#f4a261",
        "#e76f51",
        "#6a040f",
        "#0077b6",
    ];

    // --- 3. EL "CEREBRO" O ESTADO CENTRAL ---
    const appState = {
        nivelDeAgregacion: "municipio",
        indicatorId: null,
        municipioIds: [],
        microrregionId: null,
        macrorregionId: null,
        selectedYears: [],
        isLoading: false,
    };

    // --- 3. PREPARACIÓN DE LA INTERFAZ ---

    // Clona el acordeón para la vista regional, asegurando que los IDs no se dupliquen
    // y así los colapsables funcionen de forma independiente.
    if (accordionMunicipal) {
        let clonedAccordionHTML = accordionMunicipal.innerHTML;
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /collapse-dimension-/g,
            "collapse-dimension-region-"
        );
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /collapse-tematica-/g,
            "collapse-tematica-region-"
        );
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /accordionTematicas-/g,
            "accordionTematicas-region-"
        );
        accordionRegionsContainer.innerHTML = clonedAccordionHTML;
    }
    // --- 4. INICIALIZACIÓN DE COMPONENTES ---

    // Inicializa Select2 para todos los selectores
    municipioSelector.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona municipio(s)",
        maximumSelectionLength: 2,
    });
    microrregionSelector.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona una microrregión",
    });
    macrorregionSelector.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona una macrorregión",
    });

    yearSelectorEl.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona año(s)",
        closeOnSelect: false,
    });

    yearSelectorElRegions.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona año(s)",
        closeOnSelect: false,
    });

    // --- 5. FUNCIONES DE AYUDA ---
    /**
     * Muestra el mapa centrado en un solo municipio.
     */
    function displaySingleMunicipalityMap(municipioId) {
        if (!pueblaGeoJSON || !map) return;
        if (geojsonLayer) map.removeLayer(geojsonLayer);
        mapLegend.innerHTML = ""; // No necesitamos leyenda para un solo municipio

        let found = false;
        geojsonLayer = L.geoJSON(pueblaGeoJSON, {
            style: (feature) => ({
                fillColor:
                    feature.properties.cvegeo == municipioId
                        ? "#0d6efd"
                        : "#ccc",
                weight: feature.properties.cvegeo == municipioId ? 2 : 1,
                color: "white",
                fillOpacity: 0.7,
            }),
            onEachFeature: (feature, layer) => {
                if (feature.properties.cvegeo == municipioId) {
                    map.fitBounds(layer.getBounds());
                    found = true;
                }
                layer.bindPopup(
                    `<strong>${feature.properties.nomgeo}</strong>`
                );
            },
        }).addTo(map);

        if (!found)
            console.warn(
                `No se encontró el polígono para el municipio con cvegeo: ${municipioId}`
            );
    }
    /**
     * Inicializa el objeto del mapa y carga el GeoJSON una sola vez.
     */
    async function initMap() {
        if (map) return;
        map = L.map("map").setView([19.0414, -98.2063], 8);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        try {
            const response = await fetch(
                "/geojson/puebla_municipios_wgs84.geojson"
            ); // Asegúrate que el nombre sea correcto
            pueblaGeoJSON = await response.json();
            console.log("GeoJSON de Puebla cargado y listo.");
        } catch (error) {
            console.error(
                "Error crítico: No se pudo cargar el archivo GeoJSON.",
                error
            );
            if (mapContainer)
                mapContainer.innerHTML =
                    "<p class='text-danger'>No se pudo cargar la cartografía del mapa.</p>";
        }
    }
    /**
     * Construye el payload y llama al endpoint de exportación para descargar el CSV.
     */
    function handleExport() {
        console.log("Iniciando exportación...");
        // 1. Construimos el mismo payload que para generar el gráfico
        let payload = {
            indicador_id: appState.indicatorId,
            anios: appState.selectedYears,
            nivel_de_agregacion: appState.nivelDeAgregacion,
        };

        if (appState.nivelDeAgregacion === "municipio") {
            payload.municipio_ids = appState.municipioIds;
        } else if (appState.nivelDeAgregacion === "microrregion") {
            payload.region_id = appState.microrregionId;
        } else if (appState.nivelDeAgregacion === "macrorregion") {
            payload.region_id = appState.macrorregionId;
        }

        // 2. Hacemos la llamada fetch, esperando un archivo (blob) como respuesta
        fetch(EXPORT_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
        })
            .then((response) => {
                if (!response.ok)
                    throw new Error(
                        "La respuesta del servidor no fue exitosa."
                    );
                return response.blob(); // Convertimos la respuesta en un objeto de archivo
            })
            .then((blob) => {
                // 3. Creamos un link temporal en memoria para iniciar la descarga
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.style.display = "none";
                a.href = url;
                a.download = "exportacion-datos.csv"; // El nombre real lo define el backend
                document.body.appendChild(a);
                a.click(); // Simulamos un clic para que se descargue
                window.URL.revokeObjectURL(url); // Liberamos la memoria
                a.remove();
            })
            .catch((err) => console.error("Error al exportar:", err));
    }
    
    /**
     * Resetea un acordeón a su estado inicial, cerrando todos los items
     * y expandiendo únicamente el primero.
     * @param {HTMLElement} accordionElement El elemento del acordeón a resetear.
     */
    function resetearYExpandirPrimerAcordeon(accordionElement) {
        if (!accordionElement) return;

        // 1. Primero, cerramos todos los items.
        const todosLosBotones =
            accordionElement.querySelectorAll(".accordion-button");
        const todosLosPaneles = accordionElement.querySelectorAll(
            ".accordion-collapse"
        );

        todosLosBotones.forEach((boton) => boton.classList.add("collapsed"));
        todosLosPaneles.forEach((panel) => panel.classList.remove("show"));

        // 2. Después, abrimos solo el primer item.
        const primerBoton = accordionElement.querySelector(".accordion-button");
        const primerPanel = accordionElement.querySelector(
            ".accordion-collapse"
        );

        if (primerBoton && primerPanel) {
            primerBoton.classList.remove("collapsed");
            primerPanel.classList.add("show");
        }
    }
    /**
     * Revisa el acordeón de regiones y oculta las temáticas que no tienen indicadores visibles.
     */
    function ocultarTematicasVacias() {
        // Obtenemos todos los acordeones de temáticas en la vista de regiones
        const tematicas = document.querySelectorAll(
            "#accordionDimensionsRegions .accordion-item"
        );

        tematicas.forEach((tematica) => {
            // Buscamos si dentro de esta temática hay algún indicador (<li>) que esté visible
            const indicadorVisible = tematica.querySelector(
                'li[style*="display: block"]'
            );

            if (indicadorVisible) {
                // Si encuentra al menos uno, se asegura de que la temática esté visible
                tematica.style.display = "block";
            } else {
                // Si no encuentra ninguno, oculta toda la temática
                tematica.style.display = "none";
            }
        });
    }
    function expandirAcordeonHacia(linkElemento) {
        if (!linkElemento) return;
        const dimensionTargetId = linkElemento.dataset.dimensionTarget.replace(
            "#",
            ""
        );
        const tematicaTargetId = linkElemento.dataset.tematicaTarget.replace(
            "#",
            ""
        );

        // Usamos los IDs limpios para buscar en el documento y activar el colapsable
        const dimensionEl =
            document.getElementById(dimensionTargetId) ||
            document.getElementById(
                dimensionTargetId.replace("dimension", "dimension-region")
            );
        const tematicaEl =
            document.getElementById(tematicaTargetId) ||
            document.getElementById(
                tematicaTargetId.replace("tematica", "tematica-region")
            );

        if (dimensionEl) {
            const bsCollapse = new bootstrap.Collapse(dimensionEl, {
                toggle: false,
            });
            bsCollapse.show();
        }
        if (tematicaEl) {
            const bsCollapse = new bootstrap.Collapse(tematicaEl, {
                toggle: false,
            });
            bsCollapse.show();
        }
    }
    /**
     * Filtra la lista de indicadores para la vista regional.
     * @param {boolean} showAll Si es true, muestra todos los indicadores. Si es false, muestra solo los de tipo 'Absoluto'.
     */
    function filtrarAcordeonRegional(showAll = false) {
        const links = document.querySelectorAll(
            "#accordionDimensionsRegions .indicador-link"
        );
        links.forEach((link) => {
            // Seleccionamos el <li> padre para ocultarlo completamente
            const li = link.closest("li");
            if (
                showAll ||
                (link.dataset.tipoDato &&
                    link.dataset.tipoDato.toLowerCase() === "absoluto")
            ) {
                li.style.display = "block";
            } else {
                li.style.display = "none";
            }
        });
    }

    // --- 6. EVENT LISTENERS PRINCIPALES ---
    exportBtn.addEventListener("click", handleExport);
    exportBtnRegions.addEventListener("click", handleExport);
    /**
     * Construye el payload y llama al endpoint de exportación para descargar el CSV.
     */
    function handleExport() {
        console.log("Iniciando exportación...");
        // 1. Deshabilitamos el botón para prevenir dobles clics
        const activeExportBtn =
            appState.nivelDeAgregacion === "municipio"
                ? exportBtn
                : exportBtnRegions;
        activeExportBtn.disabled = true;
        activeExportBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Exportando...`;

        // 2. Construimos el mismo payload que para generar el gráfico
        let payload = {
            indicador_id: appState.indicatorId,
            anios: appState.selectedYears,
            nivel_de_agregacion: appState.nivelDeAgregacion,
        };

        if (appState.nivelDeAgregacion === "municipio") {
            payload.municipio_ids = appState.municipioIds;
        } else if (appState.nivelDeAgregacion === "microrregion") {
            payload.region_id = appState.microrregionId;
        } else if (appState.nivelDeAgregacion === "macrorregion") {
            payload.region_id = appState.macrorregionId;
        }

        // 3. Hacemos la llamada fetch, esperando un archivo (blob) como respuesta
        fetch(EXPORT_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        "La respuesta del servidor no fue exitosa."
                    );
                }
                return response.blob(); // Convertimos la respuesta en un objeto de archivo
            })
            .then((blob) => {
                // 4. Creamos un link temporal en memoria para iniciar la descarga
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.style.display = "none";
                a.href = url;
                a.download = "exportacion-datos.csv"; // El nombre real lo define el backend
                document.body.appendChild(a);
                a.click(); // Simulamos un clic para que se descargue
                window.URL.revokeObjectURL(url); // Liberamos la memoria
                a.remove();
            })
            .catch((err) => {
                console.error("Error al exportar:", err);
                alert("Hubo un error al generar el archivo de exportación.");
            })
            .finally(() => {
                // 5. Reactivamos el botón
                activeExportBtn.disabled = false;
                activeExportBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download me-1" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
            Exportar (CSV)`;
            });
    }
    /**
     * Listener para las pestañas principales (Municipio, Micro, Macro).
     * Se dispara cuando una nueva pestaña ha sido mostrada.
     */
    nivelTabs.forEach((tab) => {
        tab.addEventListener("shown.bs.tab", function (event) {
            const nivel = event.target.dataset.nivel;
            console.log(`Cambiando a nivel: ${nivel}`);

            // 1. Actualizamos el estado central
            appState.nivelDeAgregacion = nivel;
            appState.indicatorId = null; // Limpiamos el indicador seleccionado
            // Limpiamos visualmente el gráfico anterior
            const activeChartContainer =
                nivel === "municipio" ? chartContainer : chartContainerRegions;
            const activeChartTitle =
                nivel === "municipio" ? chartTitle : chartTitleRegions;
            activeChartTitle.innerText = "Selecciona un indicador";
            activeChartContainer.innerHTML = `<p class="text-muted text-center pt-5">Selecciona un indicador y una ${nivel} para comenzar.</p>`;
            todosLosIndicadores.forEach((el) =>
                el.classList.remove("fw-bold", "text-primary")
            );

            // 2. Mostramos/ocultamos los contenedores de selectores
            microrregionContainer.style.display =
                nivel === "microrregion" ? "block" : "none";
            macrorregionContainer.style.display =
                nivel === "macrorregion" ? "block" : "none";

            // 3. Filtramos el acordeón de la vista regional
            if (nivel !== "municipio") {
                filtrarAcordeonRegional(); // Oculta los indicadores no-absolutos
                ocultarTematicasVacias();

                // 4. Pre-populamos el estado con la primera región de la lista para que no esté vacío
                if (nivel === "microrregion") {
                    appState.microrregionId = microrregionSelector.val();
                    appState.macrorregionId = null; // Limpiamos el estado del otro nivel
                }
                if (nivel === "macrorregion") {
                    appState.macrorregionId = macrorregionSelector.val();
                    appState.microrregionId = null; // Limpiamos el estado del otro nivel
                }
            } else {
                // Si volvemos a la pestaña de municipios, limpiamos los estados de región
                appState.microrregionId = null;
                appState.macrorregionId = null;
                const todasLasTematicas = document.querySelectorAll(
                    "#accordionDimensionsRegions .accordion-item"
                );
                todasLasTematicas.forEach((t) => (t.style.display = "block"));
            }
            const targetPaneId = event.target.getAttribute("data-bs-target");
            const targetPane = document.querySelector(targetPaneId);

            if (targetPane) {
                // 2. Buscamos el acordeón que está DENTRO de ese panel.
                const activeAccordion = targetPane.querySelector(".accordion");
                // 3. Llamamos a nuestra nueva función para expandirlo.
                resetearYExpandirPrimerAcordeon(activeAccordion);
            }

            // 5. Futuro paso: Resetear el gráfico y la selección de indicador
            console.log("Estado actualizado:", appState);
            exportBtn.style.display = "none";
            exportBtnRegions.style.display = "none";
        });
    });

    // --- 7. LÓGICA DE DATOS Y RENDERIZADO ---

    /**
     * Función principal (ACTUALIZADA) para solicitar y renderizar los datos.
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

        // 1. Reseteamos la interfaz
        chartTitle.innerText = "Cargando...";
        chartContainer.style.display = "none";
        mapContainer.style.display = "none";
        mapLegend.innerHTML = "";

        // 2. Lógica de decisión
        const indicadorSeleccionado = document.querySelector(
            `.indicador-link[data-indicador-id='${appState.indicatorId}']`
        );
        const esAbsoluto =
            indicadorSeleccionado &&
            indicadorSeleccionado.dataset.tipoDato.toLowerCase() === "absoluto";
        const esEstatal =
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] === "estatal";
        const esUnMunicipio =
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] !== "estatal";
        const esComparativo = appState.municipioIds.length > 1;

        let showMap = false;
        let showChart = true; // El gráfico siempre se muestra

        if (esEstatal && esAbsoluto) {
            showMap = true; // Muestra Mapa de Coropletas
        } else if (esUnMunicipio) {
            showMap = true; // Muestra Mapa con Zoom
            displaySingleMunicipalityMap(appState.municipioIds[0]);
        } else if (esComparativo) {
            showMap = false; // Oculta el mapa
        }

        // Mostramos los contenedores que decidimos
        if (showMap) {
            mapContainer.style.display = "block";
            if (map) map.invalidateSize(); // Forzamos a Leaflet a recalcular su tamaño
        }
        chartContainer.style.display = "block";

        // 3. Hacemos la llamada fetch para obtener los datos
        fetch(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify({
                indicador_id: appState.indicatorId,
                municipio_ids: appState.municipioIds,
                anios: appState.selectedYears,
                nivel_de_agregacion: "municipio",
            }),
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response)
            )
            .then((data) => {
                renderizarGrafico(data); // El gráfico siempre se renderiza

                // Si es el caso del mapa de coropletas y el backend nos envió los datos, lo dibujamos
                if (showMap && esEstatal && data.mapData) {
                    displayChoroplethMap(data.mapData);
                }
            })
            .catch((error) => {
                console.error("Error en la llamada AJAX:", error);
                chartContainer.innerHTML =
                    '<p class="text-danger text-center pt-5">Hubo un error al cargar la información.</p>';
            })
            .finally(() => {
                appState.isLoading = false;
            });
    }

    /**
     * Renderiza el gráfico (MODIFICADA para aceptar el contenedor)
     */
    // function renderizarGrafico(datosParaGrafico) {
    function renderizarGrafico(datosParaGrafico, container, titleElement) {
        const activeExportBtn =
            appState.nivelDeAgregacion === "municipio"
                ? exportBtn
                : exportBtnRegions;
        // chartContainer.innerHTML = "";
        container.innerHTML = "";
        titleElement.innerText = datosParaGrafico.titulo;
        exportBtn.style.display = "none";
        exportBtnRegions.style.display = "none";

        // --- LÓGICA PARA ELEGIR LOS ELEMENTOS CORRECTOS ---
        const isMunicipal = appState.nivelDeAgregacion === "municipio";

        const currentMetadata = isMunicipal
            ? metadataContainer
            : metadataContainerRegions;
        const currentDescription = isMunicipal
            ? descriptionElement
            : descriptionElementRegions;
        const currentSource = isMunicipal
            ? sourceElement
            : sourceElementRegions;
        const currentMethod = isMunicipal
            ? methodElement
            : methodElementRegions;
        const currentNote = isMunicipal
            ? chartNoteContainer
            : chartNoteContainerRegions;
        const currentYearContainer = isMunicipal
            ? yearSelectorContainer
            : yearSelectorContainerRegions;
        const currentYearSelector = isMunicipal
            ? yearSelector
            : document.getElementById("year-selector-regions");
        const currentYearEl = isMunicipal
            ? yearSelectorEl
            : yearSelectorElRegions;
        const currentAvailableYearsElement = isMunicipal
            ? availableYearsElement
            : availableYearsElementRegions;

        // --- AHORA USAMOS LAS VARIABLES 'current' ---
        currentMetadata.style.display = "block";
        currentDescription.innerText =
            datosParaGrafico.descripcion || "No disponible.";
        currentSource.innerText = datosParaGrafico.fuente || "No disponible.";
        if (currentMethod) {
            // Comprobamos que exista por si no lo tienes en una de las vistas
            currentMethod.innerText =
                datosParaGrafico.metodo_calculo || "No disponible.";
        }

        if (datosParaGrafico.nota_explicativa) {
            currentNote.innerHTML = `<p class="mb-0">${datosParaGrafico.nota_explicativa}</p>`;
            currentNote.style.display = "block";
        } else {
            currentNote.innerHTML = "";
            currentNote.style.display = "none";
        }

        if (
            datosParaGrafico.available_years &&
            datosParaGrafico.available_years.length > 0
        ) {
            currentAvailableYearsElement.innerText =
                datosParaGrafico.available_years.sort().join(", ");
            currentYearContainer.style.display = "block";
            currentYearSelector.innerHTML = ""; // Limpiamos opciones
            datosParaGrafico.available_years.forEach((year) =>
                currentYearSelector.add(new Option(year, year))
            );
            activeExportBtn.style.display = "block";

            if (datosParaGrafico.selected_years) {
                currentYearEl
                    .val(datosParaGrafico.selected_years)
                    .trigger("change");
            }
        } else {
            currentAvailableYearsElement.innerText = "N/A";
            currentYearContainer.style.display = "none";
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
                // Solo forzamos el tipo 'numeric' si NO vienen categorías definidas
                // desde el backend. Esto da flexibilidad.
                if (
                    !datosParaGrafico.eje_x.categorias ||
                    datosParaGrafico.eje_x.categorias.length === 0
                ) {
                    xaxisOptions = {
                        type: "numeric",
                        title: {
                            text: datosParaGrafico.eje_x.titulo || "Año",
                        },
                    };
                }
                // Si SÍ vienen categorías, la configuración por defecto que ya construimos es la correcta
                // y no hacemos nada, dejando que se use el tipo 'category'.
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
                colors: PALETA_COLORES,
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
        // chart = new ApexCharts(chartContainer, options);
        chart = new ApexCharts(container, options); // <--- Cambio clave
        chart.render();
    }

    // --- 8. LISTENERS SECUNDARIOS Y CARGA INICIAL ---

    // Unificamos el listener para TODOS los links de indicadores en ambas pestañas
    yearSelectorElRegions.on("change", function () {
        const selection = $(this).val() || [];
        // Compara para evitar bucles
        if (
            JSON.stringify(selection.sort()) ===
            JSON.stringify(appState.selectedYears.sort())
        ) {
            return;
        }
        appState.selectedYears = selection;
        updateDashboard();
    });

    const todosLosIndicadores = document.querySelectorAll(".indicador-link");
    todosLosIndicadores.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            console.log("Evento: Clic en Indicador");

            // 1. Obtenemos el tipo de dato del NUEVO indicador.
            const tipoDatoNuevo = e.target.dataset.tipoDato || "Absoluto";

            // 2. LLAMADA FALTANTE: Ejecutamos la lógica para mostrar/ocultar 'Total Estatal'.
            gestionarOpcionEstatal(tipoDatoNuevo);

            // 3. CORRECCIÓN AUTOMÁTICA: Si 'Total Estatal' estaba seleccionado y ya no es válido...
            if (
                tipoDatoNuevo.toLowerCase() !== "absoluto" &&
                appState.municipioIds.includes("estatal")
            ) {
                console.log(
                    "-> Selección inválida detectada. Cambiando a primer municipio."
                );
                // ...lo cambiamos automáticamente al primer municipio de la lista.
                const primerMunicipioId = municipioSelector
                    .find("option:not([value='estatal'])")
                    .first()
                    .val();
                appState.municipioIds = [primerMunicipioId];
                // Actualizamos la UI del selector sin disparar un nuevo evento 'change'
                municipioSelector
                    .val(appState.municipioIds)
                    .trigger("change.select2");
            }

            // 4. Actualizamos el estado con el nuevo indicador ID.
            appState.indicatorId = e.target.dataset.indicadorId;

            // 5. Actualizamos los estilos visuales.
            todosLosIndicadores.forEach((el) =>
                el.classList.remove("fw-bold", "text-primary")
            );
            document
                .querySelectorAll(
                    `.indicador-link[data-indicador-id='${appState.indicatorId}']`
                )
                .forEach((activeLink) => {
                    activeLink.classList.add("fw-bold", "text-primary");
                });

            // 6. Finalmente, llamamos a la actualización del dashboard.
            updateDashboard();
        });
    });

    // Listeners para los nuevos selectores de región
    microrregionSelector.on("change", function () {
        appState.microrregionId = $(this).val();
        updateDashboard();
    });

    macrorregionSelector.on("change", function () {
        appState.macrorregionId = $(this).val();
        updateDashboard();
    });

    municipioSelector.on("select2:selecting", function (e) {
        const seleccionPropuesta = e.params.args.data.id;
        const seleccionActual = $(this).val() || [];

        // Regla A: Si se intenta seleccionar 'Total Estatal' y ya hay otros municipios,
        // primero se deseleccionan todos los demás para que 'estatal' sea la única opción.
        if (seleccionPropuesta === "estatal" && seleccionActual.length > 0) {
            $(this).val(null);
        }

        // Regla B: Si se intenta seleccionar un municipio cuando 'Total Estatal' está activo,
        // primero se quita 'Total Estatal'.
        if (
            seleccionPropuesta !== "estatal" &&
            seleccionActual.includes("estatal")
        ) {
            $(this).val(null);
        }
    });

    // Listener 2: Se dispara DESPUÉS de que la selección ha cambiado definitivamente.
    municipioSelector.on("change", function () {
        console.log("Evento: Cambio definitivo en Selector de Municipio");
        let selection = $(this).val() || [];

        // Regla C: Si el usuario deja el campo vacío, se fuerza a 'estatal'.
        if (selection.length === 0) {
            selection = ["estatal"];
            // Actualizamos la UI y disparamos el evento de nuevo para que se procese la selección de 'estatal'.
            $(this).val(selection).trigger("change.select2");
            return;
        }

        // Finalmente, actualizamos el estado y llamamos al dashboard.
        // Esta parte solo se ejecuta con una selección ya validada.
        appState.municipioIds = selection;
        appState.selectedYears = []; // Resetea los años al cambiar de municipio
        updateDashboard();
        gestionarBotonResumen();
    });

    /**
     * Muestra u oculta la opción "Total Estatal" basándose en el tipo de dato.
     * @param {string} tipoDato El tipo de dato del indicador actual.
     */
    function gestionarOpcionEstatal(tipoDato) {
        const existeAhora =
            municipioSelector.find("option[value='estatal']").length > 0;

        if (tipoDato.toLowerCase() === "absoluto") {
            // MOSTRAR: Si es absoluto y la opción no existe, la añadimos de nuevo al principio.
            if (!existeAhora && opcionEstatalElement) {
                municipioSelector.prepend(opcionEstatalElement.clone());
                console.log("-> Opción 'Total Estatal' AÑADIDA.");
            }
        } else {
            // OCULTAR: Si no es absoluto y la opción existe, la eliminamos del DOM.
            if (existeAhora) {
                municipioSelector.find("option[value='estatal']").remove();
                console.log("-> Opción 'Total Estatal' ELIMINADA.");
            }
        }

        // --- CORRECCIÓN CLAVE: Forzamos la actualización de Select2 ---
        // Esto le dice a Select2 que vuelva a leer las opciones del <select> y se redibuje.
        municipioSelector.select2({
            theme: "bootstrap-5",
            placeholder: "Selecciona municipio(s)",
            maximumSelectionLength: 2,
        });
    }

    function gestionarBotonResumen() {
        // La condición: exactamente 1 municipio seleccionado y que NO sea 'estatal'
        if (
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] !== "estatal"
        ) {
            const municipioId = appState.municipioIds[0];

            // Habilitamos y mostramos el botón
            resumenBtn.style.display = "inline-block";
            resumenBtn.classList.remove("disabled");

            // Actualizamos el link con el ID del municipio correcto
            resumenBtn.href = resumenUrlPrototype.replace(
                "ID_PLACEHOLDER",
                municipioId
            );
        } else {
            // En cualquier otro caso, lo ocultamos y deshabilitamos
            resumenBtn.style.display = "none";
            resumenBtn.classList.add("disabled");
        }
    }
    yearSelectorEl.on("change", function () {
        console.log("Evento: Cambio en Selector de Año");
        const selection = $(this).val() || [];

        // ¡SALVAGUARDA! Evita disparar la actualización si el valor no ha cambiado realmente.
        // Compara los arreglos como strings para que la comparación sea simple y fiable.
        if (
            JSON.stringify(selection.sort()) ===
            JSON.stringify(appState.selectedYears.sort())
        ) {
            return;
        }

        appState.selectedYears = selection;
        updateDashboard();
    });
    // Y por último, la función CargaInicial.

    function CargaInicial() {
        console.log("Iniciando CargaInicial()...");

        // --- PASO 1: Preparamos la opción 'estatal' (esto debe hacerse siempre) ---
        if (municipioSelector.find("option[value='estatal']").length === 0) {
            const estatalOption = $(
                '<option value="estatal">-- Total Estatal --</option>'
            );
            opcionEstatalElement = estatalOption.clone();
            municipioSelector.prepend(estatalOption);
        }

        // --- PASO 2: Leemos los parámetros de la URL ---
        const urlParams = new URLSearchParams(window.location.search);
        const indicadorIdFromUrl = urlParams.get("indicador_id");
        const municipioIdsFromUrl = urlParams.get("municipio_ids");

        // --- PASO 3: Decidimos el estado inicial ---
        if (indicadorIdFromUrl && municipioIdsFromUrl) {
            // CASO A: Hay parámetros en la URL. Los usamos para el estado inicial.
            console.log(
                "-> Parámetros de URL detectados. Cargando selección específica."
            );

            // 1. Establecemos el estado de la aplicación
            appState.indicatorId = indicadorIdFromUrl;
            appState.municipioIds = municipioIdsFromUrl.split(","); // .split(',') funciona para uno o varios IDs

            // 2. Actualizamos la UI para que refleje el estado
            municipioSelector
                .val(appState.municipioIds)
                .trigger("change.select2");

            // 3. Buscamos el link del indicador para resaltarlo y gestionar la opción 'estatal'
            const linkActivo = document.querySelector(
                `.indicador-link[data-indicador-id='${appState.indicatorId}']`
            );
            if (linkActivo) {
                linkActivo.classList.add("fw-bold", "text-primary");
                expandirAcordeonHacia(linkActivo);

                // Gestionamos si 'estatal' debe estar disponible para este indicador
                const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
                gestionarOpcionEstatal(tipoDato);
            }
        } else {
            // CASO B: No hay parámetros en la URL. Usamos la lógica por defecto que ya tenías.
            console.log("-> Sin parámetros de URL. Usando carga por defecto.");
            const firstIndicatorLink =
                document.querySelector(".indicador-link");
            if (firstIndicatorLink) {
                const tipoDato =
                    firstIndicatorLink.dataset.tipoDato || "Absoluto";
                gestionarOpcionEstatal(tipoDato);

                let idSeleccionInicial = "estatal";
                if (tipoDato.toLowerCase() !== "absoluto") {
                    idSeleccionInicial = municipioSelector
                        .find("option:not([value='estatal'])")
                        .first()
                        .val();
                }

                appState.municipioIds = [idSeleccionInicial];
                municipioSelector
                    .val(appState.municipioIds)
                    .trigger("change.select2");

                appState.indicatorId = firstIndicatorLink.dataset.indicadorId;
                firstIndicatorLink.classList.add("fw-bold", "text-primary");
                expandirAcordeonHacia(firstIndicatorLink);
            }
        }

        // --- PASO 4: Llamamos a la actualización del dashboard (se ejecuta en ambos casos) ---
        console.log(
            "-> Estado inicial listo. Llamando a updateDashboard() por primera vez."
        );
        updateDashboard();
        gestionarBotonResumen();
    }

    CargaInicial();
    initMap();
    // --- LÓGICA PARA EL MAPA INTERACTIVO (VERSIÓN FINAL) ---

    const mapIndicatorSelector = document.getElementById(
        "mapa-indicador-selector"
    );
    const mapYearSelector = document.getElementById("mapa-anio-selector");
    const mapUpdateBtn = document.getElementById("mapa-update-btn");
    let mapData = {};

    // 1. FUNCIÓN DE INICIALIZACIÓN
    function initMap() {
        if (map) return;
        map = L.map("map").setView([19.0414, -98.2063], 8);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        // Llenamos el selector de indicadores (solo los absolutos)
        mapIndicatorSelector.innerHTML = "";
        document.querySelectorAll(".indicador-link").forEach((link) => {
            if (
                link.dataset.tipoDato &&
                link.dataset.tipoDato.toLowerCase() === "absoluto"
            ) {
                mapIndicatorSelector.add(
                    new Option(
                        link.textContent.trim(),
                        link.dataset.indicadorId
                    )
                );
            }
        });

        // Disparamos el evento 'change' para cargar los años del primer indicador
        if (mapIndicatorSelector.value) {
            mapIndicatorSelector.dispatchEvent(new Event("change"));
        }
    }

    // 2. FUNCIÓN DE COLOREADO (AJUSTADA PARA 4 CUARTILES)
    function getColor(value, quintiles) {
        if (value === null || quintiles.length < 4) return "#ccc"; // Gris para sin datos
        // Comparamos el valor con los límites de cada quintil
        if (value >= quintiles[3]) return "#e76f51"; // > 80% (más alto)
        if (value >= quintiles[2]) return "#f4a261"; // > 60%
        if (value >= quintiles[1]) return "#e9c46a"; // > 40%
        if (value >= quintiles[0]) return "#2a9d8f"; // > 20%
        return "#264653"; // <= 20% (más bajo)
    }

    // 3. FUNCIÓN PRINCIPAL PARA ACTUALIZAR EL MAPA
    async function updateMap() {
        mapUpdateBtn.disabled = true;
        mapUpdateBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Cargando...`;

        const indicadorId = mapIndicatorSelector.value;
        const anio = mapYearSelector.value;
        if (!indicadorId || !anio) {
            alert("Por favor, selecciona un indicador y un año.");
            mapUpdateBtn.disabled = false;
            mapUpdateBtn.innerHTML = `Actualizar Mapa`;
            return;
        }

        const url = `/fichas/api/mapa-datos/${indicadorId}/${anio}`;

        try {
            const response = await fetch(url);
            const rawMapData = await response.json();

            mapData = {};
            for (const key in rawMapData) {
                mapData[key] = parseFloat(rawMapData[key]);
            }

            if (Object.keys(mapData).length === 0) {
                alert(
                    "No se encontraron datos para el indicador y año seleccionados."
                );
                if (geojsonLayer) map.removeLayer(geojsonLayer);
                mapLegend.innerHTML = "";
                mapUpdateBtn.disabled = false;
                mapUpdateBtn.innerHTML = `Actualizar Mapa`;
                return;
            }

            if (geojsonLayer) {
                map.removeLayer(geojsonLayer);
            }

            const values = Object.values(mapData)
                .filter((v) => v !== null)
                .sort((a, b) => a - b);
            const formatNumber = (num) =>
                num !== undefined && num !== null
                    ? new Intl.NumberFormat().format(Math.round(num))
                    : "N/A";

            let quintiles = [];
            let styleFunction;

            if (values.length >= 5) {
                quintiles = [
                    values[Math.floor(values.length * 0.2)],
                    values[Math.floor(values.length * 0.4)],
                    values[Math.floor(values.length * 0.6)],
                    values[Math.floor(values.length * 0.8)],
                ];

                styleFunction = (feature) => ({
                    fillColor: getColor(
                        mapData[feature.properties.cvegeo] || null,
                        quintiles
                    ),
                    weight: 1,
                    opacity: 1,
                    color: "white",
                    fillOpacity: 0.8,
                });

                mapLegend.innerHTML = `
                <div class="d-flex align-items-center justify-content-center flex-wrap" style="font-size: 0.8rem;">
                    <strong class="me-3">Leyenda:</strong>
                    <div class="me-2"><i class="fas fa-square me-1" style="color:#264653;"></i> ${formatNumber(
                        values[0]
                    )} - ${formatNumber(quintiles[0])}</div>
                    <div class="me-2"><i class="fas fa-square me-1" style="color:#2a9d8f;"></i> ${formatNumber(
                        quintiles[0]
                    )} - ${formatNumber(quintiles[1])}</div>
                    <div class="me-2"><i class="fas fa-square me-1" style="color:#e9c46a;"></i> ${formatNumber(
                        quintiles[1]
                    )} - ${formatNumber(quintiles[2])}</div>
                    <div class="me-2"><i class="fas fa-square me-1" style="color:#f4a261;"></i> ${formatNumber(
                        quintiles[2]
                    )} - ${formatNumber(quintiles[3])}</div>
                    <div class="me-2"><i class="fas fa-square me-1" style="color:#e76f51;"></i> &gt; ${formatNumber(
                        quintiles[3]
                    )}</div>
                </div>
            `;
            } else {
                styleFunction = (feature) => ({
                    fillColor:
                        (mapData[feature.properties.cvegeo] || null) !== null
                            ? "#2a9d8f"
                            : "#ccc",
                    weight: 1,
                    opacity: 1,
                    color: "white",
                    fillOpacity: 0.8,
                });
                mapLegend.innerHTML = `<div class="d-flex align-items-center justify-content-center flex-wrap" style="font-size: 0.8rem;"><strong class="me-3">Leyenda:</strong><div class="me-2"><i class="fas fa-square me-1" style="color:#2a9d8f;"></i> Municipios con datos</div></div>`;
            }

            // --- LA LÍNEA DEL PROBLEMA ESTABA AQUÍ ---
            const geojsonResponse = await fetch(
                "/geojson/puebla_municipios_wgs84.geojson"
            );
            const geojson = await geojsonResponse.json();

            geojsonLayer = L.geoJSON(geojson, {
                style: styleFunction,
                onEachFeature: (feature, layer) => {
                    const nombre = feature.properties.nomgeo;
                    const valor = mapData[feature.properties.cvegeo]
                        ? formatNumber(mapData[feature.properties.cvegeo])
                        : "Sin datos";
                    layer.bindPopup(
                        `<strong>${nombre}</strong><br>Valor: ${valor}`
                    );
                },
            }).addTo(map);
        } catch (error) {
            console.error("Error al actualizar el mapa:", error);
            alert("No se pudieron cargar los datos para el mapa.");
        } finally {
            mapUpdateBtn.disabled = false;
            mapUpdateBtn.innerHTML = `Actualizar Mapa`;
        }
    }

    // 4. LISTENER PARA ACTUALIZAR LOS AÑOS
    mapIndicatorSelector.addEventListener("change", async function () {
        const indicadorId = this.value;
        mapYearSelector.innerHTML = "<option>Cargando años...</option>";
        mapYearSelector.disabled = true;

        try {
            const response = await fetch(
                `/fichas/api/indicador-anios/${indicadorId}`
            );
            const years = await response.json();
            mapYearSelector.innerHTML = "";
            if (years.length > 0) {
                years.forEach((year) =>
                    mapYearSelector.add(new Option(year, year))
                );
                mapYearSelector.disabled = false;
            } else {
                mapYearSelector.innerHTML = "<option>Sin datos</option>";
            }
        } catch (error) {
            console.error("Error al cargar los años para el indicador:", error);
            mapYearSelector.innerHTML = "<option>Error al cargar</option>";
        }
    });

    // 5. LISTENERS PRINCIPALES DEL MAPA
    document
        .getElementById("pill-mapa-tab")
        .addEventListener("shown.bs.tab", initMap);
    mapUpdateBtn.addEventListener("click", updateMap);
});
