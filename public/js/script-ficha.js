// LOCAL
// --- VARIABLES GLOBALES PARA EL MAPA ---
// Las declaramos aquí para que sean accesibles por todas las funciones.
let mapMunicipal = null;
let mapRegional = null;
let geojsonLayerMunicipal = null;
let geojsonLayerRegional = null;

let pueblaGeoJSON = null;
let microGeoJSON = null;
let macroGeoJSON = null;
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
        sortField: [
            { field: "orden", direction: "asc" },
            { field: "$text", direction: "asc" },
        ],
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
                    text: MSJ_REGIONALIZACION,
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
            if (
                JSON.stringify(selection.sort()) ===
                JSON.stringify(appState.selectedYears.sort())
            ) {
                return;
            }
            appState.selectedYears = selection;
        },
    });

    const yearSelectorElRegions = new TomSelect("#year-selector-regions", {
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
    const exportBtnRegions = document.getElementById("export-btn-regions");

    const microrregionContainer = document.getElementById(
        "microrregion-selector-container",
    );
    const macrorregionContainer = document.getElementById(
        "macrorregion-selector-container",
    );
    const nivelTabs = document.querySelectorAll("#pills-tab-nivel .nav-link");
    const accordionMunicipal = document.getElementById("accordionDimensions");
    const accordionRegionsContainer = document.getElementById(
        "accordionDimensionsRegions",
    );

    const chartContainer = document.getElementById("chart-container");
    const chartTitle = document.getElementById("chart-title");
    const resumenBtn = document.getElementById("resumen-btn");
    const resumenUrlPrototype = resumenBtn ? resumenBtn.href : "";
    const chartContainerRegions = document.getElementById(
        "chart-container-regions",
    );
    const chartTitleRegions = document.getElementById("chart-title-regions");

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
    const fullscreenModal = document.getElementById("chart-fullscreen-modal");
    const fullscreenChartContainer = document.getElementById(
        "fullscreen-chart-container",
    );
    const fullscreenModalTitle = document.getElementById(
        "fullscreen-modal-title",
    );
    const fullscreenBtnRegions = document.getElementById(
        "fullscreen-btn-regions",
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
        setupIndicatorSearch(
            "indicador-search-regions",
            "accordionDimensionsRegions",
        );
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
                municipioId,
            );
        } else {
            resumenBtn.style.display = "none";
            resumenBtn.classList.add("disabled");
        }
    }
    shareBtn.addEventListener("click", handleShareView);
    shareBtnRegions.addEventListener("click", handleShareView);

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
        const dimensionTargetId = linkElemento.dataset.dimensionTarget.replace(
            "#",
            "",
        );
        const tematicaTargetId = linkElemento.dataset.tematicaTarget.replace(
            "#",
            "",
        );

        // Usamos los IDs limpios para buscar en el documento y activar el colapsable
        const dimensionEl =
            document.getElementById(dimensionTargetId) ||
            document.getElementById(
                dimensionTargetId.replace("dimension", "dimension-region"),
            );
        const tematicaEl =
            document.getElementById(tematicaTargetId) ||
            document.getElementById(
                tematicaTargetId.replace("tematica", "tematica-region"),
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
        mapMunicipal = L.map("map").setView([19.0414, -98.2063], 8);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(mapMunicipal);
        // console.log("Mapa Municipal Inicializado.");
    }

    function initMapRegional() {
        if (mapRegional) return;
        // ¡OJO! Usamos el ID del HTML de regiones
        if (!document.getElementById("map-regions")) return;
        mapRegional = L.map("map-regions").setView([19.0414, -98.2063], 8);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(mapRegional);
        // console.log("Mapa Regional Inicializado.");
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
                        `${window.APP_URL}/geojson/municipios_puebla_slim.geojson`,
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/microrregiones_puebla_sin_adecuacion.geojson`,
                    ),
                    fetch(
                        `${window.APP_URL}/geojson/macrorregiones_2025_slim.geojson`,
                    ),
                ]);

            pueblaGeoJSON = await responseMun.json();
            microGeoJSON = await responseMicro.json();
            macroGeoJSON = await responseMacro.json();

            console.log("Todos los archivos GeoJSON han sido cargados.");
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
            ${pueblaValue !== null
                    ? `<div><i class="legend-swatch" style="background:${PUEBLA_COLOR}"></i> Puebla (${formatNumber(
                        pueblaValue,
                    )})</div>`
                    : ""
                }
            ${nonZeroValues.length > 0
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
                    // Evento Clic
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

    /**
     * Renderiza el gráfico (MODIFICADA para aceptar el contenedor)
     */
    // function renderizarGrafico(datosParaGrafico) {
    function renderizarGrafico(datosParaGrafico, container, titleElement) {
        container.innerHTML = "";

        titleElement.innerText = datosParaGrafico.titulo;

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
        const verMunicipiosBtn = document.getElementById("ver-municipios-btn");
        const modalTitle = document.getElementById("municipios-modal-title");
        const modalBody = document.getElementById("municipios-modal-body");
        if (
            !isMunicipal &&
            datosParaGrafico.municipios_incluidos &&
            datosParaGrafico.municipios_incluidos.length > 0
        ) {
            // 3. Mostramos el botón
            verMunicipiosBtn.style.display = "block";

            // 4. Construimos el título del modal (usando el título del gráfico)
            let regionNombre = "la región";
            const tituloPartido = datosParaGrafico.titulo.split(" - ");
            if (tituloPartido.length > 1) {
                // Toma "Mixteca" de "Población - Mixteca (Año: 2020)"
                regionNombre = tituloPartido[1].split(" (")[0];
            }
            modalTitle.innerText = `Municipios en ${regionNombre}`;

            // 5. Construimos el cuerpo del modal (la lista)
            let listaHtml = '<ul class="list-group list-group-flush">';
            datosParaGrafico.municipios_incluidos.forEach((mun) => {
                listaHtml += `<li class="list-group-item">${mun}</li>`;
            });
            listaHtml += "</ul>";
            modalBody.innerHTML = listaHtml;
        } else {
            // 6. Si no es regional o no hay datos, ocultamos el botón
            verMunicipiosBtn.style.display = "none";
        }

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

        // --- VISUALIZACIÓN DE NOTAS EXPLICATIVAS ---
        let htmlNotas = "";

        // 1. Notas de Datos Faltantes (del Catálogo ND)
        if (
            datosParaGrafico.notas_explicativas &&
            Object.keys(datosParaGrafico.notas_explicativas).length > 0
        ) {
            htmlNotas += `
            <div class="alert alert-warning border-0 bg-opacity-10 mt-3 py-2 px-3 small" style="background-color: #fff3cd;">
                <strong class="d-block mb-1 text-warning-emphasis"><i class="fas fa-info-circle me-1"></i> Información sobre datos no disponibles:</strong>
                <ul class="mb-0 ps-3">`;

            // Ordenamos los años
            const aniosConNotas = Object.keys(
                datosParaGrafico.notas_explicativas,
            ).sort();

            aniosConNotas.forEach((anio) => {
                const motivo = datosParaGrafico.notas_explicativas[anio];
                htmlNotas += `<li><strong>${anio}:</strong> ${motivo}</li>`;
            });

            htmlNotas += `</ul></div>`;
        }

        // 2. Nota General del Indicador (Metadatos)
        if (datosParaGrafico.nota_explicativa) {
            htmlNotas += `<p class="mb-0 mt-2 text-muted small border-top pt-2">
                <i class="fas fa-comment-dots me-1"></i> ${datosParaGrafico.nota_explicativa}
            </p>`;
        }

        // 3. Renderizar en el contenedor
        if (htmlNotas) {
            currentNote.innerHTML = htmlNotas;
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
            // --- INICIO DE CORRECCIÓN DEFINITIVA ---

            // CASO 1: Gráfico de LÍNEA
            if (datosParaGrafico.tipo_grafico === "line") {
                // Revisa si el backend mandó datos como [x,y] (sin categorías)
                if (
                    !datosParaGrafico.eje_x.categorias ||
                    datosParaGrafico.eje_x.categorias.length === 0
                ) {
                    // Si SÍ es un indicador complejo (CULTIVOS)
                    if (appState.indicatorEsComplejo) {
                        // --- CASO A: "CULTIVOS" (Complejo) ---
                        // 1. Extraer los años para controlar el eje
                        const allYears = datosParaGrafico.series.flatMap(
                            (serie) => serie.data.map((point) => point[0]),
                        );
                        const validYears = allYears.filter(
                            (year) => year !== null && year !== undefined,
                        );
                        const uniqueYears = [...new Set(validYears)].sort(
                            (a, b) => a - b,
                        );

                        const minYear = Math.min(...uniqueYears);
                        const maxYear = Math.max(...uniqueYears);

                        let tickAmount = uniqueYears.length - 1;
                        if (tickAmount > 10) {
                            tickAmount = 10;
                        }
                        if (tickAmount <= 0) {
                            tickAmount = 1;
                        }
                        // console.log("¡¡¡ESTOY ENTRANDO A CASO A (CULTIVOS)!!!");
                        // 2. Opciones finales
                        options = {
                            series: datosParaGrafico.series,
                            chart: {
                                type: "line",
                                height: 500,
                                animations: { enabled: false },
                                toolbar: { show: true },
                            },
                            colors: PALETA_COLORES,
                            markers: {
                                size: 5,
                            },
                            stroke: { curve: "smooth", width: 2 },
                            yaxis: {
                                title: { text: datosParaGrafico.eje_y.titulo },
                                labels: {
                                    formatter: (value) =>
                                        new Intl.NumberFormat("es-MX").format(
                                            value,
                                        ),
                                },
                            },
                            xaxis: {
                                // <-- TU EJE X LIMPIO (CHECK)
                                type: "numeric",
                                min: minYear,
                                max: maxYear,
                                tickAmount: tickAmount,
                                title: {
                                    text:
                                        datosParaGrafico.eje_x.titulo || "Año",
                                },
                                labels: {
                                    formatter: (value) => parseInt(value, 10),
                                },
                            },
                            tooltip: {
                                shared: false, // (CHECK) Tooltip no compartido
                                intersect: true, // (CHECK) Requiere tocar el marcador

                                // ¡LA LÍNEA MÁGICA!
                                // Oculta la "mira" vertical (xaxis.tooltip) que
                                // estaba causando la ambigüedad.
                                x: {
                                    show: false,
                                },
                                // Formateador para el valor (buena práctica)
                                y: {
                                    formatter: (value) =>
                                        new Intl.NumberFormat("es-MX").format(
                                            value,
                                        ),
                                    title: {
                                        formatter: (seriesName) =>
                                            seriesName + ":",
                                    },
                                },
                            },
                            noData: {
                                text: "No hay datos disponibles para esta selección.",
                            },
                        };
                    } else {
                        // console.log("CASO B");
                        // --- CASO B: "POBLACIÓN" (Simple) ---
                        // Queremos eje de categorías para que se vea limpio.
                        const allYears = datosParaGrafico.series.flatMap(
                            (serie) => serie.data.map((point) => point[0]),
                        );
                        const uniqueYears = [...new Set(allYears)].sort(
                            (a, b) => a - b,
                        );
                        const newSeries = datosParaGrafico.series.map(
                            (serie) => {
                                const dataMap = new Map(serie.data);
                                return {
                                    name: serie.name,
                                    data: uniqueYears.map(
                                        (year) => dataMap.get(year) ?? null,
                                    ),
                                };
                            },
                        );

                        options = {
                            series: newSeries, // Usa las series re-mapeadas
                            chart: {
                                type: "line",
                                height: 500,
                                animations: { enabled: false },
                                toolbar: { show: true },
                            },
                            colors: PALETA_COLORES,
                            markers: {
                                size: 5,
                            },
                            stroke: { curve: "smooth", width: 2 },
                            yaxis: {
                                title: { text: datosParaGrafico.eje_y.titulo },
                                labels: {
                                    formatter: (value) =>
                                        new Intl.NumberFormat("es-MX").format(
                                            value,
                                        ),
                                },
                            },
                            // xaxis: {
                            //     type: "category",
                            //     categories: uniqueYears,
                            //     title: {
                            //         text:
                            //             datosParaGrafico.eje_x.titulo || "Año",
                            //     },
                            // },
                            xaxis: {
                                type: "category",
                                categories: uniqueYears,
                                title: {
                                    text:
                                        datosParaGrafico.eje_x.titulo || "Año",
                                },
                                labels: {
                                    formatter: function (value) {
                                        if (
                                            value === "N/A" ||
                                            value === null ||
                                            value === ""
                                        ) {
                                            return "Total Estatal";
                                        }
                                        return value;
                                    },
                                },
                            },
                            tooltip: {
                                shared: false,
                                intersect: true,
                            },
                            noData: {
                                text: "No hay datos disponibles para esta selección.",
                            },
                        };
                    }
                } else {
                    // --- CASO C: GRÁFICO DE LÍNEA QUE YA TENÍA CATEGORÍAS ---
                    options = {
                        series: datosParaGrafico.series,
                        chart: {
                            type: "line",
                            height: 450,
                            toolbar: { show: true },
                            animations: { enabled: false },
                        },
                        colors: PALETA_COLORES,
                        markers: {
                            size: 5,
                        },
                        xaxis: {
                            type: "category",
                            categories: datosParaGrafico.eje_x.categorias || [],
                        },
                        yaxis: {
                            title: { text: datosParaGrafico.eje_y.titulo },
                            labels: {
                                formatter: (value) =>
                                    new Intl.NumberFormat("es-MX").format(
                                        value,
                                    ),
                            },
                        },
                        tooltip: {
                            shared: false,
                            intersect: true,
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: "smooth", width: 2 },
                        noData: {
                            text: "No hay datos disponibles para esta selección.",
                        },
                    };
                }
            } else {
                // --- CASO D: GRÁFICO DE BARRAS ---
                options = {
                    series: datosParaGrafico.series,
                    chart: {
                        type: "bar",
                        height: 450,
                        toolbar: { show: true },
                        animations: { enabled: false },
                    },
                    colors: PALETA_COLORES,
                    xaxis: {
                        type: "category",
                        categories: datosParaGrafico.eje_x.categorias || [],
                    },
                    yaxis: {
                        title: { text: datosParaGrafico.eje_y.titulo },
                        labels: {
                            formatter: (value) =>
                                new Intl.NumberFormat("es-MX").format(value),
                        },
                    },
                    tooltip: { shared: false, intersect: true },
                    dataLabels: { enabled: false },
                    stroke: { curve: "smooth", width: 2 },
                    noData: {
                        text: "No hay datos disponibles para esta selección.",
                    },
                    grid: {
                        padding: {
                            bottom: 20,
                        },
                    },
                };
            }
            // --- FIN DE CORRECCIÓN ---
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

        setUIInteractivity(true);
        // Reseteo de la interfaz
        const esMunicipio = appState.nivelDeAgregacion === "municipio";
        const activeChartContainer = esMunicipio
            ? chartContainer
            : chartContainerRegions;
        const activeChartTitle = esMunicipio ? chartTitle : chartTitleRegions;

        activeChartTitle.innerText = "Cargando...";
        activeChartContainer.innerHTML =
            '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

        chartContainer.style.display = "none";
        chartContainerRegions.style.display = "none";

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

        // --- 2. Ocultamos AMBOS al inicio ---
        if (mapContainerMunicipal) mapContainerMunicipal.style.display = "none";
        if (mapContainerRegional) mapContainerRegional.style.display = "none";
        // El mapa se muestra si es un solo municipio O si es el caso del coropletas
        if (
            esUnMunicipio ||
            esCasoChoropleth ||
            esUnaMicrorregion ||
            esUnaMacrorregion
        ) {
            showMap = true;
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

            // CORRECCIÓN: Si es un array, tomamos el primero. Si no, enviamos el valor directo.
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
                renderizarGrafico(data, activeChartContainer, activeChartTitle);
                if (esCasoChoropleth && data.mapData) {
                    if (mapMunicipal) {
                        mapMunicipal.setView([19.0414, -98.2063], 7);
                    }
                    displayChoroplethMap(data.mapData);
                }
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

    /**
     * Se ejecuta una sola vez al cargar la página. Espera a que el mapa se inicialice
     * y luego decide el estado inicial de la aplicación (desde URL o por defecto).
     */
    async function CargaInicial() {
        // --- PASO 1: ESPERAMOS a que el mapa se inicialice ---
        await initMap();

        // --- PASO 2: Preparamos la opción 'estatal' ---
        opcionEstatalElement = {
            value: "estatal",
            text: "-- Total Estatal --",
            orden: 0,
        };

        const urlParams = new URLSearchParams(window.location.search);
        const indicadorIdFromUrl = urlParams.get("indicador_id");
        const municipioIdsFromUrl = urlParams.get("municipio_ids");
        const regionIdFromUrl = urlParams.get("region_id");
        const nivelFromUrl = urlParams.get("nivel");
        const aniosFromUrl = urlParams.getAll("anios[]");

        let linkActivo = null;
        let mustCallUpdateDashboard = true;

        if (aniosFromUrl.length > 0) {
            appState.selectedYears = aniosFromUrl;
            // console.log("-> Años de URL detectados:", aniosFromUrl);
        }

        if (indicadorIdFromUrl && nivelFromUrl && regionIdFromUrl) {
            // --- CASO A: URL REGIONAL ---
            // console.log("-> Parámetros de URL regionales detectados.");

            appState.nivelDeAgregacion = nivelFromUrl;
            appState.indicatorId = indicadorIdFromUrl;

            // --- INICIO DE CORRECCIÓN 1 ---
            // Buscamos el link AHORA para saber si es complejo
            const linkRegional = document.querySelector(
                `.indicador-link[data-indicador-id='${indicadorIdFromUrl}']`,
            );
            if (linkRegional) {
                appState.indicatorEsComplejo =
                    linkRegional.dataset.esComplejo === "true";
                // console.log(
                //   "-> Indicador detectado como complejo:",
                //   appState.indicatorEsComplejo
                // );
            }
            // --- FIN DE CORRECCIÓN 1 ---

            const tabSelector = `#pills-tab-nivel .nav-link[data-nivel="${nivelFromUrl}"]`;
            const tabToActivate = document.querySelector(tabSelector);

            if (tabToActivate) {
                mustCallUpdateDashboard = false;
                const tab = new bootstrap.Tab(tabToActivate);

                tabToActivate.addEventListener(
                    "shown.bs.tab",
                    () => {
                        // console.log(
                        //   "Pestaña regional terminó de mostrarse. Ahora sí, cargando datos..."
                        // );

                        // (Restauramos el estado que el listener 'show.bs.tab' borró)
                        appState.indicatorId = indicadorIdFromUrl;
                        if (nivelFromUrl === "microrregion") {
                            appState.microrregionId = regionIdFromUrl;
                        } else if (nivelFromUrl === "macrorregion") {
                            appState.macrorregionId = regionIdFromUrl;
                        }
                        appState.selectedYears = aniosFromUrl;
                        appState.indicatorEsComplejo = linkRegional
                            ? linkRegional.dataset.esComplejo === "true"
                            : false; // <-- Restaurar estado complejo

                        // Activamos el indicador
                        linkActivo = document.querySelector(
                            `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
                        );
                        if (linkActivo) {
                            linkActivo.classList.add("fw-bold", "text-primary");
                            expandirAcordeonHacia(linkActivo);
                            const tipoDato =
                                linkActivo.dataset.tipoDato || "Absoluto";
                            gestionarOpcionEstatal(tipoDato);
                        }
                        updateDashboard();
                        gestionarBotonResumen();
                    },
                    { once: true },
                );

                tab.show();
            }

            if (nivelFromUrl === "microrregion") {
                appState.microrregionId = regionIdFromUrl;
                microrregionSelector.setValue(regionIdFromUrl, true);
            } else if (nivelFromUrl === "macrorregion") {
                appState.macrorregionId = regionIdFromUrl;
                macrorregionSelector.setValue(regionIdFromUrl, true);
            }
        } else if (indicadorIdFromUrl && municipioIdsFromUrl) {
            // --- CASO B: VISTA MUNICIPAL ---
            // console.log("-> Parámetros de URL de municipio detectados.");
            appState.indicatorId = indicadorIdFromUrl;
            appState.municipioIds = municipioIdsFromUrl.split(",");
            municipioSelector.setValue(appState.municipioIds, true);
            linkActivo = document.querySelector(
                `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
            );

            // --- INICIO DE CORRECCIÓN 2 ---
            if (linkActivo) {
                appState.indicatorEsComplejo =
                    linkActivo.dataset.esComplejo === "true";
                // console.log(
                //   "-> Indicador detectado como complejo:",
                //   appState.indicatorEsComplejo
                // );
            }
            // --- FIN DE CORRECCIÓN 2 ---
        } else {
            // --- CASO C: CARGA POR DEFECTO ---
            // console.log("-> Sin parámetros de URL. Usando carga por defecto.");
            linkActivo = document.querySelector(".indicador-link");
            if (linkActivo) {
                const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
                gestionarOpcionEstatal(tipoDato);

                let idSeleccionInicial = "estatal";
                if (tipoDato.toLowerCase() !== "absoluto") {
                    const primerMunicipio = document.querySelector(
                        "#municipio-selector option:not([value='estatal'])",
                    );
                    if (primerMunicipio)
                        idSeleccionInicial = primerMunicipio.value;
                }

                if (idSeleccionInicial === "estatal") {
                    estatalBtn.classList.add("active");
                }
                appState.municipioIds = [idSeleccionInicial];
                municipioSelector.setValue(appState.municipioIds, true);
                appState.indicatorId = linkActivo.dataset.indicadorId;

                // --- INICIO DE CORRECCIÓN 3 ---
                appState.indicatorEsComplejo =
                    linkActivo.dataset.esComplejo === "true";
                // console.log(
                //   "-> Indicador por defecto complejo:",
                //   appState.indicatorEsComplejo
                // );
                // --- FIN DE CORRECCIÓN 3 ---
            }
        }

        if (linkActivo) {
            linkActivo.classList.add("fw-bold", "text-primary");
            expandirAcordeonHacia(linkActivo);
            const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
            gestionarOpcionEstatal(tipoDato);
        }

        if (mustCallUpdateDashboard) {
            updateDashboard();
            gestionarBotonResumen();
        }
    }
    async function CargaInicialOLD() {
        // --- PASO 1: ESPERAMOS a que el mapa se inicialice ---
        await initMap();

        // --- PASO 2: Preparamos la opción 'estatal' ---
        opcionEstatalElement = {
            value: "estatal",
            text: "-- Total Estatal --",
            orden: 0,
        };

        const urlParams = new URLSearchParams(window.location.search);
        const indicadorIdFromUrl = urlParams.get("indicador_id");
        const municipioIdsFromUrl = urlParams.get("municipio_ids");
        const regionIdFromUrl = urlParams.get("region_id");
        const nivelFromUrl = urlParams.get("nivel");

        const aniosFromUrl = urlParams.getAll("anios[]");

        let linkActivo = null;
        let mustCallUpdateDashboard = true;

        if (aniosFromUrl.length > 0) {
            appState.selectedYears = aniosFromUrl;
            console.log("-> Años de URL detectados:", aniosFromUrl);
        }

        if (indicadorIdFromUrl && nivelFromUrl && regionIdFromUrl) {
            console.log("-> Parámetros de URL regionales detectados.");

            appState.nivelDeAgregacion = nivelFromUrl;
            appState.indicatorId = indicadorIdFromUrl;

            // Activamos la pestaña correcta (Micro o Macro)
            const tabSelector = `#pills-tab-nivel .nav-link[data-nivel="${nivelFromUrl}"]`;
            const tabToActivate = document.querySelector(tabSelector);

            if (tabToActivate) {
                // Le decimos al script que NO llame a updateDashboard todavía
                mustCallUpdateDashboard = false;
                const tab = new bootstrap.Tab(tabToActivate);

                // 1. Nos suscribimos al evento 'shown.bs.tab' UNA SOLA VEZ.
                //    Este evento se dispara DESPUÉS de que la pestaña es visible
                //    y el acordeón de regiones ya ha sido clonado y existe.
                tabToActivate.addEventListener(
                    "shown.bs.tab",
                    () => {
                        console.log(
                            "Pestaña regional terminó de mostrarse. Ahora sí, cargando datos...",
                        );

                        appState.indicatorId = indicadorIdFromUrl;
                        if (nivelFromUrl === "microrregion") {
                            appState.microrregionId = regionIdFromUrl;
                        } else if (nivelFromUrl === "macrorregion") {
                            appState.macrorregionId = regionIdFromUrl;
                        }

                        appState.selectedYears = aniosFromUrl;
                        // 2. Activamos el indicador (ahora sí existe)
                        linkActivo = document.querySelector(
                            `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
                        );
                        if (linkActivo) {
                            linkActivo.classList.add("fw-bold", "text-primary");
                            expandirAcordeonHacia(linkActivo);
                            const tipoDato =
                                linkActivo.dataset.tipoDato || "Absoluto";
                            gestionarOpcionEstatal(tipoDato);
                        }

                        // 3. Llamamos a updateDashboard AHORA
                        updateDashboard();
                        gestionarBotonResumen();
                    },
                    { once: true },
                ); // {once: true} es clave, asegura que este listener solo corra 1 vez

                // 4. Mostramos la pestaña
                tab.show();
                // --- FIN DE LA CORRECCIÓN ---
            }

            // Asignamos el ID y actualizamos el selector visual
            if (nivelFromUrl === "microrregion") {
                appState.microrregionId = regionIdFromUrl;
                microrregionSelector.setValue(regionIdFromUrl, true);
            } else if (nivelFromUrl === "macrorregion") {
                appState.macrorregionId = regionIdFromUrl;
                macrorregionSelector.setValue(regionIdFromUrl, true);
            }
        }
        // CASO B: VISTA MUNICIPAL
        else if (indicadorIdFromUrl && municipioIdsFromUrl) {
            console.log("-> Parámetros de URL de municipio detectados.");
            appState.indicatorId = indicadorIdFromUrl;
            appState.municipioIds = municipioIdsFromUrl.split(",");
            municipioSelector.setValue(appState.municipioIds, true);
            linkActivo = document.querySelector(
                `.indicador-link[data-indicador-id='${appState.indicatorId}']`,
            );
        }
        // CASO C: CARGA POR DEFECTO
        else {
            // console.log("-> Sin parámetros de URL. Usando carga por defecto.");
            linkActivo = document.querySelector(".indicador-link");
            if (linkActivo) {
                const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
                gestionarOpcionEstatal(tipoDato);

                let idSeleccionInicial = "estatal";
                if (tipoDato.toLowerCase() !== "absoluto") {
                    const primerMunicipio = document.querySelector(
                        "#municipio-selector option:not([value='estatal'])",
                    );
                    if (primerMunicipio)
                        idSeleccionInicial = primerMunicipio.value;
                }

                if (idSeleccionInicial === "estatal") {
                    estatalBtn.classList.add("active");
                }
                appState.municipioIds = [idSeleccionInicial];
                municipioSelector.setValue(appState.municipioIds, true);
                appState.indicatorId = linkActivo.dataset.indicadorId;
            }
        }

        if (linkActivo) {
            linkActivo.classList.add("fw-bold", "text-primary");
            expandirAcordeonHacia(linkActivo);
            const tipoDato = linkActivo.dataset.tipoDato || "Absoluto";
            gestionarOpcionEstatal(tipoDato);
        }

        // Llamamos a updateDashboard solo si no estamos esperando a que se muestre la pestaña regional
        if (mustCallUpdateDashboard) {
            updateDashboard();
            gestionarBotonResumen();
        }
    }

    window.addEventListener("popstate", (event) => {
        console.log(
            "Evento popstate detectado. Recargando estado desde la URL.",
        );

        // Simplemente volvemos a ejecutar CargaInicial.
        // CargaInicial ya está diseñada para leer la URL y
        // configurar el dashboard, ¡así que no hay que escribir más código!

        // (Opcional: resetea cosas si es necesario, pero CargaInicial debería bastar)
        // location.reload(); // La forma "fácil" pero menos elegante (recarga toda la página)

        // La forma "elegante" (reutiliza tu código existente)
        CargaInicial();
    });

    // Aquí van TODOS tus listeners:
    /**
     * Listener para las pestañas principales (Municipio, Micro, Macro).
     * Se dispara cuando una nueva pestaña ha sido mostrada.
     */
    nivelTabs.forEach((tab) => {
        tab.addEventListener("show.bs.tab", function (event) {
            const nivel = event.currentTarget.dataset.nivel;
            // console.log(`Cambiando a nivel: ${nivel}`);

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

            if (chart) {
                chart.destroy();
                chart = null;
            }
            // 4. Limpiamos el gráfico y deseleccionamos indicadores
            chartContainer.innerHTML = `<p class="text-muted text-center pt-5">Selecciona un indicador y una ubicación para comenzar.</p>`;
            chartContainerRegions.innerHTML = `<p class="text-muted text-center pt-5">Selecciona un indicador y una región para comenzar.</p>`;
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

            // 6. ¡CRUCIAL! Deshabilitamos los botones de consulta
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
            const targetPaneId = event.target.getAttribute("data-bs-target");
            const targetPane = document.querySelector(targetPaneId);

            if (targetPane) {
                // 2. Buscamos el acordeón que está DENTRO de ese panel.
                const activeAccordion = targetPane.querySelector(".accordion");
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

            const tipoDatoNuevo = e.target.dataset.tipoDato || "Absoluto";
            appState.indicatorEsComplejo =
                e.target.dataset.esComplejo === "true";
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
            appState.indicatorId = e.target.dataset.indicadorId;
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
                ? "Indicador complejo en Pantalla Completa"
                : "Gráfico en Pantalla Completa";

        fullscreenChart = new ApexCharts(
            fullscreenChartContainer,
            fullscreenOptions,
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
                contentCol.classList.add("col-lg-9", "col-md-9"); // Asegúrate de que estas clases coincidan con tu HTML

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

            // 5. ¡¡EL PASO MÁS IMPORTANTE!!
            // Forzar al gráfico y al mapa a redibujarse
            // para que se ajusten al nuevo tamaño del contenedor.
            setTimeout(() => {
                // --- INICIO DE CORRECCIÓN DEL BUG (Glitcheo) ---
                if (chart && lastChartOptions && chart.el) {
                    // 1. Obtenemos el contenedor que el chart usaba
                    const container = chart.el;

                    // 2. Destruimos el chart anterior
                    chart.destroy();

                    // 3. Creamos uno NUEVO en el MISMO contenedor
                    //    usando las últimas opciones guardadas.
                    //    Esto lo fuerza a recalcular el ancho correctamente.
                    chart = new ApexCharts(container, lastChartOptions);
                    chart.render();
                }
                // --- FIN DE LA CORRECCIÓN ---

                if (mapMunicipal) {
                    mapMunicipal.invalidateSize(true); // Leaflet
                }
                if (mapRegional) {
                    mapRegional.invalidateSize(true); // Leaflet
                }
            }, 300); // Un pequeño retraso para dar tiempo a que la animación de la columna termine
        });
    });
    exportBtn.addEventListener("click", handleExport);
    exportBtnRegions.addEventListener("click", handleExport);

    // Ejecución inicial
    CargaInicial();
});
