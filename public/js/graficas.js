
    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. REFERENCIAS A ELEMENTOS DEL DOM ---
        const municipioSelector = $('#municipio-selector');
        const indicatorLinks = document.querySelectorAll('.indicador-link');
        const chartContainer = document.getElementById('chart-container');
        const chartTitle = document.getElementById('chart-title');
        const metadataContainer = document.getElementById('metadata-container');
        const descriptionElement = document.getElementById('indicator-description');
        const sourceElement = document.getElementById('indicator-source');
        const availableYearsElement = document.getElementById('indicator-available-years');
        const yearSelectorContainer = document.getElementById('year-selector-container');
        const yearSelector = document.getElementById('year-selector');
        const resumenBtn = document.getElementById('resumen-btn');
        let chart;

        // --- 2. EL "CEREBRO" O ESTADO CENTRAL DE LA APLICACIÓN ---
        const appState = {
            indicatorId: null,
            municipioIds: [],
            year: null,
            isLoading: false
        };

        // --- INICIALIZACIÓN DE SELECT2 ---
        municipioSelector.select2({
            theme: "bootstrap-5",
            placeholder: "Selecciona municipio(s)",
            maximumSelectionLength: 2,
            language: "es",
        });

        // --- Funciones de ayuda (sin cambios) ---
        function expandirAcordeonHacia(linkElemento) { /* ... tu código ... */ }
        function renderizarGrafico(datosParaGrafico) { /* ... tu código ... */ }

        /**
         * 3. FUNCIÓN PRINCIPAL Y ÚNICA PARA CARGAR DATOS
         */
        function updateDashboard() {
            // Prevenimos llamadas si ya está cargando o si falta información
            if (appState.isLoading || !appState.indicatorId || appState.municipioIds.length === 0) {
                return;
            }

            appState.isLoading = true;
            chartTitle.innerText = "Cargando...";
            chartContainer.innerHTML = '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

            fetch("{{ route('api.data') }}", {
                method: "POST",
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    indicador_id: appState.indicatorId,
                    municipio_ids: appState.municipioIds,
                    anio: appState.year,
                }),
            })
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                renderizarGrafico(data);
            })
            .catch(error => {
                console.error("Error al cargar datos:", error);
                chartContainer.innerHTML = '<p class="text-danger text-center pt-5">Hubo un error al cargar la información.</p>';
            })
            .finally(() => {
                appState.isLoading = false;
            });
        }

        // --- 4. EVENT LISTENERS SIMPLIFICADOS ---
        // Su única tarea es actualizar el "cerebro" (appState) y llamar a la actualización.

        indicatorLinks.forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                appState.indicatorId = e.target.dataset.indicatorId; // Actualiza el estado
                indicatorLinks.forEach(el => el.classList.remove("fw-bold", "text-primary"));
                e.target.classList.add("fw-bold", "text-primary");
                updateDashboard(); // Llama a la función principal
            });
        });

        municipioSelector.on("change", function() {
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
            appState.year = null; // Resetea el año al cambiar municipios
            updateDashboard();
        });

        yearSelector.addEventListener("change", function() {
            appState.year = this.value; // Actualiza el estado
            updateDashboard(); // Llama a la función principal
        });

        // --- 5. CARGA INICIAL CONTROLADA ---
        function CargaInicial() {
            if (municipioSelector.find("option[value='estatal']").length === 0) {
                const estatalOption = new Option("-- Total Estatal --", "estatal");
                municipioSelector.prepend(estatalOption);
            }
            
            // 1. Establecemos el estado inicial SIN disparar eventos
            appState.municipioIds = ["estatal"];
            municipioSelector.val(appState.municipioIds).trigger("change.select2");
            
            const firstIndicatorLink = document.querySelector(".indicador-link");
            if (firstIndicatorLink) {
                appState.indicatorId = firstIndicatorLink.dataset.indicatorId;
                firstIndicatorLink.classList.add("fw-bold", "text-primary");
                expandirAcordeonHacia(firstIndicatorLink);
                
                // 2. Llamamos a la función principal UNA SOLA VEZ con el estado ya listo
                updateDashboard();
            }
        }
        
        CargaInicial();
    });
