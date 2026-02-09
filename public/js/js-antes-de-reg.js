document.addEventListener("DOMContentLoaded", function () {
    // --- 1. REFERENCIAS A ELEMENTOS DEL DOM ---
    const municipioSelector = $("#municipio-selector");
    const indicatorLinks = document.querySelectorAll(".indicador-link");
    const chartContainer = document.getElementById("chart-container");
    const chartTitle = document.getElementById("chart-title");
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
    const resumenBtn = document.getElementById("resumen-btn");
    const chartNoteContainer = document.getElementById("chart-note-container");
    const resumenUrlPrototype = resumenBtn.href;
    let opcionEstatalElement = null;
    let chart;

    const PALETA_COLORES = [
        "#246257",
        "#c79b66",
        "#5f1b2d",
        "#f4a261", // Naranja arena
        "#e76f51", // Rojo coral
        "#6a040f", // Rojo vino
        "#0077b6", // Azul cerúleo
    ];

    const yearSelectorEl = $("#year-selector");
    yearSelectorEl.select2({
        theme: "bootstrap-5",
        placeholder: "Selecciona año(s)",
        closeOnSelect: false, // Opcional: mantiene el menú abierto para elegir más años
    });

    // --- 2. EL "CEREBRO" O ESTADO CENTRAL ---
    const appState = {
        indicatorId: null,
        municipioIds: [],
        // year: null,
        selectedYears: [],
        isLoading: false,
    };

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
        municipioSelector.select2({
            theme: "bootstrap-5",
            placeholder: "Selecciona municipio(s)",
            maximumSelectionLength: 2,
        });
    }

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
        chart = new ApexCharts(chartContainer, options);
        chart.render();
    }

    /**
     * 3. FUNCIÓN PRINCIPAL Y ÚNICA PARA CARGAR DATOS
     */
    function updateDashboard() {
        console.log(
            "-> 1. updateDashboard() llamado. Estado actual:",
            JSON.parse(JSON.stringify(appState))
        );

        if (
            appState.isLoading ||
            !appState.indicatorId ||
            appState.municipioIds.length === 0
        ) {
            console.warn("   - Actualización OMITIDA. Razón:", {
                loading: appState.isLoading,
                indicator: appState.indicatorId,
                munis: appState.municipioIds.length,
            });
            return;
        }

        appState.isLoading = true;
        chartTitle.innerText = "Cargando...";
        chartContainer.innerHTML =
            '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

        console.log("-> 2. Haciendo llamada FETCH con:", {
            indicador_id: appState.indicatorId,
            municipio_ids: appState.municipioIds,
            anio: appState.year,
        });

        fetch("{{ route('api.data') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
            },
            body: JSON.stringify({
                indicador_id: appState.indicatorId,
                municipio_ids: appState.municipioIds,
                // anio: appState.year,
                anios: appState.selectedYears,
            }),
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response)
            )
            .then((data) => {
                console.log("-> 3. Datos RECIBIDOS. Renderizando gráfico...");
                renderizarGrafico(data);
            })
            .catch((error) => {
                console.error("-> X. ERROR en la llamada AJAX:", error);
                chartContainer.innerHTML =
                    '<p class="text-danger text-center pt-5">Hubo un error al cargar la información.</p>';
            })
            .finally(() => {
                console.log("-> 4. Carga FINALIZADA.");
                appState.isLoading = false;
            });
    }

    // --- 4. EVENT LISTENERS SIMPLIFICADOS ---
    // Su única tarea es actualizar el "cerebro" (appState) y llamar a la actualización.

    indicatorLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            console.log("Evento: Clic en Indicador");

            // Actualiza el indicador seleccionado en el estado
            appState.indicatorId = e.target.dataset.indicadorId;
            const tipoDatoNuevo = e.target.dataset.tipoDato || "Absoluto";

            // 1. Llama a nuestra función para habilitar/deshabilitar la opción
            gestionarOpcionEstatal(tipoDatoNuevo);

            // 2. CORRECCIÓN AUTOMÁTICA: Si 'Total Estatal' estaba seleccionado y ya no es válido...
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
                municipioSelector.val(appState.municipioIds);
            }

            // Estilos visuales
            indicatorLinks.forEach((el) =>
                el.classList.remove("fw-bold", "text-primary")
            );
            e.target.classList.add("fw-bold", "text-primary");

            // 3. Llama a la actualización del dashboard con el estado ya corregido
            updateDashboard();
        });
    });

    // NUEVO CÓDIGO - MÁS PRECISO Y SIN BUCLES
    // Listener 1: Se dispara ANTES de que un elemento sea seleccionado. Ideal para validaciones.
    municipioSelector.on("select2:selecting", function (e) {
        const seleccionPropuesta = e.params.args.data.id;
        const seleccionActual = $(this).val() || [];

        // Regla 1: Si se intenta seleccionar 'estatal' cuando ya hay otros municipios,
        // primero se deseleccionan todos los demás.
        if (seleccionPropuesta === "estatal" && seleccionActual.length > 0) {
            $(this).val(null); // Limpia la selección actual
        }

        // Regla 2: Si se intenta seleccionar un municipio cuando 'estatal' está activo,
        // primero se quita 'estatal'.
        if (
            seleccionPropuesta !== "estatal" &&
            seleccionActual.includes("estatal")
        ) {
            $(this).val(null); // Limpia la selección actual
        }
    });

    // Pon esta función junto a tus otras funciones de ayuda
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

    // Listener 2: Se dispara DESPUÉS de que la selección ha cambiado definitivamente.
    municipioSelector.on("change", function () {
        console.log("Evento: Cambio definitivo en Selector de Municipio");
        let selection = $(this).val() || [];

        // Regla 3: Si el usuario deja el campo vacío, se fuerza a 'estatal'.
        if (selection.length === 0) {
            selection = ["estatal"];
            // Actualizamos la UI sin disparar otro evento change.
            $(this).val(selection).trigger("change.select2");
            return; // Salimos para que el siguiente 'change' haga el updateDashboard.
        }

        // Finalmente, actualizamos el estado y llamamos al dashboard.
        // Esta parte solo se ejecuta con una selección ya validada.
        appState.municipioIds = selection;
        appState.year = null; // Resetea el año al cambiar municipios
        updateDashboard();
        gestionarBotonResumen();
    });

    // yearSelector.addEventListener("change", function() {
    //     console.log("Evento: Cambio en Selector de Año");
    //     appState.year = this.value; // Actualiza el estado
    //     updateDashboard(); // Llama a la función principal
    // });

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

    // --- 5. CARGA INICIAL CONTROLADA ---
    // CÓDIGO CORREGIDO ✅
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
