// --- VARIABLES GLOBALES PARA EL MAPA ---
// Las declaramos aquí para que sean accesibles por todas las funciones.
let map = null;
let geojsonLayer = null;
let pueblaGeoJSON = null;
let microGeoJSON = null;
let macroGeoJSON = null;

document.addEventListener("DOMContentLoaded", function () {
    // --- 0. SETUP INICIAL Y LECTURA DE VARIABLES ---
    const appContainer = document.querySelector("[data-api-url]");
    if (!appContainer) {
        return; // Si no estamos en la página de fichas, no ejecutamos nada.
    }

    const API_URL = appContainer.dataset.apiUrl;
    const EXPORT_URL = appContainer.dataset.exportUrl;
    const CSRF_TOKEN = appContainer.dataset.csrfToken;
    let opcionEstatalElement = null;

    // --- 1. REFERENCIAS A ELEMENTOS DEL DOM (ÚNICAS) ---
    const estatalBtn = document.getElementById("estatal-btn");
    const consultarBtn = document.getElementById("consultar-btn");
    const consultarBtnRegions = document.getElementById(
        "consultar-btn-regions"
    );
    const municipioSelector = new TomSelect("#municipio-selector", {
        placeholder: "Selecciona hasta 2 municipios",
        maxItems: 2,
        sortField: [
            { field: "orden", direction: "asc" },
            { field: "$text", direction: "asc" },
        ],
        plugins: ["remove_button"],
        maxOptions: 217,
        // --- LÓGICA ONCHANGE CON LA REGLA CORREGIDA ---
        onChange: function (value) {
            estatalBtn.classList.remove("active");
            const currentSelection = Array.isArray(value)
                ? value
                : value
                ? [value]
                : [];
            let finalSelection = [...currentSelection];
            let needsUpdate = false;

            // --- REGLA 1 CORREGIDA ---
            // Si 'estatal' está mezclado con otros municipios...
            if (
                finalSelection.includes("estatal") &&
                finalSelection.length > 1
            ) {
                // ...la selección final son TODOS LOS MUNICIPIOS MENOS 'estatal'.
                finalSelection = finalSelection.filter(
                    (id) => id !== "estatal"
                );
                needsUpdate = true;
            }

            // Regla 2 (sin cambios): Si la selección está vacía y el indicador es absoluto, volvemos a 'estatal'.
            if (
                finalSelection.length === 0 &&
                appState.indicatorTipoDato &&
                appState.indicatorTipoDato.toLowerCase() === "absoluto"
            ) {
                finalSelection = ["estatal"];
                needsUpdate = true;
            }

            // Actualizamos el selector silenciosamente si es necesario para que no haya un bucle
            if (needsUpdate) {
                this.setValue(finalSelection, true);
            }

            // Actualizamos el estado de la aplicación y el dashboard con la selección final y correcta
            appState.municipioIds = finalSelection;
            appState.selectedYears = [];
            gestionarBotonResumen();
            checkIfCanConsult();
        },
    });

    const microrregionSelector = new TomSelect("#microrregion-selector", {
        placeholder: "Selecciona una microrregión",
        onChange: function (value) {
            appState.microrregionId = value;
            checkIfCanConsult();
        },
    });

    const macrorregionSelector = new TomSelect("#macrorregion-selector", {
        placeholder: "Selecciona una macrorregión",
        onChange: function (value) {
            appState.macrorregionId = value;
            checkIfCanConsult();
        },
    });

    const yearSelectorEl = new TomSelect("#year-selector", {
        placeholder: "Selecciona año(s)",
        plugins: ["remove_button"],
        closeAfterSelect: false,
        onChange: function (value) {
            const selection = value || [];
            if (
                JSON.stringify(selection.sort()) ===
                JSON.stringify(appState.selectedYears.sort())
            ) {
                return;
            }
            appState.selectedYears = selection;
        },
    });

    const exportBtn = document.getElementById("export-btn");

    const microrregionContainer = document.getElementById(
        "microrregion-selector-container"
    );
    const macrorregionContainer = document.getElementById(
        "macrorregion-selector-container"
    );
    const nivelTabs = document.querySelectorAll("#pills-tab-nivel .nav-link");
    const accordionMunicipal = document.getElementById("accordionDimensions");

    const chartContainer = document.getElementById("chart-container");
    const chartTitle = document.getElementById("chart-title");
    const resumenBtn = document.getElementById("resumen-btn");
    const resumenUrlPrototype = resumenBtn ? resumenBtn.href : "";

    const mapContainer = document.getElementById("map-container");
    const mapLegend = document.getElementById("map-legend");

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
    // Variables para boton de tamaño completo
    const fullscreenBtn = document.getElementById("fullscreen-btn");
    const fullscreenModal = document.getElementById("chart-fullscreen-modal");
    const fullscreenChartContainer = document.getElementById(
        "fullscreen-chart-container"
    );
    const fullscreenModalTitle = document.getElementById(
        "fullscreen-modal-title"
    );
    const fullscreenBtnRegions = document.getElementById(
        "fullscreen-btn-regions"
    );
    let fullscreenChart = null;
    let lastChartOptions = {};
    let chart;

    const PALETA_COLORES = [
        "#264653",
        "#2a9d8f",
        "#e9c46a",
        "#f4a261",
        "#e76f51",
        "#6a040f",
        "#0077b6",
    ];
    // --- 2. EL "CEREBRO" O ESTADO CENTRAL ---
    const appState = {
        nivelDeAgregacion: "municipio",
        indicatorId: null,
        indicatorEsComplejo: false,
        municipioIds: [],
        microrregionId: null,
        macrorregionId: null,
        selectedYears: [],
        isLoading: false,
    };

    // --- 3. INICIALIZACIÓN DE COMPONENTES ---
    // En tu script-ficha.js, dentro de DOMContentLoaded

    // --- 4. FUNCIONES DE AYUDA (DEFINIDAS UNA SOLA VEZ) ---

    /**
     * Muestra u oculta la opción "Total Estatal" basándose en el tipo de dato.
     * @param {string} tipoDato El tipo de dato del indicador actual.
     */

    // function gestionarOpcionEstatal(tipoDato) {
    //     // Verificamos si la opción 'estatal' ya existe en la instancia de Tom Select
    //     const existeAhora = municipioSelector.options.hasOwnProperty("estatal");

    //     if (tipoDato.toLowerCase() === "absoluto") {
    //         // MOSTRAR: Si es absoluto y la opción no existe, la añadimos.
    //         if (!existeAhora) {
    //             municipioSelector.addOption({
    //                 value: "estatal",
    //                 text: "-- Total Estatal --",
    //             });
    //             console.log("-> Opción 'Total Estatal' AÑADIDA.");
    //         }
    //     } else {
    //         // OCULTAR: Si no es absoluto y la opción existe, la eliminamos.
    //         if (existeAhora) {
    //             // Opcional pero recomendado: si 'estatal' estaba seleccionado, limpiamos la selección.
    //             if (municipioSelector.getValue() === "estatal") {
    //                 municipioSelector.clear();
    //             }
    //             municipioSelector.removeOption("estatal");
    //             console.log("-> Opción 'Total Estatal' ELIMINADA.");
    //         }
    //     }
    // }
    function gestionarOpcionEstatal(tipoDato) {
        appState.indicatorTipoDato = tipoDato;
        if (tipoDato.toLowerCase() === "absoluto") {
            estatalBtn.style.display = "block";
        } else {
            estatalBtn.style.display = "none";
        }
    }

    estatalBtn.addEventListener("click", () => {
        municipioSelector.clear();
        estatalBtn.classList.add("active");
        appState.municipioIds = ["estatal"];
        appState.selectedYears = [];
        gestionarBotonResumen();
        updateDashboard();
    });

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

            resumenBtn.href = resumenUrlPrototype.replace(
                "ID_PLACEHOLDER",
                municipioId
            );
        } else {
            resumenBtn.style.display = "none";
            resumenBtn.classList.add("disabled");
        }
    }

    /**
     * Filtra la lista de indicadores para la vista regional.
     * @param {boolean} showAll Si es true, muestra todos los indicadores. Si es false, muestra solo los de tipo 'Absoluto'.
     */
    function filtrarAcordeonRegional(showAll = false) {
        // --- LA CORRECCIÓN ESTÁ AQUÍ ---
        // Antes decía: "#accordionDimensionsRegions .indicador-link"
        const links = document.querySelectorAll(
            "#accordionDimensions .indicador-link"
        );
        // --- FIN DE LA CORRECCIÓN ---

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

    /**
     * Revisa el acordeón de regiones y oculta las temáticas que no tienen indicadores visibles.
     */
    function ocultarTematicasVacias() {
        // Obtenemos todos los acordeones de temáticas en la vista de regiones
        // --- LA CORRECCIÓN ESTÁ AQUÍ ---
        // Antes decía: "#accordionDimensionsRegions .accordion-item"
        const tematicas = document.querySelectorAll(
            "#accordionDimensions .accordion-item"
        );
        // --- FIN DE LA CORRECCIÓN ---

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

    function getColor(value, quintiles) {
        // --- LA CORRECCIÓN CLAVE ---
        // Aseguramos que el valor sea de tipo numérico antes de comparar.
        value = Number(value);

        if (value === null || quintiles.length < 4 || isNaN(value))
            return "#ccc"; // Gris para sin datos

        // Comparamos el valor de mayor a menor para asignar el color correcto
        if (value >= quintiles[3]) return "#e76f51"; // > 80% (el valor más alto)
        if (value >= quintiles[2]) return "#f4a261"; // > 60%
        if (value >= quintiles[1]) return "#e9c46a"; // > 40%
        if (value >= quintiles[0]) return "#2a9d8f"; // > 20%

        return "#264653"; // <= 20% (el valor más bajo)
    }
    async function loadAllGeoJSON() {
        // Si ya los cargamos, no lo hacemos de nuevo.
        if (pueblaGeoJSON && microGeoJSON && macroGeoJSON) return;

        try {
            const [responseMun, responseMicro, responseMacro] =
                await Promise.all([
                    fetch(
                        `${window.APP_URL}/geojson/puebla_municipios_wgs84.geojson`
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025_sin_adecuacion.geojson`
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025.geojson`
                    ),
                ]);

            pueblaGeoJSON = await responseMun.json();
            microGeoJSON = await responseMicro.json();
            macroGeoJSON = await responseMacro.json();
            console.log("Todos los archivos GeoJSON han sido cargados.");
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

    async function initMap() {
        // Si el mapa ya fue creado, no hacemos nada más.
        if (map) return;

        // Si el contenedor del mapa no existe en la página, tampoco hacemos nada.
        if (!document.getElementById("map")) return;

        // Creamos el mapa y lo centramos en Puebla.
        map = L.map("map").setView([19.0414, -98.2063], 8);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        // Intentamos cargar el archivo GeoJSON y lo guardamos en memoria.
        try {
            // Usamos Promise.all para cargar todos los archivos en paralelo
            const [responseMun, responseMicro, responseMacro] =
                await Promise.all([
                    fetch(
                        `${window.APP_URL}/geojson/puebla_municipios_wgs84.geojson`
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025_sin_adecuacion.geojson`
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025.geojson`
                    ),
                ]);

            pueblaGeoJSON = await responseMun.json();
            microGeoJSON = await responseMicro.json();
            macroGeoJSON = await responseMacro.json();

            console.log("Todos los archivos GeoJSON han sido cargados.");
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

    async function initMapOLD() {
        // Si el mapa ya fue creado, no hacemos nada más.
        if (map) return;

        // Si el contenedor del mapa no existe en la página, tampoco hacemos nada.
        if (!document.getElementById("map")) return;

        // Creamos el mapa y lo centramos en Puebla.
        map = L.map("map").setView([19.0414, -98.2063], 8);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        // Intentamos cargar el archivo GeoJSON y lo guardamos en memoria.
        try {
            // Usamos Promise.all para cargar todos los archivos en paralelo
            const [responseMun, responseMicro, responseMacro] =
                await Promise.all([
                    fetch(
                        `${window.APP_URL}/geojson/puebla_municipios_wgs84.geojson`
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025_sin_adecuacion.geojson`
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025.geojson`
                    ),
                ]);

            pueblaGeoJSON = await responseMun.json();
            microGeoJSON = await responseMicro.json();
            macroGeoJSON = await responseMacro.json();

            console.log("Todos los archivos GeoJSON han sido cargados.");
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
     * Muestra el mapa de coropletas para la vista estatal.
     * @param {object} mapData Objeto con {municipioId: valor}.
     */
    // function displayChoroplethMap(mapData) {
    //     if (!pueblaGeoJSON || !map) {
    //         console.error("El mapa o el GeoJSON no se han inicializado.");
    //         return;
    //     }

    //     if (geojsonLayer) map.removeLayer(geojsonLayer);

    //     const values = Object.values(mapData)
    //         .filter((v) => v !== null)
    //         .sort((a, b) => a - b);
    //     const formatNumber = (num) =>
    //         num !== undefined && num !== null
    //             ? new Intl.NumberFormat().format(Math.round(num))
    //             : "N/A";

    //     let quintiles = [];
    //     let styleFunction;

    //     // 2. Decide el tipo de simbología según la cantidad de datos.
    //     if (values.length >= 5) {
    //         // --- LÓGICA DE QUINTILES (5 GRUPOS) ---
    //         quintiles = [
    //             values[Math.floor(values.length * 0.2)],
    //             values[Math.floor(values.length * 0.4)],
    //             values[Math.floor(values.length * 0.6)],
    //             values[Math.floor(values.length * 0.8)],
    //         ];
    //         styleFunction = (feature) => ({
    //             fillColor: getColor(
    //                 mapData[feature.properties.cvegeo] || null,
    //                 quintiles
    //             ),
    //             weight: 1,
    //             opacity: 1,
    //             color: "white",
    //             fillOpacity: 0.8,
    //         });

    //         // --- LEYENDA DETALLADA DE 5 GRUPOS ---
    //         mapLegend.innerHTML = `
    //                 <div class="d-flex align-items-center justify-content-center flex-wrap" style="font-size: 0.8rem;">
    //                     <strong class="me-3">Leyenda:</strong>
    //                     <div class="me-2"><i class="fas fa-square me-1" style="color:#264653;"></i> ${formatNumber(
    //                         values[0]
    //                     )} - ${formatNumber(quintiles[0])}</div>
    //                     <div class="me-2"><i class="fas fa-square me-1" style="color:#2a9d8f;"></i> ${formatNumber(
    //                         quintiles[0]
    //                     )} - ${formatNumber(quintiles[1])}</div>
    //                     <div class="me-2"><i class="fas fa-square me-1" style="color:#e9c46a;"></i> ${formatNumber(
    //                         quintiles[1]
    //                     )} - ${formatNumber(quintiles[2])}</div>
    //                     <div class="me-2"><i class="fas fa-square me-1" style="color:#f4a261;"></i> ${formatNumber(
    //                         quintiles[2]
    //                     )} - ${formatNumber(quintiles[3])}</div>
    //                     <div class="me-2"><i class="fas fa-square me-1" style="color:#e76f51;"></i> &gt; ${formatNumber(
    //                         quintiles[3]
    //                     )}</div>
    //                 </div>
    //             `;
    //     } else {
    //         // --- LÓGICA SIMPLE (Pocos datos) ---
    //         styleFunction = (feature) => ({
    //             fillColor:
    //                 (mapData[feature.properties.cvegeo] || null) !== null
    //                     ? "#2a9d8f"
    //                     : "#ccc",
    //             weight: 1,
    //             opacity: 1,
    //             color: "white",
    //             fillOpacity: 0.8,
    //         });
    //         mapLegend.innerHTML = `<div class="d-flex align-items-center justify-content-center flex-wrap" style="font-size: 0.8rem;"><strong class="me-3">Leyenda:</strong><div class="me-2"><i class="fas fa-square me-1" style="color:#2a9d8f;"></i> Municipios con datos</div></div>`;
    //     }

    //     geojsonLayer = L.geoJSON(pueblaGeoJSON, {
    //         style: styleFunction, // tu styleFunction que ya tienes está bien
    //         onEachFeature: (feature, layer) => {
    //             const nombre = feature.properties.nomgeo;
    //             const valor = mapData[feature.properties.cvegeo]
    //                 ? formatNumber(mapData[feature.properties.cvegeo])
    //                 : "Sin datos";
    //             layer.bindPopup(
    //                 `<strong>${nombre}</strong><br>Valor: ${valor}`
    //             );
    //         },
    //     }).addTo(map);

    //     map.setView([19.0414, -98.2063], 7);
    // }
    function displayChoroplethMap(mapData) {
        if (!map || !pueblaGeoJSON) {
            console.error("El mapa o el GeoJSON no se han inicializado.");
            return;
        }
        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        // --- 1. SEPARAMOS A PUEBLA (sin cambios) ---
        const PUEBLA_CVEGEO = "21114";
        const pueblaValue = mapData[PUEBLA_CVEGEO];
        const otherMunicipalitiesData = { ...mapData };
        delete otherMunicipalitiesData[PUEBLA_CVEGEO];

        // --- 2. CÁLCULO DE VALORES (sin cambios) ---
        const values = Object.values(otherMunicipalitiesData)
            .map((v) => parseFloat(v))
            .filter((v) => !isNaN(v) && isFinite(v));
        values.sort((a, b) => a - b);

        // --- 3. FUNCIÓN FORMATTER (sin cambios) ---
        const formatNumber = (num) => {
            if (num === undefined || num === null) return "N/A";
            const numericValue = parseFloat(num);
            const roundedValue = Math.round(numericValue);
            return roundedValue.toLocaleString("es-MX");
        };

        let styleFunction;

        // --- INICIO DE LA LÓGICA ADAPTABLE ---
        if (values.length >= 5) {
            // CASO A: HAY SUFICIENTES DATOS
            console.log(
                "-> Hay suficientes datos. Usando simbología de 5 cuantiles."
            );

            // Definición de breaks (sin cambios)
            const breaks = [
                values[0],
                values[Math.floor(values.length * 0.2)],
                values[Math.floor(values.length * 0.4)],
                values[Math.floor(values.length * 0.6)],
                values[Math.floor(values.length * 0.8)],
            ];

            // Función getColor (sin cambios)
            const PUEBLA_COLOR = "#b10026";
            function getColor(value, cvegeo) {
                if (cvegeo == PUEBLA_CVEGEO) return PUEBLA_COLOR;
                const numericValue = parseFloat(value);
                if (isNaN(numericValue)) return "#ccc";
                return numericValue >= breaks[4]
                    ? "#084594"
                    : numericValue >= breaks[3]
                    ? "#2171b5"
                    : numericValue >= breaks[2]
                    ? "#4292c6"
                    : numericValue >= breaks[1]
                    ? "#6baed6"
                    : "#9ecae1";
            }

            // styleFunction (sin cambios)
            styleFunction = (feature) => ({
                fillColor: getColor(
                    mapData[feature.properties.cvegeo],
                    feature.properties.cvegeo
                ),
                weight: 1,
                opacity: 1,
                color: "white",
                fillOpacity: 0.8,
            });

            // --- 4. CREAMOS UNA LEYENDA INTELIGENTE (VERSIÓN CORREGIDA) ---
            // Definimos los colores y los rangos de forma explícita
            const colors = [
                "#084594",
                "#2171b5",
                "#4292c6",
                "#6baed6",
                "#9ecae1",
            ];
            const ranges = [
                [breaks[4], "o más"],
                [breaks[3], breaks[4]],
                [breaks[2], breaks[3]],
                [breaks[1], breaks[2]],
                ["Menos de", breaks[1]],
            ];

            let legendHTML = `
            <h5>Leyenda</h5>
            ${
                pueblaValue !== undefined
                    ? `<div><i class="legend-swatch" style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                          pueblaValue
                      )})</div><hr class='my-1'>`
                    : ""
            }
            <div class="text-muted small">Resto de municipios:</div>
        `;

            let lastRangeText = null; // Para guardar el texto del rango anterior

            // Iteramos sobre cada rango para construir la leyenda
            ranges.forEach((range, index) => {
                const min = range[0];
                const max = range[1];
                let rangeText;

                // Formatear los números
                const minFormatted =
                    typeof min === "number" ? formatNumber(min) : min;
                const maxFormatted =
                    typeof max === "number" ? formatNumber(max) : max;

                // 1. Crear el texto del rango
                if (index === 0) {
                    rangeText = `${minFormatted} ${maxFormatted}`; // "Menos de X"
                } else if (index === ranges.length - 1) {
                    rangeText = `${minFormatted} ${maxFormatted}`; // "X o más"
                } else {
                    if (minFormatted === maxFormatted) {
                        rangeText = `${minFormatted}`; // "0"
                    } else {
                        rangeText = `${minFormatted} - ${maxFormatted}`; // "0 - 1"
                    }
                }

                // 2. LA CLAVE: Si el texto del rango actual es idéntico al anterior, lo saltamos.
                if (rangeText === lastRangeText) {
                    return; // No se añade a la leyenda
                }

                legendHTML += `<div><i class="legend-swatch" style="background:${colors[index]};"></i> ${rangeText}</div>`;
                lastRangeText = rangeText; // Guardamos el texto de este rango
            });

            legendHTML += `<div><i class="legend-swatch" style="background:#ccc"></i> Sin datos</div>`;
            mapLegend.innerHTML = legendHTML;
            // --- FIN DE LA LEYENDA INTELIGENTE ---
        } else {
            // CASO B: NO HAY SUFICIENTES DATOS, usamos una lógica simple.
            console.log(
                "-> No hay suficientes datos. Usando simbología simple."
            );

            const PUEBLA_COLOR = "#b10026"; // Color para Puebla
            const DATA_COLOR = "#4292c6"; // Color para otros municipios con datos

            styleFunction = (feature) => {
                const cvegeo = feature.properties.cvegeo;
                let color = "#ccc"; // Color por defecto (sin datos)
                if (cvegeo == PUEBLA_CVEGEO && pueblaValue !== undefined) {
                    color = PUEBLA_COLOR;
                } else if (
                    mapData[cvegeo] !== undefined &&
                    mapData[cvegeo] !== null
                ) {
                    color = DATA_COLOR;
                }
                return {
                    fillColor: color,
                    weight: 1,
                    opacity: 1,
                    color: "white",
                    fillOpacity: 0.8,
                };
            };

            mapLegend.innerHTML = `
            <h5>Leyenda</h5>
            ${
                pueblaValue !== undefined
                    ? `<div><i style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                          pueblaValue
                      )})</div>`
                    : ""
            }
            ${
                values.length > 0
                    ? `<div><i class="legend-swatch" style="background:${DATA_COLOR}"></i> Otros municipios con datos</div>`
                    : ""
            }
            <div><i style="background:#ccc"></i> Sin datos</div>
        `;
        }

        // --- FIN DE LA LÓGICA ADAPTABLE ---

        // El resto de la función que dibuja el mapa se queda igual
        geojsonLayer = L.geoJson(pueblaGeoJSON, {
            style: styleFunction,
            onEachFeature: (feature, layer) => {
                const nombre = feature.properties.nomgeo;
                const valor = formatNumber(mapData[feature.properties.cvegeo]);
                layer.bindPopup(
                    `<strong>${nombre}</strong><br>Valor: ${valor}`
                );
            },
        }).addTo(map);

        mapLegend.style.display = "block";
    }

    /**
     * Resalta un polígono (municipio o región) y hace zoom en él.
     * @param {object} geojsonData El archivo GeoJSON completo (ej. pueblaGeoJSON, microGeoJSON)
     * @param {string} idToFind El ID/Clave del polígono que queremos encontrar.
     * @param {string} propertyKey El nombre de la propiedad donde buscar el ID (ej. "cvegeo", "id_micro")
     */
    function displaySingleFeatureMap(geojsonData, idToFind, propertyKey) {
        if (!geojsonData || !map) {
            console.error("El mapa o el GeoJSON no se han inicializado.");
            return;
        }
        if (geojsonLayer) map.removeLayer(geojsonLayer);
        mapLegend.style.display = "none";

        let foundFeature = false;

        geojsonLayer = L.geoJson(geojsonData, {
            style: function (feature) {
                if (feature.properties[propertyKey] == idToFind) {
                    // Estilo para la forma resaltada
                    return {
                        fillColor: "#246257",
                        weight: 2,
                        color: "#333",
                        fillOpacity: 0.7,
                    };
                } else {
                    // Estilo para las otras formas
                    return {
                        fillColor: "#ccc",
                        weight: 1,
                        color: "white",
                        fillOpacity: 0.5,
                    };
                }
            },
            onEachFeature: function (feature, layer) {
                if (feature.properties[propertyKey] == idToFind) {
                    map.fitBounds(layer.getBounds()); // Hacemos zoom
                    foundFeature = true;
                }
                // Añadimos el popup (puedes mejorarlo para que muestre el nombre)
                layer.bindPopup(
                    `<strong>${
                        feature.properties.nombre || feature.properties.nomgeo
                    }</strong>`
                );
            },
        }).addTo(map);

        if (!foundFeature) {
            console.warn(
                `No se encontró el polígono con ${propertyKey}: ${idToFind}`
            );
        }
    }
    function displayChoroplethMapOLD(mapData) {
        if (!map || !pueblaGeoJSON) {
            console.error("El mapa o el GeoJSON no se han inicializado.");
            return;
        }
        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        // --- 1. SEPARAMOS A PUEBLA ---
        const PUEBLA_CVEGEO = "21114";
        const pueblaValue = mapData[PUEBLA_CVEGEO];
        const otherMunicipalitiesData = { ...mapData };
        delete otherMunicipalitiesData[PUEBLA_CVEGEO];

        // --- 2. CÁLCULO DE VALORES ---
        const initialValues = Object.values(otherMunicipalitiesData);
        console.log(
            "1. Valores ANTES de la conversión (deberían ser strings):",
            initialValues.slice(0, 5)
        );

        const convertedValues = initialValues.map((v) => parseFloat(v));
        console.log(
            "2. Valores DESPUÉS de parseFloat:",
            convertedValues.slice(0, 5)
        );

        const finalValues = convertedValues.filter(
            (v) => !isNaN(v) && isFinite(v)
        );
        console.log(
            "3. Valores FINALES después de filtrar (deben ser números):",
            finalValues.slice(0, 5)
        );

        console.log(
            `%cTotal de valores numéricos válidos: ${finalValues.length}`,
            "color: green; font-weight: bold;"
        );

        const values = Object.values(otherMunicipalitiesData)
            .map((v) => parseFloat(v))
            .filter((v) => !isNaN(v) && isFinite(v));
        values.sort((a, b) => a - b);

        const formatNumber = (num) => {
            // Si el número es nulo o indefinido, devolvemos 'N/A'
            if (num === undefined || num === null) return "N/A";

            // 1. Convertimos el texto (ej. "21032.0000") a un número.
            const numericValue = parseFloat(num);

            // 2. Redondeamos al entero más cercano para quitar los decimales.
            const roundedValue = Math.round(numericValue);

            // 3. Le damos el formato local (es-MX) que añade las comas.
            return roundedValue.toLocaleString("es-MX");
        };

        let styleFunction;

        // --- INICIO DE LA LÓGICA ADAPTABLE ---

        if (values.length >= 5) {
            // CASO A: HAY SUFICIENTES DATOS, usamos la lógica de 5 cuantiles.
            console.log(
                "-> Hay suficientes datos. Usando simbología de 5 cuantiles."
            );

            const breaks = [
                values[0],
                values[Math.floor(values.length * 0.2)],
                values[Math.floor(values.length * 0.4)],
                values[Math.floor(values.length * 0.6)],
                values[Math.floor(values.length * 0.8)],
            ];
            const PUEBLA_COLOR = "#b10026";
            function getColor(value, cvegeo) {
                if (cvegeo == PUEBLA_CVEGEO) return PUEBLA_COLOR;

                // --- CORRECCIÓN CLAVE AQUÍ ---
                // 1. Convertimos el valor (que puede ser un string) a número justo antes de comparar.
                const numericValue = parseFloat(value);

                // 2. Si no es un número válido o no hay datos, lo pintamos de gris.
                if (isNaN(numericValue)) return "#ccc";

                // 3. Ahora la comparación es entre NÚMERO y NÚMERO, 100% confiable.
                return numericValue >= breaks[4]
                    ? "#084594"
                    : numericValue >= breaks[3]
                    ? "#2171b5"
                    : numericValue >= breaks[2]
                    ? "#4292c6"
                    : numericValue >= breaks[1]
                    ? "#6baed6"
                    : "#9ecae1";
            }

            styleFunction = (feature) => ({
                fillColor: getColor(
                    mapData[feature.properties.cvegeo],
                    feature.properties.cvegeo
                ),
                weight: 1,
                opacity: 1,
                color: "white",
                fillOpacity: 0.8,
            });

            mapLegend.innerHTML = `
            <h5>Leyenda</h5>
            ${
                pueblaValue !== undefined
                    ? `<div><i class="legend-swatch" style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                          pueblaValue
                      )})</div><hr class='my-1'>`
                    : ""
            }
            <div class="text-muted small">Resto de municipios:</div>
            <div><i class="legend-swatch" style="background:#084594"></i> ${formatNumber(
                breaks[4]
            )} o más</div>
            <div><i class="legend-swatch" style="background:#2171b5"></i> ${formatNumber(
                breaks[3]
            )} - ${formatNumber(breaks[4])}</div>
            <div><i class="legend-swatch" style="background:#4292c6"></i> ${formatNumber(
                breaks[2]
            )} - ${formatNumber(breaks[3])}</div>
            <div><i class="legend-swatch" style="background:#6baed6"></i> ${formatNumber(
                breaks[1]
            )} - ${formatNumber(breaks[2])}</div>
            <div><i class="legend-swatch" style="background:#9ecae1"></i> Menos de ${formatNumber(
                breaks[1]
            )}</div>
            <div><i class="legend-swatch" style="background:#ccc"></i> Sin datos</div>
        `;
        } else {
            // CASO B: NO HAY SUFICIENTES DATOS, usamos una lógica simple.
            console.log(
                "-> No hay suficientes datos. Usando simbología simple."
            );

            const PUEBLA_COLOR = "#b10026"; // Color para Puebla
            const DATA_COLOR = "#4292c6"; // Color para otros municipios con datos

            styleFunction = (feature) => {
                const cvegeo = feature.properties.cvegeo;
                let color = "#ccc"; // Color por defecto (sin datos)
                if (cvegeo == PUEBLA_CVEGEO && pueblaValue !== undefined) {
                    color = PUEBLA_COLOR;
                } else if (
                    mapData[cvegeo] !== undefined &&
                    mapData[cvegeo] !== null
                ) {
                    color = DATA_COLOR;
                }
                return {
                    fillColor: color,
                    weight: 1,
                    opacity: 1,
                    color: "white",
                    fillOpacity: 0.8,
                };
            };

            mapLegend.innerHTML = `
            <h5>Leyenda</h5>
            ${
                pueblaValue !== undefined
                    ? `<div><i style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                          pueblaValue
                      )})</div>`
                    : ""
            }
            ${
                values.length > 0
                    ? `<div><i class="legend-swatch" style="background:${DATA_COLOR}"></i> Otros municipios con datos</div>`
                    : ""
            }
            <div><i style="background:#ccc"></i> Sin datos</div>
        `;
        }

        // --- FIN DE LA LÓGICA ADAPTABLE ---

        // El resto de la función que dibuja el mapa se queda igual
        geojsonLayer = L.geoJson(pueblaGeoJSON, {
            style: styleFunction,
            onEachFeature: (feature, layer) => {
                const nombre = feature.properties.nomgeo;
                const valor = formatNumber(mapData[feature.properties.cvegeo]);
                layer.bindPopup(
                    `<strong>${nombre}</strong><br>Valor: ${valor}`
                );
            },
        }).addTo(map);

        mapLegend.style.display = "block";
    }

    /**
     * Muestra el mapa centrado y resaltado en un solo municipio.
     * @param {string} municipioId El ID (cvegeo) del municipio a resaltar.
     */
    function displaySingleMunicipalityMap(municipioId) {
        // Seguridad: no hacer nada si el mapa o el GeoJSON no están listos.
        if (!pueblaGeoJSON || !map) {
            console.error("El mapa o el GeoJSON no se han inicializado.");
            return;
        }

        // Limpia cualquier capa de municipios y la leyenda anterior.
        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }
        mapLegend.innerHTML = ""; // No necesitamos leyenda para un solo municipio.

        let found = false;
        // Dibuja la capa de municipios con una lógica de estilo condicional.
        geojsonLayer = L.geoJSON(pueblaGeoJSON, {
            style: function (feature) {
                // Si el 'cvegeo' del polígono coincide con el ID que buscamos...
                if (feature.properties.cvegeo == municipioId) {
                    // ...lo pintamos de color azul y con un borde más grueso.
                    return {
                        fillColor: "#0c312d", // Azul primario de Bootstrap
                        weight: 2,
                        color: "#333", // Borde oscuro
                        fillOpacity: 0.7,
                    };
                } else {
                    // A todos los demás les damos un estilo neutro y semitransparente.
                    return {
                        fillColor: "#ccc",
                        weight: 1,
                        color: "white",
                        fillOpacity: 0.5,
                    };
                }
            },
            onEachFeature: function (feature, layer) {
                // Cuando Leaflet dibuja el polígono del municipio que nos interesa...
                if (feature.properties.cvegeo == municipioId) {
                    // ...le decimos al mapa que haga zoom y se centre en los límites de ese polígono.
                    map.fitBounds(layer.getBounds());
                    found = true;
                }
                // A todos los municipios les añadimos un popup con su nombre.
                layer.bindPopup(
                    `<strong>${feature.properties.nomgeo}</strong>`
                );
            },
        }).addTo(map);

        if (!found) {
            console.warn(
                `No se encontró el polígono para el municipio con cvegeo: ${municipioId}`
            );
        }
    }

    /**
     * Construye el payload y llama al endpoint de exportación para descargar el CSV.
     */
    function handleExport() {
        // console.log("Iniciando exportación...");
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
     * Renderiza el gráfico (MODIFICADA para aceptar el contenedor)
     */
    // function renderizarGrafico(datosParaGrafico) {
    function renderizarGrafico(datosParaGrafico) {
        chartContainer.innerHTML = "";
        chartTitle.innerText = datosParaGrafico.titulo;

        // Ocultamos/mostramos botones
        exportBtn.style.display = "none";
        fullscreenBtn.style.display = "none";

        // --- USAMOS LOS ELEMENTOS ÚNICOS ---
        metadataContainer.style.display = "block";
        descriptionElement.innerText =
            datosParaGrafico.descripcion || "No disponible.";
        sourceElement.innerText = datosParaGrafico.fuente || "No disponible.";
        methodElement.innerText =
            datosParaGrafico.metodo_calculo || "No disponible.";

        if (datosParaGrafico.series && datosParaGrafico.series.length > 0) {
            fullscreenBtn.style.display = "block";
        }

        if (datosParaGrafico.nota_explicativa) {
            chartNoteContainer.innerHTML = `<p class="mb-0">${datosParaGrafico.nota_explicativa}</p>`;
            chartNoteContainer.style.display = "block";
        } else {
            chartNoteContainer.innerHTML = "";
            chartNoteContainer.style.display = "none";
        }

        const years = datosParaGrafico.available_years;

        if (Array.isArray(years) && years.length > 0) {
            availableYearsElement.innerText = years.sort().join(", ");
            yearSelectorContainer.style.display = "block";
            exportBtn.style.display = "block"; // Solo hay un botón de exportar

            yearSelectorEl.clearOptions();
            years.forEach((year) => {
                yearSelectorEl.addOption({ value: year, text: year });
            });

            if (datosParaGrafico.selected_years) {
                yearSelectorEl.setValue(datosParaGrafico.selected_years, true);
            }
        } else {
            availableYearsElement.innerText = "No disponible";
            yearSelectorContainer.style.display = "none";
            exportBtn.style.display = "none";
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
            // --- INICIO DE LA CORRECCIÓN ---
            // 1. ¡EL GUARDIA! Si no hay eje_x o no hay series,
            // creamos una opción de gráfico vacío y salimos.
            if (!datosParaGrafico.eje_x || !datosParaGrafico.series) {
                console.warn(
                    "Respuesta de API no contenía 'eje_x' o 'series'. Mostrando 'sin datos'."
                );
                options = {
                    series: [], // Series vacías
                    chart: {
                        type: "line", // Tipo por defecto
                        height: 450,
                    },
                    noData: {
                        // El mensaje que verá el usuario
                        text: "No hay datos disponibles para esta selección.",
                    },
                };
            } else {
                // 2. Si SÍ hay eje_x, continuamos con tu lógica normal.
                // (Esta es tu lógica original, solo asegúrate de que esté
                // dentro de este nuevo 'else')

                let xaxisOptions = {
                    type: "category",
                    categories: datosParaGrafico.eje_x.categorias || [],
                    labels: {
                        hideOverlappingLabels: false,
                        trim: false,
                    },
                };

                // Si es un gráfico de líneas...
                if (datosParaGrafico.tipo_grafico === "line") {
                    if (
                        !datosParaGrafico.eje_x.categorias ||
                        datosParaGrafico.eje_x.categorias.length === 0
                    ) {
                        const allYears = datosParaGrafico.series.flatMap(
                            (serie) => serie.data.map((point) => point[0])
                        );
                        const uniqueYears = [...new Set(allYears)].sort(
                            (a, b) => a - b
                        );

                        console.log("Años únicos encontrados:", uniqueYears);

                        // 2. Reestructuramos las series para que coincidan con las categorías.
                        const newSeries = datosParaGrafico.series.map(
                            (serie) => {
                                const dataMap = new Map(serie.data);
                                return {
                                    name: serie.name,
                                    data: uniqueYears.map(
                                        (year) => dataMap.get(year) ?? null
                                    ),
                                };
                            }
                        );

                        console.log(
                            "Nuevas series reestructuradas:",
                            newSeries
                        );

                        // 3. Construimos el objeto de opciones DESDE CERO para este caso.
                        options = {
                            series: newSeries, // Usamos las series reestructuradas
                            chart: {
                                type: "line",
                                height: 500,
                                animations: { enabled: false },
                                toolbar: { show: true },
                            },
                            colors: PALETA_COLORES,
                            stroke: { curve: "smooth", width: 2 },
                            yaxis: {
                                title: { text: datosParaGrafico.eje_y.titulo },
                                labels: {
                                    formatter: (value) =>
                                        new Intl.NumberFormat("es-MX").format(
                                            value
                                        ),
                                },
                            },
                            xaxis: {
                                // Ahora sí podemos usar 'category' porque los datos coinciden
                                type: "category",
                                categories: uniqueYears,
                                title: {
                                    text:
                                        datosParaGrafico.eje_x.titulo || "Año",
                                },
                            },
                            noData: {
                                text: "No hay datos disponibles para esta selección.",
                            },
                            tooltip: {
                                shared: false, 
                                followCursor: true,
                            },
                        };
                    }
                }

                // Si 'options' no fue definido por el gráfico de línea,
                // usamos las de barra/default.
                if (Object.keys(options).length === 0) {
                    options = {
                        series: datosParaGrafico.series,
                        chart: {
                            type: datosParaGrafico.tipo_grafico,
                            height: 450,
                            toolbar: { show: true },
                            animations: { enabled: false },
                        },
                        colors: PALETA_COLORES,
                        xaxis: xaxisOptions,
                        yaxis: {
                            // Añadimos seguridad aquí también
                            title: {
                                text: datosParaGrafico.eje_y?.titulo || "",
                            },
                            labels: {
                                formatter: (value) =>
                                    new Intl.NumberFormat("es-MX").format(
                                        value
                                    ),
                            },
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: "smooth", width: 2 },
                        noData: {
                            text: "No hay datos disponibles para esta selección.",
                        },
                        tooltip: {
                            shared: false,
                            // intersect: true,
                            followCursor: true,
                        },
                    };
                }
            }
            // --- FIN DE LA CORRECCIÓN ---
        }

        lastChartOptions = options;
        if (chart) chart.destroy();
        chart = new ApexCharts(chartContainer, options);
        chart.render();
    }

    function renderizarGraficoOLD(datosParaGrafico, container, titleElement) {
        container.innerHTML = "";
        titleElement.innerText = datosParaGrafico.titulo;

        const activeExportBtn =
            appState.nivelDeAgregacion === "municipio" ? exportBtn : exportBtn;
        // chartContainer.innerHTML = "";
        container.innerHTML = "";
        titleElement.innerText = datosParaGrafico.titulo;
        exportBtn.style.display = "none";

        // --- LÓGICA PARA ELEGIR LOS ELEMENTOS CORRECTOS ---
        const isMunicipal = appState.nivelDeAgregacion === "municipio";

        const years = datosParaGrafico.available_years;

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

        const currentAvailableYearsElement = isMunicipal
            ? availableYearsElement
            : availableYearsElementRegions;

        // --- AHORA USAMOS LAS VARIABLES 'current' ---
        currentMetadata.style.display = "block";
        currentDescription.innerText =
            datosParaGrafico.descripcion || "No disponible.";
        currentSource.innerText = datosParaGrafico.fuente || "No disponible.";

        // Mostramos el botón de pantalla completa si hay un gráfico
        if (datosParaGrafico.series && datosParaGrafico.series.length > 0) {
            fullscreenBtn.style.display = "block";
            fullscreenBtnRegions.style.display = "block";
        } else {
            fullscreenBtn.style.display = "none";
            fullscreenBtnRegions.style.display = "none";
        }

        if (Array.isArray(years) && years.length > 0) {
            // Si 'years' es un array y no está vacío, lo mostramos.
            currentAvailableYearsElement.innerText = years.sort().join(", ");

            // Mostramos el contenedor del selector de años y el botón de exportar.
            currentYearContainer.style.display = "block";
            activeExportBtn.style.display = "block";

            // Lógica para llenar el selector de años con las nuevas opciones.
            currentYearEl.clearOptions();
            years.forEach((year) => {
                currentYearEl.addOption({ value: year, text: year });
            });

            // Sincronizamos el selector con los años que ya están seleccionados en el estado.
            if (datosParaGrafico.selected_years) {
                currentYearEl.setValue(datosParaGrafico.selected_years, true);
            }
        } else {
            // Para cualquier otro caso (si no hay años disponibles), mostramos "No disponible".
            currentAvailableYearsElement.innerText = "No disponible";

            // Ocultamos el selector de años y el botón de exportar.
            currentYearContainer.style.display = "none";
            activeExportBtn.style.display = "none";
        }
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
            // Hacemos visible el contenedor del selector de años
            currentYearContainer.style.display = "block";

            // Mostramos el botón de exportar correspondiente
            activeExportBtn.style.display = "block";

            // Vaciamos las opciones anteriores de Tom Select antes de añadir las nuevas
            currentYearEl.clearOptions();

            // Añadimos las nuevas opciones disponibles
            datosParaGrafico.available_years.forEach((year) => {
                currentYearEl.addOption({ value: year, text: year });
            });

            // Si el backend nos indica qué años deben estar seleccionados, los establecemos
            if (datosParaGrafico.selected_years) {
                // Usamos setValue y el parámetro 'silent' (true) para evitar un bucle infinito de eventos
                currentYearEl.setValue(datosParaGrafico.selected_years, true);
            }
        } else {
            // Si no hay años disponibles, ocultamos el selector
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
                labels: {
                    hideOverlappingLabels: false,
                    trim: false,
                },
            };

            // Si es un gráfico de líneas, sobreescribimos la configuración
            if (datosParaGrafico.tipo_grafico === "line") {
                if (
                    !datosParaGrafico.eje_x.categorias ||
                    datosParaGrafico.eje_x.categorias.length === 0
                ) {
                    const allYears = datosParaGrafico.series.flatMap((serie) =>
                        serie.data.map((point) => point[0])
                    );
                    const uniqueYears = [...new Set(allYears)].sort(
                        (a, b) => a - b
                    );

                    console.log("Años únicos encontrados:", uniqueYears);

                    // 2. Reestructuramos las series para que coincidan con las categorías.
                    const newSeries = datosParaGrafico.series.map((serie) => {
                        const dataMap = new Map(serie.data);
                        return {
                            name: serie.name,
                            data: uniqueYears.map(
                                (year) => dataMap.get(year) ?? null
                            ),
                        };
                    });

                    console.log("Nuevas series reestructuradas:", newSeries);

                    // 3. Construimos el objeto de opciones DESDE CERO para este caso.
                    options = {
                        series: newSeries, // Usamos las series reestructuradas
                        chart: {
                            type: "line",
                            height: 500,
                            animations: { enabled: false },
                            toolbar: { show: true },
                        },
                        colors: PALETA_COLORES,
                        stroke: { curve: "smooth", width: 2 },
                        yaxis: {
                            title: { text: datosParaGrafico.eje_y.titulo },
                            labels: {
                                formatter: (value) =>
                                    new Intl.NumberFormat("es-MX").format(
                                        value
                                    ),
                            },
                        },
                        xaxis: {
                            // Ahora sí podemos usar 'category' porque los datos coinciden
                            type: "category",
                            categories: uniqueYears,
                            title: {
                                text: datosParaGrafico.eje_x.titulo || "Año",
                            },
                        },
                        noData: {
                            text: "No hay datos disponibles para esta selección.",
                        },
                    };
                    // xaxisOptions = {
                    //     type: "numeric",
                    //     title: { text: datosParaGrafico.eje_x.titulo || "Año" },
                    //     // 2. Le decimos al gráfico que el número de etiquetas es igual al de años únicos
                    //     tickAmount:
                    //         uniqueYears.length > 10 ? 10 : uniqueYears.length, // Limita a 10 para no saturar
                    //     labels: {
                    //         formatter: function (value) {
                    //             return parseInt(value, 10);
                    //         },
                    //     },
                    // };
                    // xaxisOptions = {
                    //     type: "numeric",
                    //     title: {
                    //         text: datosParaGrafico.eje_x.titulo || "Año",
                    //     },
                    //     labels: {
                    //         formatter: function (value) {
                    //             // Convierte el valor a un entero para quitar los decimales
                    //             return parseInt(value, 10);
                    //         },
                    //     },
                    // };
                }
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
                    animations: {
                        enabled: false, // Desactiva todas las animaciones
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

        lastChartOptions = options;
        if (chart) chart.destroy();
        chart = new ApexCharts(container, options);
        chart.render();
    }

    // --- 5. FUNCIÓN "DIRECTORA" PRINCIPAL ---

    function updateDashboard() {
        if (
            appState.isLoading ||
            !appState.indicatorId ||
            (appState.nivelDeAgregacion === "municipio" &&
                appState.municipioIds.length === 0)
        ) {
            return;
        }
        appState.isLoading = true;

        chartTitle.innerText = "Cargando...";
        chartContainer.innerHTML =
            '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

        if (mapContainer) mapContainer.style.display = "none";
        chartContainer.style.display = "block"; // El contenedor del gráfico siempre es visible

        // Lógica de decisión (sin cambios)
        const indicadorSeleccionado = document.querySelector(
            `.indicador-link[data-indicador-id='${appState.indicatorId}']`
        );
        const esAbsoluto =
            indicadorSeleccionado &&
            indicadorSeleccionado.dataset.tipoDato.toLowerCase() === "absoluto";
        const esEstatal =
            appState.nivelDeAgregacion === "municipio" &&
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] === "estatal";
        const esUnMunicipio =
            appState.nivelDeAgregacion === "municipio" &&
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] !== "estatal";
        const esUnaMicrorregion =
            appState.nivelDeAgregacion === "microrregion" &&
            appState.microrregionId;
        const esUnaMacrorregion =
            appState.nivelDeAgregacion === "macrorregion" &&
            appState.macrorregionId;

        const esCasoChoropleth =
            esEstatal && esAbsoluto && !appState.indicatorEsComplejo;

        let showMap =
            esUnMunicipio ||
            esCasoChoropleth ||
            esUnaMicrorregion ||
            esUnaMacrorregion;

        // --- Lógica de Mapa (SIMPLE) ---
        if (showMap) {
            mapContainer.style.display = "block";
            // Forzamos la actualización de tamaño
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 10);

            if (esUnMunicipio) {
                const municipioId = appState.municipioIds[0];
                const optionData = municipioSelector.options[municipioId];
                const cvegeo = optionData ? optionData.cvegeo : null;
                if (cvegeo) {
                    displaySingleMunicipalityMap(cvegeo);
                }
            } else if (esUnaMicrorregion) {
                displaySingleFeatureMap(
                    microGeoJSON,
                    appState.microrregionId,
                    "id_micro"
                );
            } else if (esUnaMacrorregion) {
                displaySingleFeatureMap(
                    macroGeoJSON,
                    appState.macrorregionId,
                    "id_macro"
                );
            }
        }

        // Construcción del payload (sin cambios)
        let payload = {
            indicador_id: appState.indicatorId,
            anios: appState.selectedYears,
            nivel_de_agregacion: appState.nivelDeAgregacion,
        };
        if (appState.nivelDeAgregacion === "municipio") {
            payload.municipio_ids = appState.municipioIds;
        } else {
            payload.region_id =
                appState.nivelDeAgregacion === "microrregion"
                    ? appState.microrregionId
                    : appState.macrorregionId;
        }
        fetch(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response)
            )
            .then((data) => {
                // ¡Llamada simple!
                renderizarGrafico(data);

                // Lógica del coropletas
                if (esCasoChoropleth && data.mapData) {
                    if (map) {
                        map.setView([19.0414, -98.2063], 7);
                    }
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

        // Llamada fetch
    }

    // --- 6. CARGA INICIAL Y LISTENERS ---

    /**
     * Se ejecuta una sola vez al cargar la página. Espera a que el mapa se inicialice
     * y luego decide el estado inicial de la aplicación (desde URL o por defecto).
     */
    async function CargaInicial() {
        // console.log("Iniciando CargaInicial()...");

        // --- PASO 1: ESPERAMOS a que el mapa se inicialice ---
        await initMap();

        // --- PASO 2: Preparamos la opción 'estatal' ---
        // Guardamos la opción 'estatal' en una variable para poder añadirla y quitarla.
        // Ya no necesitamos clonarla desde jQuery.
        opcionEstatalElement = {
            value: "estatal",
            text: "-- Total Estatal --",
        };

        // La añadimos por defecto. La función gestionarOpcionEstatal decidirá si debe quedarse.
        municipioSelector.addOption(opcionEstatalElement);

        // --- PASO 3: Leemos los parámetros de la URL (sin cambios) ---
        const urlParams = new URLSearchParams(window.location.search);
        const indicadorIdFromUrl = urlParams.get("indicador_id");
        const municipioIdsFromUrl = urlParams.get("municipio_ids");

        // --- PASO 4: Decidimos el estado inicial ---
        if (indicadorIdFromUrl && municipioIdsFromUrl) {
            // CASO A: Hay parámetros en la URL.
            console.log(
                "-> Parámetros de URL detectados. Cargando selección específica."
            );

            appState.indicatorId = indicadorIdFromUrl;
            appState.municipioIds = municipioIdsFromUrl.split(",");

            // La nueva forma de establecer el valor. No necesita .trigger()
            municipioSelector.setValue(appState.municipioIds);

            // El resto de la lógica para activar el indicador es igual...
            const linkActivo = document.querySelector(
                `.indicador-link[data-indicador-id='${appState.indicatorId}']`
            );
            if (linkActivo) {
                linkActivo.classList.add("fw-bold", "text-primary");
                expandirAcordeonHacia(linkActivo);
                const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
                gestionarOpcionEstatal(tipoDato);
            }
        } else {
            // CASO B: No hay parámetros en la URL.
            // console.log("-> Sin parámetros de URL. Usando carga por defecto.");
            const firstIndicatorLink =
                document.querySelector(".indicador-link");
            if (firstIndicatorLink) {
                const tipoDato =
                    firstIndicatorLink.dataset.tipoDato || "Absoluto";
                gestionarOpcionEstatal(tipoDato);

                let idSeleccionInicial = "estatal";
                if (tipoDato.toLowerCase() !== "absoluto") {
                    // Obtenemos el ID del primer municipio "real" de una forma más directa
                    const primerMunicipio = document.querySelector(
                        "#municipio-selector option:not([value='estatal'])"
                    );
                    if (primerMunicipio) {
                        idSeleccionInicial = primerMunicipio.value;
                    }
                }

                appState.municipioIds = [idSeleccionInicial];
                // Establecemos el valor con la nueva API
                municipioSelector.setValue(appState.municipioIds);

                // El resto de la lógica es igual...
                appState.indicatorId = firstIndicatorLink.dataset.indicadorId;
                firstIndicatorLink.classList.add("fw-bold", "text-primary");
                expandirAcordeonHacia(firstIndicatorLink);
            }
        }

        // --- PASO 5: Llamamos a la actualización (sin cambios) ---
        updateDashboard();
        gestionarBotonResumen();
    }

    // Aquí van TODOS tus listeners:
    /**
     * Listener para las pestañas principales (Municipio, Micro, Macro).
     * Se dispara cuando una nueva pestaña ha sido mostrada.
     */
    nivelTabs.forEach((tab) => {
        tab.addEventListener("show.bs.tab", function (event) {
            const nivel = event.currentTarget.dataset.nivel;
            console.log(`Cambiando a nivel: ${nivel}`);

            // 1. Actualizamos el estado central
            appState.nivelDeAgregacion = nivel;
            appState.indicatorId = null;
            appState.municipioIds = [];
            appState.microrregionId = null;
            appState.macrorregionId = null;
            appState.selectedYears = [];

            // 2. Limpiamos visualmente los selectores
            municipioSelector.clear();
            microrregionSelector.clear();
            macrorregionSelector.clear();
            yearSelectorEl.clearOptions();
            yearSelectorEl.clear();
            yearSelectorContainer.style.display = "none";

            // 3. Limpiamos el dashboard
            chartContainer.innerHTML = `<p class="text-muted text-center pt-5">Selecciona un indicador y una ubicación para comenzar.</p>`;
            chartTitle.innerText = "Gráfico";
            if (mapContainer) mapContainer.style.display = "none";
            if (metadataContainer) metadataContainer.style.display = "none";
            exportBtn.style.display = "none";

            // 4. Deseleccionamos todos los indicadores
            document
                .querySelectorAll(".indicador-link")
                .forEach((el) =>
                    el.classList.remove("fw-bold", "text-primary")
                );

            // 5. Deshabilitamos el botón de consulta
            consultarBtn.disabled = true;

            // 6. ¡LÓGICA CLAVE! Filtramos el ÚNICO acordeón
            if (nivel !== "municipio") {
                filtrarAcordeonRegional(false); // Muestra solo 'Absoluto'
                ocultarTematicasVacias();

                // Opcional: auto-seleccionar la primera región
                if (nivel === "microrregion") {
                    const primerMicro = document.querySelector(
                        "#microrregion-selector option"
                    );
                    if (primerMicro) {
                        const primerMicroId = primerMicro.value;
                        // Actualizamos nuestro estado
                        appState.microrregionId = primerMicroId;
                        // Le decimos a Tom Select que se actualice (en modo silencioso)
                        microrregionSelector.setValue(primerMicroId, true);
                    }
                    appState.macrorregionId = null; // Nos aseguramos que el otro esté nulo
                } else if (nivel === "macrorregion") {
                    const primerMacro = document.querySelector(
                        "#macrorregion-selector option"
                    );
                    if (primerMacro) {
                        const primerMacroId = primerMacro.value;
                        // Actualizamos nuestro estado
                        appState.macrorregionId = primerMacroId;
                        // Le decimos a Tom Select que se actualice (en modo silencioso)
                        macrorregionSelector.setValue(primerMacroId, true);
                    }
                    appState.microrregionId = null; // Nos aseguramos que el otro esté nulo
                }
            } else {
                filtrarAcordeonRegional(true); // Muestra todos

                // Re-mostramos las temáticas que pudieron ocultarse
                const todasLastematicas = document.querySelectorAll(
                    "#accordionDimensions .accordion-item"
                );
                todasLastematicas.forEach((t) => (t.style.display = "block"));

                appState.microrregionId = null;
                appState.macrorregionId = null;
            }

            // 7. Expandimos el primer acordeón
            resetearYExpandirPrimerAcordeon(accordionMunicipal);
        });
    });

    const todosLosIndicadores = document.querySelectorAll(".indicador-link");
    todosLosIndicadores.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();

            const tipoDatoNuevo = e.target.dataset.tipoDato || "Absoluto";
            appState.indicatorEsComplejo =
                e.target.dataset.esComplejo === "true";
            gestionarOpcionEstatal(tipoDatoNuevo);

            if (
                tipoDatoNuevo.toLowerCase() !== "absoluto" &&
                appState.municipioIds.includes("estatal")
            ) {
                const primerMunicipio = document.querySelector(
                    "#municipio-selector option:not([value='estatal'])"
                );
                let primerMunicipioId = null;
                if (primerMunicipio) {
                    primerMunicipioId = primerMunicipio.value;
                }
                if (primerMunicipioId) {
                    appState.municipioIds = [primerMunicipioId];
                    municipioSelector.setValue(primerMunicipioId);
                }
            }

            // Actualizamos el estado
            appState.indicatorId = e.target.dataset.indicadorId;
            checkIfCanConsult();
            appState.selectedYears = [];

            // 1. Vaciamos el <select> original del HTML
            document.getElementById("year-selector").innerHTML = "";

            // 2. Le decimos a Tom Select que se sincronice con el <select> ahora vacío
            yearSelectorEl.sync();

            // 3. Limpiamos cualquier valor que pudiera haber quedado seleccionado en la caja de texto
            yearSelectorEl.clear();

            // 4. Ocultamos los contenedores
            yearSelectorContainer.style.display = "none";

            // Actualizamos estilos (sin cambios)
            todosLosIndicadores.forEach((el) =>
                el.classList.remove("fw-bold", "text-primary")
            );
            document
                .querySelectorAll(
                    `.indicador-link[data-indicador-id='${appState.indicatorId}']`
                )
                .forEach((activeLink) =>
                    activeLink.classList.add("fw-bold", "text-primary")
                );

            updateDashboard();
        });
    });

    consultarBtn.addEventListener("click", () => {
        // Simplemente llamamos a la función que dibuja todo
        updateDashboard();
    });

    /**
     * Revisa el estado actual y habilita/deshabilita el botón "Consultar"
     */
    function checkIfCanConsult() {
        let canConsult = false;
        const isMunicipal = appState.nivelDeAgregacion === "municipio";

        // Verificamos si tenemos la información mínima necesaria
        if (appState.indicatorId) {
            if (isMunicipal && appState.municipioIds.length > 0) {
                canConsult = true;
            } else if (
                appState.nivelDeAgregacion === "microrregion" &&
                appState.microrregionId
            ) {
                canConsult = true;
            } else if (
                appState.nivelDeAgregacion === "macrorregion" &&
                appState.macrorregionId
            ) {
                canConsult = true;
            }
        }
        if (consultarBtn) {
            // (Añadimos una pequeña seguridad por si acaso)
            consultarBtn.disabled = !canConsult;
        }
    }

    fullscreenModal.addEventListener("shown.bs.modal", () => {
        if (fullscreenChart) {
            fullscreenChart.destroy();
        }
        // Creamos una copia de las opciones, pero ajustamos la altura
        let fullscreenOptions = { ...lastChartOptions };
        fullscreenOptions.chart.height = window.innerHeight * 0.8; // 80% de la altura de la pantalla

        fullscreenModalTitle.innerText =
            lastChartOptions.series.length > 10
                ? "Cultivos en Pantalla Completa"
                : "Gráfico en Pantalla Completa";

        fullscreenChart = new ApexCharts(
            fullscreenChartContainer,
            fullscreenOptions
        );
        fullscreenChart.render();
    });

    // Listener para cuando el modal se oculta
    fullscreenModal.addEventListener("hidden.bs.modal", () => {
        // Destruimos el gráfico para liberar memoria
        if (fullscreenChart) {
            fullscreenChart.destroy();
            fullscreenChart = null;
        }
    });
    exportBtn.addEventListener("click", handleExport);

    // Ejecución inicial
    CargaInicial();
});
