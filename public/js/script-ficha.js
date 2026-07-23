// LOCAL
console.log("Script-ficha.js cargado correctamente v2");
// --- VARIABLES GLOBALES PARA EL MAPA ---
// Las declaramos aquí para que sean accesibles por todas las funciones.
let mapMunicipal = null;
let mapRegional = null;
let geojsonLayerMunicipal = null;
let geojsonLayerRegional = null;

let pueblaGeoJSON = null;
let microGeoJSON = null;
let macroGeoJSON = null;

function limpiarCapasMapa() {
    if (geojsonLayerMunicipal && mapMunicipal) {
        mapMunicipal.removeLayer(geojsonLayerMunicipal);
        geojsonLayerMunicipal = null;
    }
    if (geojsonLayerRegional && mapRegional) {
        mapRegional.removeLayer(geojsonLayerRegional);
        geojsonLayerRegional = null;
    }
}
// Lista negra de microrregiones
const IDS_BLOQUEADOS = ["9", "10", "24"];
const MENSAJE_REGIONALIZACION =
    "Como algunas microrregiones abarcan más de un municipio, no es posible separar sus datos con exactitud. Para estas zonas, puedes consultar la información de cada municipio por separado.";

document.addEventListener("DOMContentLoaded", function () {
    // --- 0. SETUP INICIAL Y LECTURA DE VARIABLES ---
    const appContainer = document.querySelector("[data-api-url]");
    if (!appContainer) {
        return; // Si no estamos en la página de fichas, no ejecutamos nada.
    }

    // --- 2. EL "CEREBRO" O ESTADO CENTRAL ---
    const appState = {
        nivelDeAgregacion: "municipio",
        indicatorId: null,
        indicatorEsComplejo: false,
        municipioIds: [],
        microrregionId: null,
        macrorregionId: null,
        selectedYears: [],
        showAsPercentage: false,
    };

    const API_URL = appContainer.dataset.apiUrl;
    const EXPORT_URL = appContainer.dataset.exportUrl;
    const CSRF_TOKEN = appContainer.dataset.csrfToken;
    let opcionEstatalElement = null;

    // --- 1. REFERENCIAS A ELEMENTOS DEL DOM (ÚNICAS) ---
    const estatalBtn = document.getElementById("estatal-btn");
    const consultarBtn = document.getElementById("consultar-btn");
    const consultarBtnRegions = document.getElementById(
        "consultar-btn-regions",
    );

    const shareBtn = document.getElementById("share-btn");
    const shareBtnRegions = document.getElementById("share-btn-regions");
    const compareStateSwitch = document.getElementById("compare-state-switch");

    const municipioSelector = new TomSelect("#municipio-selector", {
        placeholder: "Selecciona hasta 2 municipios",
        maxItems: 2,
        plugins: {
            clear_button: {
                title: "Quitar todas los municipios seleccionados",
            },
            remove_button: {
                title: "Quitar este municipio",
            },
        },

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
                    (id) => id !== "estatal",
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
            if (IDS_BLOQUEADOS.includes(value)) {
                this.removeItem(value, true); // Quita la selección
                this.blur(); // Quita el foco
                Swal.fire({
                    icon: "warning",
                    title: "Región Unificada",
                    text: MENSAJE_REGIONALIZACION,
                    confirmButtonColor: "#246257",
                });
                return;
            }

            appState.microrregionId = value;
            checkIfCanConsult();
            consultarBtnRegions.classList.replace(
                "btn-secondary",
                "btn-custom-primary",
            );
        },
    });

    // --- INHABILITAR OPCIONES EN EL DROPDOWN ---
    setTimeout(() => {
        IDS_BLOQUEADOS.forEach((id) => {
            const dataExistente = microrregionSelector.options[id];
            if (dataExistente) {
                microrregionSelector.updateOption(id, {
                    ...dataExistente,
                    disabled: true,
                });
            }
        });
        microrregionSelector.refreshOptions(false);
    }, 400);

    const macrorregionSelector = new TomSelect("#macrorregion-selector", {
        placeholder: "Selecciona una macrorregión",
        onChange: function (value) {
            appState.macrorregionId = value;
            checkIfCanConsult();
            consultarBtnRegions.classList.replace(
                "btn-secondary",
                "btn-custom-primary",
            );
        },
    });

    const yearSelectorEl = new TomSelect("#year-selector", {
        placeholder: "Selecciona año(s)",
        plugins: ["remove_button"],
        closeAfterSelect: false,
        onChange: function (value) {
            const selection = value || [];
            if (tienenMismosAnios(selection, appState.selectedYears)) {
                return;
            }
            appState.selectedYears = [...selection];
            actualizarResumenConsulta();
        },
    });

    const yearSelectorElRegions = new TomSelect("#year-selector-regions", {
        placeholder: "Selecciona año(s)",
        plugins: ["remove_button"],
        closeAfterSelect: false,
        onChange: function (value) {
            const selection = value || [];
            if (tienenMismosAnios(selection, appState.selectedYears)) {
                return;
            }
            appState.selectedYears = [...selection];
            actualizarResumenConsulta();
        },
    });

    const exportBtn = document.getElementById("export-btn");
    const exportBtnRegions = document.getElementById("export-btn-regions");

    const microrregionContainer = document.getElementById(
        "microrregion-selector-container",
    );
    const macrorregionContainer = document.getElementById(
        "macrorregion-selector-container",
    );
    const nivelTabs = document.querySelectorAll(".level-switcher .nav-link");
    const accordionMunicipal = document.getElementById("accordionDimensions");
    const accordionRegionsContainer = document.getElementById(
        "accordionDimensionsRegions",
    );

    const chartContainer = document.getElementById("chart-container");
    const chartTitle = document.getElementById("chart-title");
    const consultFeedback = document.getElementById("consult-feedback");
    const viewSummary = document.getElementById("view-summary");
    const viewGuidance = document.getElementById("view-guidance");
    const resumenBtn = document.getElementById("resumen-btn");
    const resumenContainer = document.getElementById("resumen-container");
    const resumenUrlPrototype = resumenBtn ? resumenBtn.href : "";
    const chartContainerRegions = document.getElementById(
        "chart-container-regions",
    );
    const chartTitleRegions = document.getElementById("chart-title-regions");
    const consultFeedbackRegions = document.getElementById(
        "consult-feedback-regions",
    );
    const viewSummaryRegions = document.getElementById("view-summary-regions");
    const viewGuidanceRegions = document.getElementById(
        "view-guidance-regions",
    );

    const mapContainer = document.getElementById("map-container");
    const mapLegend = document.getElementById("map-legend");

    const metadataContainer = document.getElementById("metadata-container");
    const descriptionElement = document.getElementById("indicator-description");
    const sourceElement = document.getElementById("indicator-source");
    const methodElement = document.getElementById("indicator-method");
    const availableYearsElement = document.getElementById(
        "indicator-available-years",
    );
    const yearSelectorContainer = document.getElementById(
        "year-selector-container",
    );
    const yearSelector = document.getElementById("year-selector");
    const chartNoteContainer = document.getElementById("chart-note-container");
    // Selectores de años en regiones
    const metadataContainerRegions = document.getElementById(
        "metadata-container-regions",
    );
    const descriptionElementRegions = document.getElementById(
        "indicator-description-regions",
    );
    const sourceElementRegions = document.getElementById(
        "indicator-source-regions",
    );
    const methodElementRegions = document.getElementById(
        "indicator-method-regions",
    );
    const yearSelectorContainerRegions = document.getElementById(
        "year-selector-container-regions",
    );
    const chartNoteContainerRegions = document.getElementById(
        "chart-note-container-regions",
    );
    const availableYearsElementRegions = document.getElementById(
        "indicator-available-years-regions",
    );
    // Variables para boton de tamaño completo
    const fullscreenBtn = document.getElementById("fullscreen-btn");
    const toggleMapBtn = document.getElementById("toggle-map-btn");
    const toggleMapBtnRegions = document.getElementById(
        "toggle-map-btn-regions",
    );
    const MAP_VISIBILITY_KEY = "bancoIndicadoresMapaVisible";
    let mapVisiblePreference = localStorage.getItem(MAP_VISIBILITY_KEY) !== "false";
    const fullscreenModal = document.getElementById("chart-fullscreen-modal");
    const fullscreenChartContainer = document.getElementById(
        "chart-fullscreen-container",
    );
    const fullscreenModalTitle = document.getElementById(
        "fullscreen-modal-title",
    );
    const fullscreenBtnRegions = document.getElementById(
        "fullscreen-btn-regions",
    );
    let fullscreenChart = null;
    const chartInstances = {
        municipio: null,
        regional: null,
    };
    const lastChartOptionsByView = {
        municipio: null,
        regional: null,
    };
    const rawDataByView = {
        municipio: null,
        regional: null,
    };

    const PALETA_COLORES = [
        "#5f1b2d", // --color1 (Guinda oscuro)
        "#0c312d", // --color5 (Verde oscuro)
        "#c79b66", // --color4 (Dorado)
        "#af1731", // --color3 (Guinda brillante)
        "#609b84", // --color7 (Verde claro)
        "#484747", // --color8 (Gris)
        "#861e34", // --color2 (Guinda medio)
        "#246257", // --color6 (Verde medio)
    ];

    // --- 3. INICIALIZACIÓN DE COMPONENTES ---
    // En tu script-ficha.js, dentro de DOMContentLoaded

    function normalizarAnios(anios = []) {
        return [...anios].map(String).sort();
    }

    function tienenMismosAnios(aniosA = [], aniosB = []) {
        return (
            JSON.stringify(normalizarAnios(aniosA)) ===
            JSON.stringify(normalizarAnios(aniosB))
        );
    }

    function getEmptyStateHtml(isMunicipal) {
        const pasoDos = isMunicipal
            ? "2. Selecciona uno o dos municipios."
            : "2. Selecciona una microrregión o macrorregión.";

        return `
            <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5">
                <i class="fas ${isMunicipal ? "fa-chart-line" : "fa-chart-area"} fa-4x text-light mb-3"></i>
                <p class="text-muted fw-semibold mb-2">Sigue estos pasos para comenzar</p>
                <p class="text-muted mb-1">1. Elige un indicador del catálogo.</p>
                <p class="text-muted mb-1">${pasoDos}</p>
                <p class="text-muted mb-0">3. Presiona Consultar para cargar la gráfica.</p>
            </div>
        `;
    }

    function getTextoOpcion(options, id) {
        if (!options || !options[id]) return null;
        return options[id].text.replace(/\s+/g, " ").trim();
    }

    function getNombreIndicadorActivo() {
        const indicadorActivo = document.querySelector(
            `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
        );
        return indicadorActivo
            ? indicadorActivo.innerText.trim()
            : "Sin indicador";
    }

    function getResumenSeleccionActual() {
        if (appState.nivelDeAgregacion === "municipio") {
            if (appState.municipioIds.length === 0) {
                return "Sin municipios";
            }
            if (
                appState.municipioIds.length === 1 &&
                appState.municipioIds[0] === "estatal"
            ) {
                return "Total estatal";
            }
            return appState.municipioIds
                .map(
                    (id) =>
                        getTextoOpcion(municipioSelector.options, id) ||
                        `Municipio ${id}`,
                )
                .join(", ");
        }

        if (appState.nivelDeAgregacion === "microrregion") {
            return (
                getTextoOpcion(
                    microrregionSelector.options,
                    appState.microrregionId,
                ) || "Sin microrregión"
            );
        }

        return (
            getTextoOpcion(
                macrorregionSelector.options,
                appState.macrorregionId,
            ) || "Sin macrorregión"
        );
    }

    function getResumenAniosActuales() {
        if (!appState.selectedYears.length) {
            return "Todos los disponibles";
        }
        return normalizarAnios(appState.selectedYears).join(", ");
    }

    function actualizarResumenConsulta() {
        const isMunicipal = appState.nivelDeAgregacion === "municipio";
        const target = isMunicipal ? viewSummary : viewSummaryRegions;
        if (!target) return;

        if (!appState.indicatorId) {
            target.innerText = isMunicipal
                ? "Consulta actual: Aún no has seleccionado un indicador."
                : "Consulta actual: Aún no has seleccionado un indicador regional.";
            return;
        }

        const nivelTexto =
            appState.nivelDeAgregacion === "municipio"
                ? "Municipio"
                : appState.nivelDeAgregacion === "microrregion"
                  ? "Microrregión"
                  : "Macrorregión";

        target.innerText =
            `Consulta actual: Indicador: ${getNombreIndicadorActivo()} | ` +
            `Nivel: ${nivelTexto} | ` +
            `Selección: ${getResumenSeleccionActual()} | ` +
            `Años: ${getResumenAniosActuales()}`;
    }

    function actualizarGuiaVista(mensajePersonalizado = null) {
        const isMunicipal = appState.nivelDeAgregacion === "municipio";
        const target = isMunicipal ? viewGuidance : viewGuidanceRegions;
        if (!target) return;

        if (mensajePersonalizado) {
            target.innerText = mensajePersonalizado;
            return;
        }

        if (isMunicipal) {
            target.innerText =
                "Puedes seleccionar hasta 2 municipios. El total estatal solo está disponible para indicadores absolutos.";
            return;
        }

        if (appState.nivelDeAgregacion === "microrregion") {
            target.innerText =
                "Algunas microrregiones no se muestran por limitaciones de desagregación. El mapa se activa cuando la consulta regional aplica.";
            return;
        }

        target.innerText =
            "Estás consultando una macrorregión. El mapa se activa cuando la consulta regional aplica.";
    }

    function actualizarFeedbackConsulta() {
        const isMunicipal = appState.nivelDeAgregacion === "municipio";
        const target = isMunicipal ? consultFeedback : consultFeedbackRegions;
        if (!target) return;

        let message = "";

        if (!appState.indicatorId) {
            message = "Falta seleccionar un indicador.";
        } else if (isMunicipal && appState.municipioIds.length === 0) {
            message = "Falta elegir al menos un municipio.";
        } else if (
            appState.nivelDeAgregacion === "microrregion" &&
            !appState.microrregionId
        ) {
            message = "Falta elegir una microrregión.";
        } else if (
            appState.nivelDeAgregacion === "macrorregion" &&
            !appState.macrorregionId
        ) {
            message = "Falta elegir una macrorregión.";
        } else {
            message = "La consulta está lista para ejecutarse.";
        }

        target.innerText = message;
        target.classList.toggle(
            "text-success",
            message === "La consulta está lista para ejecutarse.",
        );
        target.classList.toggle(
            "text-muted",
            message !== "La consulta está lista para ejecutarse.",
        );
    }

    if (accordionMunicipal && accordionRegionsContainer) {
        let clonedAccordionHTML = accordionMunicipal.innerHTML;

        // --- CADENA DE REEMPLAZO MEJORADA ---

        // 1. Reemplaza los IDs de los paneles colapsables
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /id="collapse-dimension-/g,
            'id="collapse-dimension-region-',
        );
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /id="collapse-tematica-/g,
            'id="collapse-tematica-region-',
        );

        // 2. Reemplaza los data-bs-target de los botones que los controlan
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /data-bs-target="#collapse-dimension-/g,
            'data-bs-target="#collapse-dimension-region-',
        );
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /data-bs-target="#collapse-tematica-/g,
            'data-bs-target="#collapse-tematica-region-',
        );

        // 3. Reemplaza los data-bs-parent para que cada acordeón sea independiente
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /data-bs-parent="#accordionDimensions"/g,
            'data-bs-parent="#accordionDimensionsRegions"',
        );
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /data-bs-parent="#accordionTematicas-/g,
            'data-bs-parent="#accordionTematicas-region-',
        );

        // 4. (LA CLAVE) Reemplaza nuestros data-attributes personalizados en los links
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /data-dimension-target="#collapse-dimension-/g,
            'data-dimension-target="#collapse-dimension-region-',
        );
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /data-tematica-target="#collapse-tematica-/g,
            'data-tematica-target="#collapse-tematica-region-',
        );

        // 5. Reemplaza el ID del sub-acordeón
        clonedAccordionHTML = clonedAccordionHTML.replace(
            /id="accordionTematicas-/g,
            'id="accordionTematicas-region-',
        );

        // --- FIN DE LA CADENA ---

        accordionRegionsContainer.innerHTML = clonedAccordionHTML;

        // --- 3.5. Inicializamos búsqueda en ambos acordeones ---
        setupIndicatorSearch("indicador-search", "accordionDimensions");
        setupIndicatorSearch(
            "indicador-search-regions",
            "accordionDimensionsRegions",
        );
    }

    /**
     * Configura la funcionalidad de búsqueda en tiempo real para los acordeones.
     * @param {string} searchInputId ID del input de búsqueda.
     * @param {string} accordionId ID del contenedor del acordeón.
     */
    function setupIndicatorSearch(searchInputId, accordionId) {
        const searchInput = document.getElementById(searchInputId);
        const accordion = document.getElementById(accordionId);

        if (!searchInput || !accordion) return;

        searchInput.addEventListener("input", function () {
            const term = searchInput.value.toLowerCase().trim();
            const dimensions = accordion.querySelectorAll(".dimension-item");

            dimensions.forEach((dim) => {
                let dimensionHasMatch = false;
                const tematicas = dim.querySelectorAll(".tematica-item");

                tematicas.forEach((tem) => {
                    let tematicaHasMatch = false;
                    const indicators = tem.querySelectorAll(".indicador-link");

                    indicators.forEach((link) => {
                        const text = link.innerText.toLowerCase();
                        const isMatch = text.includes(term);
                        link.parentElement.style.display = isMatch
                            ? "block"
                            : "none";
                        if (isMatch) {
                            tematicaHasMatch = true;
                            dimensionHasMatch = true;
                        }
                    });

                    tem.style.display = tematicaHasMatch ? "block" : "none";

                    // Expandir la temática si tiene coincidencias
                    if (term.length > 0 && tematicaHasMatch) {
                        const collapseTematica = tem.querySelector(
                            ".accordion-collapse",
                        );
                        if (
                            collapseTematica &&
                            !collapseTematica.classList.contains("show")
                        ) {
                            const bsCollapseTematica =
                                bootstrap.Collapse.getOrCreateInstance(
                                    collapseTematica,
                                    { toggle: false },
                                );
                            bsCollapseTematica.show();
                        }
                    }
                });

                dim.style.display = dimensionHasMatch ? "block" : "none";

                // Expandir la dimensión si tiene coincidencias
                if (term.length > 0 && dimensionHasMatch) {
                    const collapse = dim.querySelector(".accordion-collapse");
                    if (collapse && !collapse.classList.contains("show")) {
                        const bsCollapse =
                            bootstrap.Collapse.getOrCreateInstance(collapse, {
                                toggle: false,
                            });
                        bsCollapse.show();
                    }
                }
            });
        });
    }

    // --- 4. FUNCIONES DE AYUDA (DEFINIDAS UNA SOLA VEZ) ---
    /**
     * Genera una URL con los parámetros del estado actual.
     * @returns {string} La URL completa con los parámetros.
     */
    function generarURLdeEstado() {
        const baseUrl = window.location.origin + window.location.pathname;
        const params = new URLSearchParams();

        if (appState.indicatorId) {
            params.append("indicador_id", appState.indicatorId);
        }
        params.append("nivel", appState.nivelDeAgregacion);

        if (appState.nivelDeAgregacion === "municipio") {
            if (appState.municipioIds.length > 0) {
                params.append("municipio_ids", appState.municipioIds.join(","));
            }
        } else {
            const regionId =
                appState.nivelDeAgregacion === "microrregion"
                    ? appState.microrregionId
                    : appState.macrorregionId;
            const finalRegionId = Array.isArray(regionId)
                ? regionId[0]
                : regionId;
            if (finalRegionId) {
                params.append("region_id", finalRegionId);
            }
        }

        if (appState.selectedYears.length > 0) {
            appState.selectedYears.forEach((year) => {
                params.append("anios[]", year);
            });
        }

        return `${baseUrl}?${params.toString()}`;
    }

    /**
     * Genera una URL basada en el estado actual y la copia al portapapeles.
     */
    function handleShareView() {
        // 1. Obtenemos la URL del estado actual
        const shareUrl = generarURLdeEstado();

        // 2. Usamos la API del navegador para copiar
        navigator.clipboard
            .writeText(shareUrl)
            .then(() => {
                // ¡Éxito con SweetAlert!
                Swal.fire({
                    icon: "success",
                    title: "¡Enlace copiado!",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                });
            })
            .catch((err) => {
                // Error con SweetAlert
                console.error("Error al copiar el enlace: ", err);
                Swal.fire({
                    icon: "error",
                    title: "¡Error al copiar!",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                });
            });
    }
    /**
     * Muestra u oculta la opción "Total Estatal" basándose en el tipo de dato.
     * @param {string} tipoDato El tipo de dato del indicador actual.
     */
    function gestionarOpcionEstatal(tipoDato) {
        appState.indicatorTipoDato = tipoDato;
        appState.showAsPercentage = false;
        const esAbsoluto = tipoDato.toLowerCase() === "absoluto";
        estatalBtn.style.display = esAbsoluto ? "block" : "none";
    }

    /**
     * Transforma datos absolutos a porcentajes para la visualización actual.
     */
    function aplicarPorcentajePorCategoria(datos) {
        const totalsByCategory = new Map();
        datos.series.forEach(serie => {
            serie.data.forEach(point => {
                const category = Array.isArray(point) ? point[0] : null;
                const value = Array.isArray(point) ? point[1] : point;
                totalsByCategory.set(
                    category,
                    (totalsByCategory.get(category) || 0) + Math.abs(Number(value) || 0),
                );
            });
        });

        datos.series.forEach(serie => {
            serie.data = serie.data.map(point => {
                const isPoint = Array.isArray(point);
                const category = isPoint ? point[0] : null;
                const value = isPoint ? point[1] : point;
                const denominator = totalsByCategory.get(category) || 0;
                if (!denominator) return isPoint ? [category, 0] : 0;
                const pct = +(Math.abs(Number(value) || 0) / denominator * 100).toFixed(2);
                const signedPct = Number(value) < 0 ? -pct : pct;
                return isPoint ? [category, signedPct] : signedPct;
            });
        });
    }

    function transformarAPorcentaje(datos) {
        if (!datos || !datos.series) return datos;
        const cloned = JSON.parse(JSON.stringify(datos));

        // Identificar series que son "Total" (case-insensitive)
        const isTotalSerie = (name) => name && name.toLowerCase().includes("total");

        // La serie Total duplica la suma de las demás y no debe graficarse en modo %.
        cloned.series = cloned.series.filter(serie => !isTotalSerie(serie.name));
        if (!cloned.series.length) return datos;

        if (cloned.tipo_grafico === "piramide") {
            const grandTotal = cloned.series.reduce(
                (total, serie) => total + serie.data.reduce((sum, value) => sum + Math.abs(value), 0),
                0,
            );
            if (!grandTotal) return datos;

            cloned.series.forEach(serie => {
                serie.data = serie.data.map(value => {
                    const pct = +(Math.abs(value) / grandTotal * 100).toFixed(2);
                    return value < 0 ? -pct : pct;
                });
            });
        } else if (Array.isArray(cloned.eje_x?.categorias)) {
            // En gráficos de barras, "Total" suele ser una categoría del eje X,
            // no una serie independiente.
            const totalIndexes = cloned.eje_x.categorias.reduce((indexes, category, index) => {
                if (isTotalSerie(String(category))) indexes.push(index);
                return indexes;
            }, []);
            const totalIndexSet = new Set(totalIndexes);

            if (totalIndexes.length) {
                cloned.eje_x.categorias = cloned.eje_x.categorias.filter((_, index) => !totalIndexSet.has(index));
                cloned.series.forEach(serie => {
                    const values = serie.data.filter((_, index) => !totalIndexSet.has(index));
                    const denominator = values.reduce((sum, value) => sum + Math.abs(Number(value) || 0), 0);
                    serie.data = denominator
                        ? values.map(value => {
                            const pct = +(Math.abs(Number(value) || 0) / denominator * 100).toFixed(2);
                            return Number(value) < 0 ? -pct : pct;
                        })
                        : values.map(() => 0);
                });
            } else {
                const esSerieUnicaDeCategorias = cloned.series.length === 1 &&
                    cloned.series[0].data.every(value => !Array.isArray(value));
                if (esSerieUnicaDeCategorias) {
                    const serie = cloned.series[0];
                    const denominator = serie.data.reduce(
                        (sum, value) => sum + Math.abs(Number(value) || 0),
                        0,
                    );
                    serie.data = denominator
                        ? serie.data.map(value => +(Math.abs(Number(value) || 0) / denominator * 100).toFixed(2))
                        : serie.data.map(() => 0);
                } else {
                    aplicarPorcentajePorCategoria(cloned);
                }
            }
        } else {
            // Para opciones/años, cada categoría se expresa como parte de su total.
            aplicarPorcentajePorCategoria(cloned);
        }

        cloned._esPorcentaje = true;
        return cloned;
    }

    /**
     * Habilita o deshabilita los controles principales de la UI
     * para prevenir acciones mientras se cargan los datos.
     * @param {boolean} isLoading Si es true, deshabilita los controles.
     */
    function setUIInteractivity(isLoading) {
        const isDisabled = isLoading;

        // Botones de consulta
        if (consultarBtn) consultarBtn.disabled = isDisabled;
        if (consultarBtnRegions) consultarBtnRegions.disabled = isDisabled;

        // Botones de acción
        if (exportBtn) exportBtn.disabled = isDisabled;
        if (exportBtnRegions) exportBtnRegions.disabled = isDisabled;
        if (shareBtn) shareBtn.disabled = isDisabled;
        if (shareBtnRegions) shareBtnRegions.disabled = isDisabled;

        // Opcional: Deshabilitar los selectores de TomSelect
        // (Esto es más robusto pero puede sentirse "pesado" para el usuario)
        isDisabled ? municipioSelector.disable() : municipioSelector.enable();
        isDisabled
            ? microrregionSelector.disable()
            : microrregionSelector.enable();
        isDisabled
            ? macrorregionSelector.disable()
            : macrorregionSelector.enable();
        isDisabled ? yearSelectorEl.disable() : yearSelectorEl.enable();
        isDisabled
            ? yearSelectorElRegions.disable()
            : yearSelectorElRegions.enable();
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
        if (
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] !== "estatal"
        ) {
            const municipioId = appState.municipioIds[0];
            // Obtenemos el slug desde los datos cargados en TomSelect
            const selectedOption = municipioSelector.options[municipioId];
            const slug = selectedOption ? selectedOption.slug : municipioId;

            if (resumenBtn && resumenContainer) {
                resumenContainer.style.display = "block";
                resumenBtn.classList.remove("disabled");

                resumenBtn.href = resumenUrlPrototype.replace(
                    "ID_PLACEHOLDER",
                    slug,
                );
            }
        } else {
            if (resumenBtn && resumenContainer) {
                resumenContainer.style.display = "none";
                resumenBtn.classList.add("disabled");
            }
        }
    }
    if (shareBtn) shareBtn.addEventListener("click", handleShareView);
    if (shareBtnRegions)
        shareBtnRegions.addEventListener("click", handleShareView);

    if (toggleMapBtn) {
        toggleMapBtn.addEventListener("click", () => {
            if (mapContainer.style.display === "none") {
                mapContainer.style.display = "block";
                mapVisiblePreference = true;
                localStorage.setItem(MAP_VISIBILITY_KEY, "true");
                toggleMapBtn.classList.replace(
                    "btn-outline-primary",
                    "btn-primary",
                );
                if (mapMunicipal) mapMunicipal.invalidateSize();
                if (appState.municipioIds.length === 1 && appState.municipioIds[0] !== "estatal") {
                    const optionData = municipioSelector.options[appState.municipioIds[0]];
                    if (optionData?.cvegeo) displaySingleMunicipalityMap(optionData.cvegeo);
                }
            } else {
                mapContainer.style.display = "none";
                mapVisiblePreference = false;
                localStorage.setItem(MAP_VISIBILITY_KEY, "false");
                toggleMapBtn.classList.replace(
                    "btn-primary",
                    "btn-outline-primary",
                );
            }
        });
    }

    if (toggleMapBtnRegions) {
        toggleMapBtnRegions.addEventListener("click", () => {
            const container = document.getElementById("map-container-regions");
            if (container.style.display === "none") {
                container.style.display = "block";
                mapVisiblePreference = true;
                localStorage.setItem(MAP_VISIBILITY_KEY, "true");
                toggleMapBtnRegions.classList.replace(
                    "btn-outline-primary",
                    "btn-primary",
                );
                if (mapRegional) mapRegional.invalidateSize();
                if (appState.nivelDeAgregacion === "microrregion" && appState.microrregionId) {
                    displaySingleFeatureMap(microGeoJSON, appState.microrregionId, "id_micro");
                } else if (appState.nivelDeAgregacion === "macrorregion" && appState.macrorregionId) {
                    displaySingleFeatureMap(macroGeoJSON, appState.macrorregionId, "id_macro");
                }
            } else {
                container.style.display = "none";
                mapVisiblePreference = false;
                localStorage.setItem(MAP_VISIBILITY_KEY, "false");
                toggleMapBtnRegions.classList.replace(
                    "btn-primary",
                    "btn-outline-primary",
                );
            }
        });
    }

    if (compareStateSwitch) {
        compareStateSwitch.addEventListener("change", () => {
            const esConsultaMunicipal =
                appState.nivelDeAgregacion === "municipio" &&
                appState.indicatorId &&
                appState.municipioIds.length > 0;
            const esSeleccionEstatal =
                appState.municipioIds.length === 1 &&
                appState.municipioIds[0] === "estatal";

            if (esConsultaMunicipal && !esSeleccionEstatal) {
                updateDashboard();
            }
        });
    }

    /**
     * Filtra la lista de indicadores para la vista regional.
     * @param {boolean} showAll Si es true, muestra todos los indicadores. Si es false, muestra solo los de tipo 'Absoluto'.
     */
    function filtrarAcordeonRegional(showAll = false) {
        const links = document.querySelectorAll(
            "#accordionDimensionsRegions .indicador-link",
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

    /**
     * Revisa el acordeón de regiones y oculta las temáticas que no tienen indicadores visibles.
     */
    function ocultarTematicasVacias() {
        // Obtenemos todos los acordeones de temáticas en la vista de regiones
        const tematicas = document.querySelectorAll(
            "#accordionDimensionsRegions .accordion-item",
        );

        tematicas.forEach((tematica) => {
            // Buscamos si dentro de esta temática hay algún indicador (<li>) que esté visible
            const indicadorVisible = tematica.querySelector(
                'li[style*="display: block"]',
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
            ".accordion-collapse",
        );

        todosLosBotones.forEach((boton) => boton.classList.add("collapsed"));
        todosLosPaneles.forEach((panel) => panel.classList.remove("show"));

        // 2. Después, abrimos solo el primer item.
        const primerBoton = accordionElement.querySelector(".accordion-button");
        const primerPanel = accordionElement.querySelector(
            ".accordion-collapse",
        );

        if (primerBoton && primerPanel) {
            primerBoton.classList.remove("collapsed");
            primerPanel.classList.add("show");
        }
    }

    function expandirAcordeonHacia(linkElemento) {
        if (!linkElemento) return;

        // Subimos por el DOM buscando los paneles colapsables padres
        // en lugar de depender de atributos data- que pueden no existir
        let el = linkElemento.parentElement;
        const panelsToOpen = [];

        while (el) {
            if (el.classList && el.classList.contains("accordion-collapse")) {
                panelsToOpen.push(el);
            }
            el = el.parentElement;
        }

        // Abrimos desde el más externo al más interno
        panelsToOpen.reverse().forEach((panel) => {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(panel, {
                toggle: false,
            });
            bsCollapse.show();
        });
    }

    function getColor(value, quintiles) {
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
                        `${window.APP_URL}/geojson/municipios_puebla_slim.geojson`,
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/Microrregiones2026.geojson`,
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025_slim.geojson`,
                    ),
                ]);

            pueblaGeoJSON = await responseMun.json();
            microGeoJSON = await responseMicro.json();
            macroGeoJSON = await responseMacro.json();
            // console.log("Todos los archivos GeoJSON han sido cargados.");
        } catch (error) {
            console.error(
                "Error crítico: No se pudo cargar el archivo GeoJSON.",
                error,
            );
            if (mapContainer)
                mapContainer.innerHTML =
                    "<p class='text-danger'>No se pudo cargar la cartografía del mapa.</p>";
        }
    }

    function initMapMunicipal() {
        if (mapMunicipal) return;
        if (!document.getElementById("map")) return;
        // Se añade limitación de zoom (minZoom, maxZoom)
        mapMunicipal = L.map("map", { minZoom: 6, maxZoom: 14 }).setView(
            [19.0414, -98.2063],
            8,
        );
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(mapMunicipal);
    }

    function initMapRegional() {
        if (mapRegional) return;
        if (!document.getElementById("map-regions")) return;
        // Se añade limitación de zoom (minZoom, maxZoom)
        mapRegional = L.map("map-regions", { minZoom: 6, maxZoom: 14 }).setView(
            [19.0414, -98.2063],
            8,
        );
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(mapRegional);
    }
    /**
     * Inicializa el objeto del mapa, carga el GeoJSON una sola vez
     * y prepara los selectores de la interfaz del mapa.
     */
    async function initMap() {
        // Primero cargamos los datos
        await loadAllGeoJSON();
        // Luego inicializamos SÓLO el mapa municipal (el único visible al inicio)
        initMapMunicipal();
    }

    /**
     * Muestra el mapa de coropletas para la vista estatal.
     * @param {object} mapData Objeto con {municipioId: valor}.
     */
    function displayChoroplethMap(mapData) {
        if (!mapMunicipal || !pueblaGeoJSON) {
            console.error("El mapa o el GeoJSON no se han inicializado.");
            return;
        }
        if (geojsonLayerMunicipal) {
            mapMunicipal.removeLayer(geojsonLayerMunicipal);
        }

        // --- HELPER: Limpieza de datos ---
        const parseSeguro = (val) => {
            if (val === undefined || val === null || val === "") return null;
            if (typeof val === "number") return val;
            const limpio = val.toString().replace(/,/g, "").replace(/\s/g, "");
            const numero = parseFloat(limpio);
            return isNaN(numero) ? null : numero;
        };

        const formatNumber = (num) => {
            if (num === null) return "N/A";
            return Math.round(num).toLocaleString("es-MX");
        };

        // --- 1. SEPARAMOS PUEBLA CAPITAL ---
        const PUEBLA_CVEGEO = "21114";
        const pueblaRaw = mapData[PUEBLA_CVEGEO];
        const pueblaValue = parseSeguro(pueblaRaw);

        // --- 2. PROCESAMOS EL RESTO ---
        const otherData = { ...mapData };
        delete otherData[PUEBLA_CVEGEO];

        // Obtenemos TODOS los valores limpios
        const allValues = Object.values(otherData)
            .map(parseSeguro)
            .filter((v) => v !== null);

        // Obtenemos SOLO los valores mayores a 0 para calcular rangos útiles
        const nonZeroValues = allValues.filter((v) => v > 0);
        nonZeroValues.sort((a, b) => a - b);

        // console.log(`Total municipios: ${allValues.length}, Con datos > 0: ${nonZeroValues.length}`);

        let styleFunction;
        const PUEBLA_COLOR = "#b10026"; // Rojo intenso para la capital
        // Azul oscuro -> Azul claro
        const colors = ["#084594", "#2171b5", "#4292c6", "#6baed6", "#9ecae1"];
        const ZERO_COLOR = "#f0f0f0"; // Color muy pálido para el 0

        // --- 3. LÓGICA DE SIMBOLOGÍA ---
        // Usamos quintiles SOLO si tenemos suficientes datos MAYORES A CERO (al menos 5)
        if (nonZeroValues.length >= 5) {
            // Calculamos breaks basándonos SOLO en los que tienen divorcios/datos
            const breaks = [
                nonZeroValues[0], // Mínimo no-cero
                nonZeroValues[Math.floor(nonZeroValues.length * 0.2)],
                nonZeroValues[Math.floor(nonZeroValues.length * 0.4)],
                nonZeroValues[Math.floor(nonZeroValues.length * 0.6)],
                nonZeroValues[Math.floor(nonZeroValues.length * 0.8)],
            ];

            // Función de color ajustada
            const getColor = (rawVal, cvegeo) => {
                if (cvegeo == PUEBLA_CVEGEO) return PUEBLA_COLOR;

                const val = parseSeguro(rawVal);
                if (val === null) return "#ccc"; // Sin datos (Null)
                if (val === 0) return ZERO_COLOR; // Cero explícito

                // Gradiente para los que tienen datos
                if (val >= breaks[4]) return colors[0];
                if (val >= breaks[3]) return colors[1];
                if (val >= breaks[2]) return colors[2];
                if (val >= breaks[1]) return colors[3];
                return colors[4];
            };

            styleFunction = (feature) => ({
                fillColor: getColor(
                    mapData[feature.properties.cvegeo],
                    feature.properties.cvegeo,
                ),
                weight: 1,
                opacity: 1,
                color: "#666", // Borde gris tenue
                fillOpacity: 0.8,
            });

            // --- LEYENDA (Muestra rangos reales + categoría 0) ---
            let legendHTML = `<h5>Leyenda</h5>`;

            if (pueblaValue !== null) {
                legendHTML += `<div><i class="legend-swatch" style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                    pueblaValue,
                )})</div><hr class='my-1'>`;
            }
            legendHTML += `<div class="text-muted small mb-1">Resto de municipios:</div>`;

            // Construimos los rangos de la leyenda
            const ranges = [
                { label: `${formatNumber(breaks[4])} o más`, color: colors[0] },
                {
                    label: `${formatNumber(breaks[3])} - ${formatNumber(breaks[4])}`,
                    color: colors[1],
                },
                {
                    label: `${formatNumber(breaks[2])} - ${formatNumber(breaks[3])}`,
                    color: colors[2],
                },
                {
                    label: `${formatNumber(breaks[1])} - ${formatNumber(breaks[2])}`,
                    color: colors[3],
                },
                {
                    label: `${formatNumber(breaks[0])} - ${formatNumber(breaks[1])}`,
                    color: colors[4],
                }, // Rango más bajo NO cero
            ];

            // Filtramos etiquetas repetidas
            const uniqueLabels = new Set();
            ranges.forEach((range) => {
                if (!uniqueLabels.has(range.label)) {
                    legendHTML += `<div><i class="legend-swatch" style="background:${range.color};"></i> ${range.label}</div>`;
                    uniqueLabels.add(range.label);
                }
            });

            // Agregamos explícitamente el 0
            legendHTML += `<div><i class="legend-swatch" style="background:${ZERO_COLOR}; border: 1px solid #ccc;"></i> 0</div>`;
            legendHTML += `<div><i class="legend-swatch" style="background:#ccc"></i> Sin datos</div>`;

            mapLegend.innerHTML = legendHTML;
        } else {
            // CASO B: Muy pocos datos positivos (o puros ceros)
            const DATA_COLOR = "#4292c6";

            styleFunction = (feature) => {
                const cvegeo = feature.properties.cvegeo;
                const val = parseSeguro(mapData[cvegeo]);

                if (cvegeo == PUEBLA_CVEGEO && pueblaValue !== null)
                    return {
                        fillColor: PUEBLA_COLOR,
                        weight: 1,
                        color: "white",
                        fillOpacity: 0.8,
                    };

                if (val > 0)
                    return {
                        fillColor: DATA_COLOR,
                        weight: 1,
                        color: "white",
                        fillOpacity: 0.8,
                    };
                if (val === 0)
                    return {
                        fillColor: ZERO_COLOR,
                        weight: 1,
                        color: "#999",
                        fillOpacity: 0.8,
                    };

                return {
                    fillColor: "#ccc",
                    weight: 1,
                    color: "white",
                    fillOpacity: 0.8,
                };
            };

            mapLegend.innerHTML = `
            <h5>Leyenda</h5>
            ${
                pueblaValue !== null
                    ? `<div><i class="legend-swatch" style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                          pueblaValue,
                      )})</div>`
                    : ""
            }
            ${
                nonZeroValues.length > 0
                    ? `<div><i class="legend-swatch" style="background:${DATA_COLOR}"></i> Con registros (> 0)</div>`
                    : ""
            }
            <div><i class="legend-swatch" style="background:${ZERO_COLOR}; border: 1px solid #ccc;"></i> 0</div>
            <div><i class="legend-swatch" style="background:#ccc"></i> Sin datos</div>
        `;
        }

        // Dibujamos el mapa
        geojsonLayerMunicipal = L.geoJson(pueblaGeoJSON, {
            style: styleFunction,
            onEachFeature: (feature, layer) => {
                const nombre = feature.properties.nomgeo;
                const rawVal = mapData[feature.properties.cvegeo];
                const val = parseSeguro(rawVal);
                const textoValor =
                    val !== null ? formatNumber(val) : "Sin datos";
                layer.bindPopup(
                    `<strong>${nombre}</strong><br>Valor: ${textoValor}`,
                );
            },
        }).addTo(mapMunicipal);

        mapLegend.style.display = "block";
    }
    /**
     * Resalta un polígono (municipio o región) y hace zoom en él.
     * @param {object} geojsonData El archivo GeoJSON completo (ej. pueblaGeoJSON, microGeoJSON)
     * @param {string} idToFind El ID/Clave del polígono que queremos encontrar.
     * @param {string} propertyKey El nombre de la propiedad donde buscar el ID (ej. "cvegeo", "id_micro")
     */
    function displaySingleFeatureMap(geojsonData, idToFind, propertyKey) {
        if (!geojsonData || !mapRegional) return;
        if (geojsonLayerRegional) mapRegional.removeLayer(geojsonLayerRegional);

        geojsonLayerRegional = L.geoJson(geojsonData, {
            style: function (feature) {
                const idActual = feature.properties[propertyKey]?.toString();
                const esBloqueado = IDS_BLOQUEADOS.includes(idActual);

                if (esBloqueado) {
                    return {
                        fillColor: "#95a5a6",
                        weight: 1.5,
                        color: "#7f8c8d",
                        fillOpacity: 0.4,
                        dashArray: "4, 4",
                    };
                }
                if (feature.properties[propertyKey] == idToFind) {
                    return {
                        fillColor: "#246257",
                        weight: 2,
                        color: "#333",
                        fillOpacity: 0.7,
                    };
                }
                return {
                    fillColor: "#ccc",
                    weight: 1,
                    color: "white",
                    fillOpacity: 0.5,
                };
            },
            onEachFeature: function (feature, layer) {
                const idActual = feature.properties[propertyKey]?.toString();
                const esBloqueado = IDS_BLOQUEADOS.includes(idActual);

                if (esBloqueado) {
                    // Evento Click
                    layer.on("click", function (e) {
                        L.DomEvent.stopPropagation(e);
                        Swal.fire({
                            icon: "info",
                            title: "Zona Especial",
                            text: MENSAJE_REGIONALIZACION, // <--- NOMBRE CORREGIDO
                        });
                    });

                    // Evento Mouseover (Forma segura de cambiar el cursor)
                    layer.on("mouseover", function () {
                        const el = layer.getElement();
                        if (el) el.style.cursor = "not-allowed";
                    });
                } else if (feature.properties[propertyKey] == idToFind) {
                    mapRegional.fitBounds(layer.getBounds());
                }

                layer.bindPopup(
                    `<strong>${feature.properties.nombre || feature.properties.nomgeo}</strong>`,
                );
            },
        }).addTo(mapRegional);
    }

    /**
     * Muestra el mapa centrado y resaltado en un solo municipio.
     * @param {string} municipioId El ID (cvegeo) del municipio a resaltar.
     */
    function displaySingleMunicipalityMap(municipioId) {
        if (!pueblaGeoJSON || !mapMunicipal) return;
        if (geojsonLayerMunicipal)
            mapMunicipal.removeLayer(geojsonLayerMunicipal);

        mapLegend.innerHTML = "";

        geojsonLayerMunicipal = L.geoJSON(pueblaGeoJSON, {
            style: function (feature) {
                if (feature.properties.cvegeo == municipioId) {
                    return {
                        fillColor: "#0c312d",
                        weight: 2,
                        color: "#333",
                        fillOpacity: 0.7,
                    };
                }
                return {
                    fillColor: "#ccc",
                    weight: 1,
                    color: "white",
                    fillOpacity: 0.5,
                };
            },
            onEachFeature: function (feature, layer) {
                if (feature.properties.cvegeo == municipioId) {
                    mapMunicipal.fitBounds(layer.getBounds());
                }
                layer.bindPopup(
                    `<strong>${feature.properties.nomgeo}</strong>`,
                );
            },
        }).addTo(mapMunicipal);
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
                        "La respuesta del servidor no fue exitosa.",
                    );
                return response.blob();
                // Convertimos la respuesta en un objeto de archivo
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
    function actualizarTextosUI(datosParaGrafico, titleElement) {
        titleElement.innerText = datosParaGrafico.titulo;
        const isMunicipal = appState.nivelDeAgregacion === "municipio";
        const activeExportBtn = isMunicipal ? exportBtn : exportBtnRegions;
        exportBtn.style.display = "none";
        exportBtnRegions.style.display = "none";

        // --- MODAL DE MUNICIPIOS ---
        const verMunicipiosBtn = document.getElementById("ver-municipios-btn");
        const modalTitle = document.getElementById("municipios-modal-title");
        const modalBody = document.getElementById("municipios-modal-body");

        if (
            !isMunicipal &&
            datosParaGrafico.municipios_incluidos &&
            datosParaGrafico.municipios_incluidos.length > 0
        ) {
            if (verMunicipiosBtn) verMunicipiosBtn.style.display = "block";
            let regionNombre = "la región";
            const tituloPartido = datosParaGrafico.titulo.split(" - ");
            if (tituloPartido.length > 1) {
                regionNombre = tituloPartido[1].split(" (")[0];
            }
            if (modalTitle)
                modalTitle.innerText = `Municipios en ${regionNombre}`;
            let listaHtml = '<ul class="list-group list-group-flush">';
            datosParaGrafico.municipios_incluidos.forEach((mun) => {
                listaHtml += `<li class="list-group-item">${mun}</li>`;
            });
            listaHtml += "</ul>";
            if (modalBody) modalBody.innerHTML = listaHtml;
        } else {
            if (verMunicipiosBtn) verMunicipiosBtn.style.display = "none";
        }

        // --- METADATOS Y SELECTOR DE AÑOS ---
        const years = datosParaGrafico.available_years;
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
        const currentYearEl = isMunicipal
            ? yearSelectorEl
            : yearSelectorElRegions;
        const currentAvailableYearsElement = isMunicipal
            ? availableYearsElement
            : availableYearsElementRegions;

        if (currentMetadata) currentMetadata.style.display = "block";
        if (currentDescription)
            currentDescription.innerText =
                datosParaGrafico.descripcion || "No disponible.";
        if (currentSource)
            currentSource.innerText =
                datosParaGrafico.fuente || "No disponible.";
        if (currentMethod)
            currentMethod.innerText =
                datosParaGrafico.metodo_calculo || "No disponible.";

        if (datosParaGrafico.series && datosParaGrafico.series.length > 0) {
            if (fullscreenBtn) fullscreenBtn.style.display = "block";
            if (fullscreenBtnRegions)
                fullscreenBtnRegions.style.display = "block";
        } else {
            if (fullscreenBtn) fullscreenBtn.style.display = "none";
            if (fullscreenBtnRegions)
                fullscreenBtnRegions.style.display = "none";
        }

        if (Array.isArray(years) && years.length > 0) {
            if (currentAvailableYearsElement)
                currentAvailableYearsElement.innerText = years
                    .sort()
                    .join(", ");
            if (currentYearContainer)
                currentYearContainer.style.display = "block";
            if (activeExportBtn) activeExportBtn.style.display = "block";
            if (currentYearEl) {
                currentYearEl.clearOptions();
                years.forEach((year) =>
                    currentYearEl.addOption({ value: year, text: year }),
                );
                if (datosParaGrafico.selected_years) {
                    currentYearEl.setValue(
                        datosParaGrafico.selected_years,
                        true,
                    );
                }
            }
        } else {
            if (currentAvailableYearsElement)
                currentAvailableYearsElement.innerText = "No disponible";
            if (currentYearContainer)
                currentYearContainer.style.display = "none";
            if (activeExportBtn) activeExportBtn.style.display = "none";
        }

        // --- NOTAS EXPLICATIVAS ---
        let htmlNotas = "";
        if (
            datosParaGrafico.notas_explicativas &&
            Object.keys(datosParaGrafico.notas_explicativas).length > 0
        ) {
            htmlNotas += `<div class="alert alert-warning border-0 bg-opacity-10 mt-3 py-2 px-3 small" style="background-color: #fff3cd;"><strong class="d-block mb-1 text-warning-emphasis"><i class="fas fa-info-circle me-1"></i> Información sobre datos no disponibles:</strong><ul class="mb-0 ps-3">`;
            const aniosConNotas = Object.keys(
                datosParaGrafico.notas_explicativas,
            ).sort();
            aniosConNotas.forEach((anio) => {
                htmlNotas += `<li><strong>${anio}:</strong> ${datosParaGrafico.notas_explicativas[anio]}</li>`;
            });
            htmlNotas += `</ul></div>`;
        }
        if (datosParaGrafico.nota_explicativa) {
            htmlNotas += `<p class="mb-0 mt-2 text-muted small border-top pt-2"><i class="fas fa-comment-dots me-1"></i> ${datosParaGrafico.nota_explicativa}</p>`;
        }

        if (currentNote) {
            currentNote.innerHTML = htmlNotas;
            currentNote.style.display = htmlNotas ? "block" : "none";
        }
    }
    function generarOpcionesEcharts(datosParaGrafico) {
        let options = {};
        const formatNum = (val) => new Intl.NumberFormat("es-MX").format(val);
        const esAbsoluto = appState.indicatorTipoDato && appState.indicatorTipoDato.toLowerCase() === "absoluto";
        const pctFeature = esAbsoluto ? {
            myPercentage: {
                show: true,
                title: appState.showAsPercentage ? "Ver valores absolutos" : "Ver como porcentaje",
                icon: "path://M7.5,4C5.6,4,4,5.6,4,7.5S5.6,11,7.5,11S11,9.4,11,7.5S9.4,4,7.5,4z M16.5,13c-1.9,0-3.5,1.6-3.5,3.5s1.6,3.5,3.5,3.5s3.5-1.6,3.5-3.5S18.4,13,16.5,13z M5.4,18.6L18.6,5.4l1.4,1.4L6.8,20L5.4,18.6z",
                onclick: function () {
                    appState.showAsPercentage = !appState.showAsPercentage;
                    const viewKey = getChartViewKey();
                    const rawData = rawDataByView[viewKey];
                    if (rawData) {
                        const container = document.getElementById(
                            appState.nivelDeAgregacion === "municipio" ? "chart-container" : "chart-container-regions"
                        );
                        const titleEl = document.getElementById(
                            appState.nivelDeAgregacion === "municipio" ? "chart-title" : "chart-title-regions"
                        );
                        renderizarGrafico(rawData, container, titleEl, viewKey);
                    }
                }
            }
        } : {};

        if (datosParaGrafico.tipo_grafico === "piramide") {
            const esPct = datosParaGrafico._esPorcentaje;
            options = {
                color: ["#008FFB", "#FF4560"],
                toolbox: {
                    show: true,
                    feature: {
                        saveAsImage: { title: "Descargar" },
                        dataView: { title: "Ver datos", readOnly: true },
                        restore: { title: "Restaurar" },
                        ...pctFeature
                    },
                },
                tooltip: {
                    trigger: "axis",
                    axisPointer: { type: "shadow" },
                    formatter: function (params) {
                        let html = `<strong>${params[0].axisValueLabel}</strong><br/>`;
                        params.forEach(
                            (p) =>
                                (html += `${p.marker} ${p.seriesName}: ${formatNum(Math.abs(p.value))}${esPct ? '%' : ' personas'}<br/>`),
                        );
                        return html;
                    },
                },
                legend: { top: "bottom" },
                grid: {
                    left: "3%",
                    right: "4%",
                    bottom: "10%",
                    containLabel: true,
                },
                xAxis: {
                    type: "value",
                    name: esPct ? "Porcentaje de la Población (%)" : "Número de Habitantes",
                    nameLocation: "middle",
                    nameGap: 30,
                    axisLabel: { formatter: (v) => formatNum(Math.abs(v)) + (esPct ? '%' : '') },
                },
                yAxis: {
                    type: "category",
                    name: "Grupos de Edad",
                    data: datosParaGrafico.eje_x.categorias,
                    inverse: true,
                },
                series: datosParaGrafico.series.map((s) => ({
                    name: s.name,
                    type: "bar",
                    stack: "Total",
                    data: s.data,
                })),
            };
        } else {
            let xAxisData = [];
            let seriesData = [];

            if (
                datosParaGrafico.tipo_grafico === "line" &&
                (!datosParaGrafico.eje_x.categorias ||
                    datosParaGrafico.eje_x.categorias.length === 0)
            ) {
                const allYears = datosParaGrafico.series
                    .flatMap((s) => s.data.map((p) => p[0]))
                    .filter((y) => y != null);
                xAxisData = [...new Set(allYears)].sort((a, b) => a - b);
                seriesData = datosParaGrafico.series.map((serie) => {
                    const dataMap = new Map(serie.data);
                    return {
                        name: serie.name,
                        type: "line",
                        data: xAxisData.map((y) => dataMap.get(y) ?? null),
                        symbol: "circle",
                        symbolSize: 6,
                        connectNulls: false,
                        smooth: true,
                    };
                });
            } else {
                xAxisData = datosParaGrafico.eje_x.categorias || [];
                const tipoSeries =
                    datosParaGrafico.tipo_grafico === "bar" ? "bar" : "line";
                seriesData = datosParaGrafico.series.map((serie) => ({
                    name: serie.name,
                    type: tipoSeries,
                    data: serie.data,
                    symbol: "circle",
                    symbolSize: 6,
                    smooth: true,
                }));
            }

            options = {
                color: PALETA_COLORES,
                toolbox: {
                    show: true,
                    right: "2%",
                    top: 0,
                    feature: {
                        magicType: {
                            type: ["bar", "line"],
                            title: {
                                bar: "Cambiar a Barras",
                                line: "Cambiar a Líneas",
                            },
                        },
                        dataZoom: {
                            title: {
                                zoom: "Área de Zoom",
                                back: "Restaurar Zoom",
                            },
                        },
                        saveAsImage: {
                            title: "Descargar Imagen",
                            pixelRatio: 2,
                        },
                        dataView: { title: "Ver Datos", readOnly: true },
                        restore: { title: "Restaurar" },
                        ...pctFeature
                    },
                },
                tooltip: {
                    trigger: "item",
                    formatter: function (params) {
                        if (params.value == null || isNaN(params.value))
                            return "";

                        const prefijoX = datosParaGrafico.eje_x.titulo
                            ? `${datosParaGrafico.eje_x.titulo}: `
                            : "";

                        let html = `<div style="font-weight:bold; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px;">${prefijoX}${params.name}</div>`;

                        const sufijo = datosParaGrafico._esPorcentaje ? '%' : '';
                        html += `<div style="display: flex; justify-content: space-between; gap: 20px; font-size: 13px;">
                                    <span>${params.marker} ${params.seriesName}</span>
                                    <span style="font-weight: bold;">${formatNum(params.value)}${sufijo}</span>
                                 </div>`;

                        return html;
                    },
                },
                // --- LEYENDA: Vertical para complejos, horizontal para simples ---
                legend: appState.indicatorEsComplejo
                    ? {
                          show: seriesData.length > 1,
                          type: "scroll",
                          orient: "vertical",
                          right: 0,
                          top: 30,
                          bottom: 20,
                          tooltip: { show: true },
                          textStyle: {
                              width: 180,
                              overflow: "break",
                              lineHeight: 14,
                              fontSize: 11,
                          },
                          pageIconSize: 12,
                          pageTextStyle: { fontSize: 10 },
                          selector: [
                              { type: "all", title: "Todos" },
                              { type: "inverse", title: "Invertir" },
                          ],
                          selectorPosition: "start",
                          selectorLabel: {
                              color: "#246257",
                              fontWeight: "bold",
                              borderColor: "#246257",
                              borderWidth: 1,
                              padding: [3, 5],
                              borderRadius: 3,
                          },
                      }
                    : {
                          show: seriesData.length > 1,
                          type: "scroll",
                          orient: "horizontal",
                          bottom: 0,
                          tooltip: { show: true },
                          textStyle: {
                              width: 250,
                              overflow: "break",
                              lineHeight: 14,
                              fontSize: 11
                          },
                          selector: [
                              { type: "all", title: "Todos" },
                              { type: "inverse", title: "Invertir" },
                          ],
                          selectorPosition: "start",
                          selectorLabel: {
                              color: "#246257",
                              fontWeight: "bold",
                              borderColor: "#246257",
                              borderWidth: 1,
                              padding: [3, 5],
                              borderRadius: 3,
                          },
                      },

                // 3. GRID: Más espacio a la derecha para leyenda vertical en complejos
                grid: appState.indicatorEsComplejo
                    ? {
                          left: "3%",
                          right: "22%",
                          bottom: "15%",
                          top: "10%",
                          containLabel: true,
                      }
                    : {
                          left: "3%",
                          right: "4%",
                          bottom: "22%",
                          top: "15%",
                          containLabel: true,
                      },
                xAxis: {
                    type: "category",
                    data: xAxisData,
                    name: datosParaGrafico.eje_x.titulo || "",
                    nameLocation: "middle",
                    nameGap:
                        xAxisData.length > 10
                            ? 80
                            : xAxisData.length > 4
                              ? 65
                              : 40,
                    axisLabel: {
                        rotate:
                            xAxisData.length > 10
                                ? 45
                                : xAxisData.length > 4
                                  ? 30
                                  : 0,
                        overflow: "break",
                        width: 140,
                        fontSize: xAxisData.length > 15 ? 10 : 11,
                        interval: 0,
                        formatter: (v) =>
                            v === "N/A" || v === null || v === ""
                                ? "Total Estatal"
                                : v,
                    },
                    axisPointer: { show: true, type: "shadow" },
                    triggerEvent: true,
                },
                yAxis: {
                    type: "value",
                    name: datosParaGrafico._esPorcentaje ? "Porcentaje (%)" : (datosParaGrafico.eje_y?.titulo || ""),
                    nameTextStyle: { align: "left", padding: [0, 0, 0, -30] },
                    axisLabel: { formatter: (v) => formatNum(v) + (datosParaGrafico._esPorcentaje ? '%' : '') },
                },
                series: seriesData.map((s, idx) => ({
                    ...s,
                    label: {
                        show:
                            (s.type === "bar" || s.type === "line") &&
                            seriesData.length <= 3 &&
                            xAxisData.length <= 15,
                        position: "top",
                        formatter: (params) => formatNum(params.value),
                        fontSize: 10,
                        color: "#444",
                    },
                    itemStyle: {
                        color:
                            seriesData.length === 1 && s.type === "bar"
                                ? function (params) {
                                      return PALETA_COLORES[
                                          params.dataIndex %
                                              PALETA_COLORES.length
                                      ];
                                  }
                                : PALETA_COLORES[idx % PALETA_COLORES.length],
                        borderRadius: [4, 4, 0, 0],
                    },
                    emphasis: {
                        itemStyle: {
                            filter: "brightness(1.1)",
                        },
                    },
                })),
            };
        }
        return options;
    }

    function getChartViewKey(nivel = appState.nivelDeAgregacion) {
        return nivel === "municipio" ? "municipio" : "regional";
    }

    function getChartInstance(viewKey) {
        return chartInstances[viewKey];
    }

    function setChartInstance(viewKey, instance) {
        chartInstances[viewKey] = instance;
    }

    function disposeChartInstance(viewKey) {
        const instance = chartInstances[viewKey];
        if (instance) {
            if (instance.dispose) instance.dispose();
            else if (instance.destroy) instance.destroy();
            chartInstances[viewKey] = null;
        }
        lastChartOptionsByView[viewKey] = null;
    }

    function resizeAllCharts() {
        Object.values(chartInstances).forEach((instance) => {
            if (instance && instance.resize) {
                instance.resize();
            }
        });
    }

    function desplazarASeccionGrafica(container) {
        if (
            !container ||
            !window.matchMedia("(max-width: 767.98px)").matches
        ) {
            return;
        }

        const target = container.closest(".viz-wrapper") || container;
        requestAnimationFrame(() => {
            const mainNav = document.getElementById("main-nav");
            const offset = (mainNav?.getBoundingClientRect().height || 0) + 12;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            const behavior = window.matchMedia("(prefers-reduced-motion: reduce)").matches
                ? "auto"
                : "smooth";

            window.scrollTo({ top: Math.max(0, top), behavior });
        });
    }

    function renderizarGrafico(
        datosParaGrafico,
        container,
        titleElement,
        viewKey,
    ) {
        // 1. Delegamos el trabajo de los textos y modales a su propia función
        actualizarTextosUI(datosParaGrafico, titleElement);

        rawDataByView[viewKey] = datosParaGrafico;

        // 2. Si está activo el modo porcentaje, transformar los datos
        let datosRender = datosParaGrafico;
        if (appState.showAsPercentage) {
            datosRender = transformarAPorcentaje(datosParaGrafico);
        }

        // 3. Delegamos el cálculo matemático y visual a su propia función
        const options = generarOpcionesEcharts(datosRender);
        lastChartOptionsByView[viewKey] = options;

        // 4. Pintamos el lienzo
        container.style.width = "100%";
        container.style.height = "500px";

        let chart = getChartInstance(viewKey);
        if (!chart || chart.getDom() !== container) {
            disposeChartInstance(viewKey);
            chart = echarts.init(container);
            setChartInstance(viewKey, chart);

            // Resetear modo porcentaje al usar "Restaurar" en la toolbox
            chart.on("restore", () => {
                appState.showAsPercentage = false;
            });
        }

        chart.setOption(options, true);
        chart.hideLoading();
    }

    // --- 5. FUNCIÓN "DIRECTORA" PRINCIPAL ---

    function updateDashboard() {
        if (appState.isLoading || !appState.indicatorId) {
            return;
        }
        appState.isLoading = true;

        setUIInteractivity(true);
        // Reseteo de la interfaz
        const esMunicipio = appState.nivelDeAgregacion === "municipio";
        const chartViewKey = getChartViewKey();
        const activeChartContainer = esMunicipio
            ? chartContainer
            : chartContainerRegions;
        const activeChartTitle = esMunicipio ? chartTitle : chartTitleRegions;
        activeChartTitle.innerText = "Cargando...";

        // 1. Mantenemos el contenedor visible para que ECharts pueda trabajar
        activeChartContainer.style.display = "block";

        // 2. Si la gráfica aún no existe en este contenedor, la inicializamos vacía
        let chart = getChartInstance(chartViewKey);
        if (!chart || chart.getDom() !== activeChartContainer) {
            disposeChartInstance(chartViewKey);
            chart = echarts.init(activeChartContainer);
            setChartInstance(chartViewKey, chart);
        }

        // 3. Encendemos el Loading nativo de ECharts
        chart.showLoading({
            text: "Cargando datos...",
            color: "#246257",
            textColor: "#333",
            maskColor: "rgba(255, 255, 255, 0.8)",
            spinnerRadius: 20,
            lineWidth: 4,
        });

        // Lógica de decisión
        const indicadorSeleccionado = document.querySelector(
            `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
        );
        const esAbsoluto =
            indicadorSeleccionado &&
            indicadorSeleccionado.dataset.tipoDato.toLowerCase() === "absoluto";
        const esEstatal =
            esMunicipio &&
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] === "estatal";
        const esUnMunicipio =
            esMunicipio &&
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] !== "estatal";
        const esUnaMicrorregion =
            appState.nivelDeAgregacion === "microrregion" &&
            appState.microrregionId;
        const esUnaMacrorregion =
            appState.nivelDeAgregacion === "macrorregion" &&
            appState.macrorregionId;

        if (compareStateSwitch) {
            // Buscamos el contenedor padre para ocultarlo/mostrarlo
            const switchContainer =
                compareStateSwitch.closest(".form-check") ||
                compareStateSwitch.parentElement;

            if (switchContainer) {
                if (esEstatal) {
                    // Si es estatal, ocultamos el switch
                    switchContainer.style.display = "none";
                    compareStateSwitch.checked = false;
                } else if (esUnMunicipio) {
                    // Si es un municipio normal, lo mostramos
                    switchContainer.style.display = "block";
                } else {
                    // En otros casos (regiones, etc.), ocultar por defecto
                    switchContainer.style.display = "none";
                    compareStateSwitch.checked = false;
                }
            }
        }
        let showMap = false;
        // if ((esEstatal && esAbsoluto) || esUnMunicipio) {
        //     showMap = true;
        // }
        const esCasoChoropleth =
            esEstatal && esAbsoluto && !appState.indicatorEsComplejo;
        // --- 1. Obtenemos AMBOS contenedores ---
        const mapContainerMunicipal = document.getElementById("map-container");
        const mapContainerRegional = document.getElementById(
            "map-container-regions",
        );

        limpiarCapasMapa();

        // --- 2. Ocultamos AMBOS al inicio ---
        if (mapContainerMunicipal) mapContainerMunicipal.style.display = "none";
        if (mapContainerRegional) mapContainerRegional.style.display = "none";
        // El mapa se muestra si es un solo municipio O si es el caso del coropletas
        if (
            mapVisiblePreference &&
            (esUnMunicipio ||
                esCasoChoropleth ||
                esUnaMicrorregion ||
                esUnaMacrorregion)
        ) {
            showMap = true;
        }

        // Mostrar u ocultar los botones del mapa según si la vista lo soporta
        if (toggleMapBtn) {
            toggleMapBtn.style.display =
                esUnMunicipio || esCasoChoropleth ? "inline-block" : "none";
            if (esUnMunicipio || esCasoChoropleth) {
                toggleMapBtn.classList.toggle("btn-primary", mapVisiblePreference);
                toggleMapBtn.classList.toggle("btn-outline-primary", !mapVisiblePreference);
            } else {
                toggleMapBtn.classList.replace(
                    "btn-primary",
                    "btn-outline-primary",
                );
            }
        }
        if (toggleMapBtnRegions) {
            toggleMapBtnRegions.style.display =
                esUnaMicrorregion || esUnaMacrorregion
                    ? "inline-block"
                    : "none";
            if (esUnaMicrorregion || esUnaMacrorregion) {
                toggleMapBtnRegions.classList.toggle("btn-primary", mapVisiblePreference);
                toggleMapBtnRegions.classList.toggle("btn-outline-primary", !mapVisiblePreference);
            } else {
                toggleMapBtnRegions.classList.replace(
                    "btn-primary",
                    "btn-outline-primary",
                );
            }
        }

        if (showMap) {
            if (esUnMunicipio) {
                mapContainerMunicipal.style.display = "block";
                if (mapMunicipal) mapMunicipal.invalidateSize();

                // (Tu lógica para obtener cvegeo...)
                const municipioId = appState.municipioIds[0];
                const optionData = municipioSelector.options[municipioId];
                const cvegeo = optionData ? optionData.cvegeo : null;
                if (cvegeo) {
                    displaySingleMunicipalityMap(cvegeo);
                }
            } else if (esUnaMicrorregion) {
                mapContainerRegional.style.display = "block";
                if (mapRegional) mapRegional.invalidateSize();
                displaySingleFeatureMap(
                    microGeoJSON,
                    appState.microrregionId,
                    "id_micro",
                );
            } else if (esUnaMacrorregion) {
                mapContainerRegional.style.display = "block";
                if (mapRegional) mapRegional.invalidateSize();
                displaySingleFeatureMap(
                    macroGeoJSON,
                    appState.macrorregionId,
                    "id_macro",
                );
            } else if (esCasoChoropleth) {
                // Para el coropletas, solo mostramos el contenedor.
                // El dibujo se hace en el .then()
                mapContainerMunicipal.style.display = "block";
                if (mapMunicipal) mapMunicipal.invalidateSize();
            }
        }

        activeChartContainer.style.display = "block";

        // Construcción del payload
        let payload = {
            indicador_id: appState.indicatorId,
            anios: appState.selectedYears,
            nivel_de_agregacion: appState.nivelDeAgregacion,
        };
        if (esMunicipio) {
            // payload.municipio_ids = appState.municipioIds;
            let idsParaEnviar = [...appState.municipioIds];

            // Lógica del Switch:
            // Si el switch existe, está activado, Y no tenemos ya 'estatal' seleccionado...
            if (
                compareStateSwitch &&
                compareStateSwitch.checked &&
                !idsParaEnviar.includes("estatal")
            ) {
                // ...agregamos 'estatal' para forzar la comparación.
                idsParaEnviar.push("estatal");
            }

            payload.municipio_ids = idsParaEnviar;
        } else {
            let regionId =
                appState.nivelDeAgregacion === "microrregion"
                    ? appState.microrregionId
                    : appState.macrorregionId;

            // Si es un array, tomamos el primero. Si no, enviamos el valor directo.
            // Esto asegura que el backend reciba un número (ej. 2) y no un array (ej. [2]).
            payload.region_id = Array.isArray(regionId)
                ? regionId[0]
                : regionId;
        }

        // Llamada fetch
        fetch(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response),
            )
            .then((data) => {
                renderizarGrafico(
                    data,
                    activeChartContainer,
                    activeChartTitle,
                    chartViewKey,
                );
                if (esCasoChoropleth && data.mapData) {
                    if (mapMunicipal) {
                        mapMunicipal.setView([19.0414, -98.2063], 6);
                    }
                    displayChoroplethMap(data.mapData);
                }
                desplazarASeccionGrafica(activeChartContainer);
                const nuevaUrl = generarURLdeEstado();
                const nuevoTitulo = data.titulo || "Banco de Indicadores";
                history.pushState(appState, nuevoTitulo, nuevaUrl);
            })
            .catch((error) => {
                console.error("Error en la llamada AJAX:", error);
                activeChartContainer.innerHTML =
                    '<p class="text-danger text-center pt-5">Hubo un error al cargar la información.</p>';
            })
            .finally(() => {
                appState.isLoading = false;
                setUIInteractivity(false);
            });
    }

    // --- 6. CARGA INICIAL Y LISTENERS ---
    let mapsInitializationPromise = null;

    function ensureMapsInitialized() {
        if (!mapsInitializationPromise) {
            mapsInitializationPromise = initMap();
        }
        return mapsInitializationPromise;
    }

    function activarNivelProgramaticamente(nivel) {
        const tabSelector = `#pills-tab-nivel .nav-link[data-nivel="${nivel}"]`;
        const tabToActivate = document.querySelector(tabSelector);

        if (tabToActivate && !tabToActivate.classList.contains("active")) {
            tabToActivate.click();
        }
    }

    function activarIndicadorEnUI(linkActivo) {
        if (!linkActivo) return;

        document
            .querySelectorAll(".indicador-link")
            .forEach((el) => el.classList.remove("fw-bold", "text-primary"));

        linkActivo.classList.add("fw-bold", "text-primary");
        expandirAcordeonHacia(linkActivo);
        gestionarOpcionEstatal(linkActivo.dataset.tipoDato || "Absoluto");
    }

    function restaurarEstadoDesdeURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const indicadorIdFromUrl = urlParams.get("indicador_id");
        const municipioIdsFromUrl = urlParams.get("municipio_ids");
        const regionIdFromUrl = urlParams.get("region_id");
        const nivelFromUrl = urlParams.get("nivel");
        const aniosFromUrl = urlParams.getAll("anios[]");

        let linkActivo = null;

        appState.selectedYears = aniosFromUrl.length > 0 ? aniosFromUrl : [];

        if (indicadorIdFromUrl && nivelFromUrl && regionIdFromUrl) {
            activarNivelProgramaticamente(nivelFromUrl);

            appState.nivelDeAgregacion = nivelFromUrl;
            appState.indicatorId = indicadorIdFromUrl;
            linkActivo = document.querySelector(
                `.indicador-link[data-indicador-id='${indicadorIdFromUrl}']`,
            );
            appState.indicatorEsComplejo = linkActivo
                ? linkActivo.dataset.esComplejo === "true"
                : false;

            if (nivelFromUrl === "microrregion") {
                appState.microrregionId = regionIdFromUrl;
                appState.macrorregionId = null;
                microrregionSelector.setValue(regionIdFromUrl, true);
                macrorregionSelector.clear(true);
            } else if (nivelFromUrl === "macrorregion") {
                appState.macrorregionId = regionIdFromUrl;
                appState.microrregionId = null;
                macrorregionSelector.setValue(regionIdFromUrl, true);
                microrregionSelector.clear(true);
            }
        } else if (indicadorIdFromUrl && municipioIdsFromUrl) {
            activarNivelProgramaticamente("municipio");

            appState.nivelDeAgregacion = "municipio";
            appState.indicatorId = indicadorIdFromUrl;
            appState.municipioIds = municipioIdsFromUrl.split(",");
            municipioSelector.setValue(appState.municipioIds, true);
            linkActivo = document.querySelector(
                `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
            );
            appState.indicatorEsComplejo = linkActivo
                ? linkActivo.dataset.esComplejo === "true"
                : false;
            estatalBtn.classList.toggle(
                "active",
                appState.municipioIds.length === 1 &&
                    appState.municipioIds[0] === "estatal",
            );
        } else {
            activarNivelProgramaticamente("municipio");

            linkActivo = document.querySelector(".indicador-link");
            if (linkActivo) {
                const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
                let idSeleccionInicial = "estatal";

                if (tipoDato.toLowerCase() !== "absoluto") {
                    const primerMunicipio = document.querySelector(
                        "#municipio-selector option:not([value='estatal'])",
                    );
                    if (primerMunicipio) {
                        idSeleccionInicial = primerMunicipio.value;
                    }
                }

                appState.nivelDeAgregacion = "municipio";
                appState.municipioIds = [idSeleccionInicial];
                appState.indicatorId = linkActivo.dataset.indicadorId;
                appState.indicatorEsComplejo =
                    linkActivo.dataset.esComplejo === "true";

                municipioSelector.setValue(appState.municipioIds, true);
                estatalBtn.classList.toggle(
                    "active",
                    idSeleccionInicial === "estatal",
                );
            }
        }

        activarIndicadorEnUI(linkActivo);
        actualizarGuiaVista();
        gestionarBotonResumen();
        checkIfCanConsult();

        if (appState.indicatorId) {
            updateDashboard();
        }
    }

    /**
     * Se ejecuta una sola vez al cargar la página. Inicializa los componentes
     * pesados una vez y luego hidrata el estado desde la URL.
     */
    async function CargaInicial() {
        await ensureMapsInitialized();

        opcionEstatalElement = {
            value: "estatal",
            text: "-- Total Estatal --",
            orden: 0,
        };

        chartContainer.innerHTML = getEmptyStateHtml(true);
        chartContainerRegions.innerHTML = getEmptyStateHtml(false);

        restaurarEstadoDesdeURL();
    }

    window.addEventListener("popstate", () => {
        console.log(
            "Evento popstate detectado. Restaurando estado desde la URL.",
        );
        restaurarEstadoDesdeURL();
    });

    // Aquí van TODOS tus listeners:
    /**
     * Listener para las pestañas principales (Municipio, Micro, Macro).
     * Se dispara cuando una nueva pestaña ha sido mostrada.
     */
    nivelTabs.forEach((tab) => {
        tab.addEventListener("click", function (event) {
            const target = event.currentTarget;
            const nivel = target.dataset.nivel;

            // Si ya estamos en este nivel, no hacemos nada (opcional)
            // if (appState.nivelDeAgregacion === nivel) return;

            // Manejo manual de la activación visual de los botones
            document
                .querySelectorAll(".level-switcher .nav-link")
                .forEach((el) => el.classList.remove("active"));
            target.classList.add("active");

            // Mostrar el pane principal correspondiente manualmente
            const targetPaneId = target.getAttribute("data-bs-target");
            const targetPane = document.querySelector(targetPaneId);
            if (targetPane) {
                document
                    .querySelectorAll("#pills-main-content .tab-pane")
                    .forEach((p) => p.classList.remove("show", "active"));
                targetPane.classList.add("show", "active");
            }
            // console.log(`Cambiando a nivel: ${nivel}`);

            // Sincronizar sidebar manually since Bootstrap only handles one target
            const sidebarTargetId =
                nivel === "municipio"
                    ? "#sidebar-pane-municipios"
                    : "#sidebar-pane-regiones";
            const sidebarPane = document.querySelector(sidebarTargetId);
            if (sidebarPane) {
                document
                    .querySelectorAll("#pills-sidebar-content .tab-pane")
                    .forEach((p) => p.classList.remove("show", "active"));
                sidebarPane.classList.add("show", "active");
            }

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
            // 3. Limpiamos y ocultamos AMBOS selectores de años
            yearSelectorEl.clearOptions();
            yearSelectorEl.clear(); // Limpia la selección visible
            yearSelectorContainer.style.display = "none";

            yearSelectorElRegions.clearOptions();
            yearSelectorElRegions.clear(); // Limpia la selección visible
            yearSelectorContainerRegions.style.display = "none";

            disposeChartInstance("municipio");
            disposeChartInstance("regional");
            // 4. Limpiamos el gráfico y deseleccionamos indicadores
            chartContainer.innerHTML = getEmptyStateHtml(true);
            chartContainerRegions.innerHTML = getEmptyStateHtml(false);
            chartTitle.innerText = "Gráfico";
            chartTitleRegions.innerText = "Gráfico";
            const mapContainerRegional = document.getElementById(
                "map-container-regions",
            );

            // 2. Ocultamos ambos contenedores de mapa
            if (mapContainer) mapContainer.style.display = "none";
            if (mapContainerRegional)
                mapContainerRegional.style.display = "none";

            // 3. Destruimos las capas de dibujo (los polígonos)
            if (geojsonLayerMunicipal && mapMunicipal) {
                mapMunicipal.removeLayer(geojsonLayerMunicipal);
            }
            if (geojsonLayerRegional && mapRegional) {
                mapRegional.removeLayer(geojsonLayerRegional);
            }
            // 5. Deseleccionamos todos los indicadores
            document
                .querySelectorAll(".indicador-link")
                .forEach((el) =>
                    el.classList.remove("fw-bold", "text-primary"),
                );

            if (metadataContainer) metadataContainer.style.display = "none";
            if (metadataContainerRegions)
                metadataContainerRegions.style.display = "none";

            // 6. Deshabilitamos los botones de consulta
            consultarBtn.disabled = true;
            consultarBtnRegions.disabled = true;

            // 7. Mostramos/ocultamos los contenedores de selectores
            microrregionContainer.style.display =
                nivel === "microrregion" ? "block" : "none";
            macrorregionContainer.style.display =
                nivel === "macrorregion" ? "block" : "none";

            // 3. Filtramos el acordeón de la vista regional
            if (nivel !== "municipio") {
                initMapRegional();
                filtrarAcordeonRegional();
                ocultarTematicasVacias();

                if (nivel === "microrregion") {
                    const primerMicro = document.querySelector(
                        "#microrregion-selector option",
                    );
                    if (primerMicro) {
                        const primerMicroId = primerMicro.value;
                        // Actualizamos nuestro estado
                        appState.microrregionId = primerMicroId;
                        // Le decimos a Tom Select que se actualice (en modo silencioso)
                        microrregionSelector.setValue(primerMicroId, true);
                    }
                    appState.macrorregionId = null;
                }
                if (nivel === "macrorregion") {
                    const primerMacro = document.querySelector(
                        "#macrorregion-selector option",
                    );
                    if (primerMacro) {
                        const primerMacroId = primerMacro.value;
                        // Actualizamos nuestro estado
                        appState.macrorregionId = primerMacroId;
                        // Le decimos a Tom Select que se actualice (en modo silencioso)
                        macrorregionSelector.setValue(primerMacroId, true);
                    }
                    appState.microrregionId = null;
                }
                setTimeout(() => {
                    if (mapRegional) mapRegional.invalidateSize();
                }, 10);
            } else {
                // Si volvemos a la pestaña de municipios, limpiamos los estados de región
                appState.microrregionId = null;
                appState.macrorregionId = null;
                const todasLasTematicas = document.querySelectorAll(
                    "#accordionDimensionsRegions .accordion-item",
                );
                todasLasTematicas.forEach((t) => (t.style.display = "block"));
                setTimeout(() => {
                    if (mapMunicipal) mapMunicipal.invalidateSize();
                }, 10);
                const linkActivo = document.querySelector(
                    "#accordionDimensions .indicador-link",
                );
                let tipoDatoDefecto = "Absoluto"; // Asumir Absoluto si algo falla

                if (linkActivo) {
                    tipoDatoDefecto = linkActivo.dataset.tipoDato || "Absoluto";
                }

                // 2. LLAMAMOS A LA FUNCIÓN que gestiona el botón estatal
                // Esto SÓLO muestra u oculta el botón, no selecciona nada.
                gestionarOpcionEstatal(tipoDatoDefecto);

                // 3. (Opcional pero recomendado) Dejamos la selección de TomSelect en 'estatal'
                // si el tipo de dato es absoluto, o vacío si no lo es.
                if (tipoDatoDefecto.toLowerCase() === "absoluto") {
                    appState.municipioIds = ["estatal"];
                    // No usamos setValue, solo activamos el botón visualmente
                    estatalBtn.classList.add("active");
                } else {
                    appState.municipioIds = [];
                    estatalBtn.classList.remove("active");
                }
            }

            actualizarGuiaVista(
                `Cambiaste a ${nivel}. Se reiniciaron los filtros para evitar mezclar consultas de distintos niveles.`,
            );
            actualizarResumenConsulta();
            actualizarFeedbackConsulta();

            if (sidebarPane) {
                // 2. Buscamos el acordeón en el sidebar correspondiente
                const activeAccordion = sidebarPane.querySelector(".accordion");
                // 3. Llamamos a nuestra nueva función para expandirlo.
                resetearYExpandirPrimerAcordeon(activeAccordion);
            }

            // 5. Futuro paso: Resetear el gráfico y la selección de indicador
            // console.log("Estado actualizado:", appState);
            exportBtn.style.display = "none";
            exportBtnRegions.style.display = "none";
        });
    });

    const todosLosIndicadores = document.querySelectorAll(".indicador-link");
    todosLosIndicadores.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const target = e.currentTarget;

            const tipoDatoNuevo = target.dataset.tipoDato || "Absoluto";
            appState.indicatorEsComplejo = target.dataset.esComplejo === "true";
            gestionarOpcionEstatal(tipoDatoNuevo);

            if (
                tipoDatoNuevo.toLowerCase() !== "absoluto" &&
                appState.municipioIds.includes("estatal")
            ) {
                const primerMunicipio = document.querySelector(
                    "#municipio-selector option:not([value='estatal'])",
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
            appState.indicatorId = target.dataset.indicadorId;
            checkIfCanConsult();
            appState.selectedYears = [];

            // 1. Vaciamos el <select> original del HTML
            document.getElementById("year-selector").innerHTML = "";
            document.getElementById("year-selector-regions").innerHTML = "";

            // 2. Le decimos a Tom Select que se sincronice con el <select> ahora vacío
            yearSelectorEl.sync();
            yearSelectorElRegions.sync();

            // 3. Limpiamos cualquier valor que pudiera haber quedado seleccionado en la caja de texto
            yearSelectorEl.clear();
            yearSelectorElRegions.clear();

            // 4. Ocultamos los contenedores
            yearSelectorContainer.style.display = "none";
            yearSelectorContainerRegions.style.display = "none";

            // Actualizamos estilos (sin cambios)
            todosLosIndicadores.forEach((el) =>
                el.classList.remove("fw-bold", "text-primary"),
            );
            document
                .querySelectorAll(
                    `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
                )
                .forEach((activeLink) =>
                    activeLink.classList.add("fw-bold", "text-primary"),
                );

            updateDashboard();
        });
    });

    consultarBtn.addEventListener("click", () => {
        // Simplemente llamamos a la función que dibuja todo
        updateDashboard();
    });

    consultarBtnRegions.addEventListener("click", () => {
        updateDashboard();
        consultarBtnRegions.classList.replace(
            "btn-custom-primary",
            "btn-secondary",
        );
    });

    /**
     * Revisa el estado actual y habilita/deshabilita el botón "Consultar"
     */
    function checkIfCanConsult() {
        let canConsult = false;
        const isMunicipal = appState.nivelDeAgregacion === "municipio";
        const activeConsultarBtn = isMunicipal
            ? consultarBtn
            : consultarBtnRegions;
        actualizarGuiaVista();

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

        // Habilitamos o deshabilitamos el botón
        activeConsultarBtn.disabled = !canConsult;
        actualizarResumenConsulta();
        actualizarFeedbackConsulta();
    }

    fullscreenModal.addEventListener("shown.bs.modal", () => {
        if (fullscreenChart) {
            if (fullscreenChart.dispose) fullscreenChart.dispose();
            else if (fullscreenChart.destroy) fullscreenChart.destroy();
        }

        const activeViewKey = getChartViewKey();
        const lastChartOptions = lastChartOptionsByView[activeViewKey];
        if (!lastChartOptions) {
            return;
        }

        fullscreenModalTitle.innerText =
            lastChartOptions.series.length > 10
                ? "Indicador complejo en Pantalla Completa"
                : "Gráfico en Pantalla Completa";

        // Aseguramos que el contenedor del modal tenga altura dinámica
        fullscreenChartContainer.style.width = "100%";
        fullscreenChartContainer.style.height =
            window.innerHeight * 0.75 + "px";

        // Inicializamos ECharts en el Modal
        fullscreenChart = echarts.init(fullscreenChartContainer);
        fullscreenChart.setOption(lastChartOptions);
    });

    fullscreenModal.addEventListener("hidden.bs.modal", () => {
        if (fullscreenChart) {
            fullscreenChart.dispose();
            fullscreenChart = null;
        }
    });
    // --- PASO 3: RESIZE CON DEBOUNCE ---
    let resizeTimer; // Variable para guardar nuestro temporizador

    window.addEventListener("resize", function () {
        // 1. Si el usuario sigue moviendo la ventana, cancelamos el redibujado pendiente
        clearTimeout(resizeTimer);

        // 2. Programamos un nuevo redibujado para dentro de 250 milisegundos
        resizeTimer = setTimeout(function () {
            // Solo redibujamos si la gráfica existe y la ventana ya se quedó quieta
            resizeAllCharts();
            // Aprovechamos para ajustar también los mapas si están visibles
            if (mapMunicipal) mapMunicipal.invalidateSize(true);
            if (mapRegional) mapRegional.invalidateSize(true);
        }, 250);
    });
    // --- 7. LÓGICA PARA OCULTAR/MOSTRAR EL CATÁLOGO ---

    // 1. Inicializar los tooltips de los nuevos botones
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]'),
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Seleccionar AMBOS botones (el de municipios y el de regiones)
    const toggleBtns = document.querySelectorAll(".toggle-catalogo-btn");

    toggleBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            // 3. Encontrar las columnas "hermanas" dentro del tab activo
            const parentRow = btn.closest(".row");
            const catalogoCol = parentRow.querySelector(".catalogo-col");
            const contentCol = parentRow.querySelector(".content-col");
            const icon = btn.querySelector("i");

            if (!catalogoCol || !contentCol) return;

            // 4. Comprobar el estado actual
            if (catalogoCol.style.display === "none") {
                // --- MOSTRAR CATÁLOGO ---
                catalogoCol.style.display = "block";

                // Devuelve la columna de contenido a su tamaño original
                contentCol.classList.remove("col-lg-12", "col-md-12");
                contentCol.classList.add("col-lg-9", "col-md-9");

                // Cambia el ícono y el tooltip
                icon.classList.remove("fa-chevron-right");
                icon.classList.add("fa-chevron-left");
                btn.setAttribute("title", "Ocultar catálogo");
            } else {
                // --- OCULTAR CATÁLOGO ---
                catalogoCol.style.display = "none";

                // Expande la columna de contenido al ancho completo
                contentCol.classList.remove("col-lg-9", "col-md-9");
                contentCol.classList.add("col-lg-12", "col-md-12");

                // Cambia el ícono y el tooltip
                icon.classList.remove("fa-chevron-left");
                icon.classList.add("fa-chevron-right");
                btn.setAttribute("title", "Mostrar catálogo");
            }

            // 5. Forzar al gráfico y al mapa a redibujarse
            // para que se ajusten al nuevo tamaño del contenedor.
            setTimeout(() => {
                // --- ECHARTS RESIZE NATIVO ---
                resizeAllCharts();

                if (mapMunicipal) {
                    mapMunicipal.invalidateSize(true);
                }
                if (mapRegional) {
                    mapRegional.invalidateSize(true);
                }
            }, 300);
        });
    });
    exportBtn.addEventListener("click", handleExport);
    exportBtnRegions.addEventListener("click", handleExport);

    // --- 8. PREPARAR GRÁFICAS PARA IMPRESIÓN ---
    window.addEventListener("beforeprint", () => {
        // A. Apagar los botones de ECharts
        ["municipio", "regional"].forEach((viewKey) => {
            const chart = chartInstances[viewKey];
            if (chart) chart.setOption({ toolbox: { show: false } });
        });
        // B. Calcular la fecha a incrustar en el documento impreso
        const fechaHoy = new Date().toLocaleDateString("es-MX", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
        if (metadataContainer)
            metadataContainer.setAttribute("data-fecha-impresion", fechaHoy);
        if (metadataContainerRegions)
            metadataContainerRegions.setAttribute(
                "data-fecha-impresion",
                fechaHoy,
            );
        // C. Redimensionar todo al tamaño del papel
        resizeAllCharts();
        if (mapMunicipal) mapMunicipal.invalidateSize(true);
        if (mapRegional) mapRegional.invalidateSize(true);
    });

    // Cuando se cierra el diálogo de impresión (guarde o cancele)
    window.addEventListener("afterprint", () => {
        // C. Volver a encender los botones de ECharts en la web
        ["municipio", "regional"].forEach((viewKey) => {
            const chart = chartInstances[viewKey];
            if (chart) chart.setOption({ toolbox: { show: true } });
        });
        setTimeout(() => {
            resizeAllCharts();
            if (mapMunicipal) mapMunicipal.invalidateSize(true);
            if (mapRegional) mapRegional.invalidateSize(true);
        }, 300);
    });
    // Ejecución inicial
    CargaInicial();
});
