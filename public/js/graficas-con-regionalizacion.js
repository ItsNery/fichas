document.addEventListener("DOMContentLoaded", function () {
    // --- 0. SETUP INICIAL ---
    let opcionEstatalElement = null;

    // --- 1. REFERENCIAS A ELEMENTOS DEL DOM (COMPLETO) ---
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

    let chart;

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

    // --- 5. FUNCIONES DE AYUDA ---
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

    // Aquí irán tus otras funciones como renderizarGrafico, gestionarBotonResumen, etc.
    // Las añadiremos al final para mantener el orden.
    // --- 6. EVENT LISTENERS PRINCIPALES ---

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

            // 2. Mostramos/ocultamos los contenedores de selectores
            microrregionContainer.style.display =
                nivel === "microrregion" ? "block" : "none";
            macrorregionContainer.style.display =
                nivel === "macrorregion" ? "block" : "none";

            // 3. Filtramos el acordeón de la vista regional
            if (nivel !== "municipio") {
                filtrarAcordeonRegional(); // Oculta los indicadores no-absolutos

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
            }

            // 5. Futuro paso: Resetear el gráfico y la selección de indicador
            console.log("Estado actualizado:", appState);
            // Aquí se podría limpiar el gráfico actual.
        });
    });

    // --- 7. LÓGICA DE DATOS Y RENDERIZADO ---

    /**
     * Función principal (ACTUALIZADA) para solicitar y renderizar los datos.
     */
    function updateDashboard() {
        // Previene llamadas si falta información esencial
        if (
            appState.isLoading ||
            !appState.indicatorId ||
            (appState.nivelDeAgregacion === "municipio" &&
                appState.municipioIds.length === 0) ||
            (appState.nivelDeAgregacion === "microrregion" &&
                !appState.microrregionId) ||
            (appState.nivelDeAgregacion === "macrorregion" &&
                !appState.macrorregionId)
        ) {
            console.warn(
                "Llamada a updateDashboard omitida por falta de datos",
                appState
            );
            return;
        }

        appState.isLoading = true;
        // Mostramos el spinner de carga en el contenedor apropiado
        const activeChartContainer =
            appState.nivelDeAgregacion === "municipio"
                ? document.getElementById("chart-container")
                : document.getElementById("chart-container-regions");
        const activeChartTitle =
            appState.nivelDeAgregacion === "municipio"
                ? document.getElementById("chart-title")
                : document.getElementById("chart-title-regions");
        activeChartTitle.innerText = "Cargando...";
        activeChartContainer.innerHTML =
            '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

        // Construimos el cuerpo de la petición según el estado
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

        console.log("Enviando payload:", payload);

        fetch("{{ route('api.data') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
            },
            body: JSON.stringify(payload),
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response)
            )
            .then((data) => {
                console.log("Datos recibidos:", data);
                // Pasamos el contenedor activo a la función de renderizado
                renderizarGrafico(data, activeChartContainer, activeChartTitle);
            })
            .catch((error) => {
                console.error("Error en la llamada AJAX:", error);
                activeChartContainer.innerHTML =
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
        // chartContainer.innerHTML = "";
        container.innerHTML = "";
        titleElement.innerText = datosParaGrafico.titulo;
        // chartTitle.innerText = datosParaGrafico.titulo;
        metadataContainer.style.display = "block";
        descriptionElement.innerText =
            datosParaGrafico.descripcion || "No disponible.";
        sourceElement.innerText = datosParaGrafico.fuente || "No disponible.";
        methodElement.innerText =
            datosParaGrafico.metodo_calculo || "No disponible.";

        if (datosParaGrafico.nota_explicativa) {
            chartNoteContainer.innerHTML = `<p class="mb-0">${datosParaGrafico.nota_explicativa}</p>`;
            chartNoteContainer.style.display = "block";
        } else {
            chartNoteContainer.innerHTML = "";
            chartNoteContainer.style.display = "none";
        }
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
            if (datosParaGrafico.selected_years) {
                // Usamos la referencia de jQuery que ya teníamos
                yearSelectorEl
                    .val(datosParaGrafico.selected_years)
                    .trigger("change");
            }
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
                colors: ["#008FFB", "#E4007C"],
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
});
