// public/js/fichas-dashboard.js (Versión Unificada y Corregida)
document.addEventListener("DOMContentLoaded", function () {
    // --- 2. PALETA DE COLORES ---


    /**
     * Función principal y "director de orquesta". Decide qué se muestra (mapa, gráfico o ambos)
     * y solicita los datos necesarios al backend.
     */
    function updateDashboard() {
        // 1. Verificación inicial: salimos si no hay información suficiente para continuar.
        const esCasoMunicipio = appState.nivelDeAgregacion === "municipio";
        if (
            appState.isLoading ||
            !appState.indicatorId ||
            (esCasoMunicipio && appState.municipioIds.length === 0)
        ) {
            return;
        }
        appState.isLoading = true;

        // 2. Reseteo de la interfaz: ocultamos todo y mostramos un estado de carga.
        const activeChartContainer = esCasoMunicipio
            ? chartContainer
            : chartContainerRegions;
        const activeChartTitle = esCasoMunicipio
            ? chartTitle
            : chartTitleRegions;

        activeChartTitle.innerText = "Cargando...";
        activeChartContainer.innerHTML =
            '<div class="text-center pt-5"><div class="spinner-border" role="status"></div></div>';

        // Solo manipulamos el mapa si estamos en la vista de municipios
        if (mapContainer) {
            mapContainer.style.display = "none";
            mapLegend.innerHTML = "";
        }
        chartContainer.style.display = "none"; // Ocultamos el gráfico de municipios
        chartContainerRegions.style.display = "none"; // Ocultamos el gráfico de regiones

        // 3. Lógica de Decisión: ¿Qué componentes vamos a mostrar?
        const indicadorSeleccionado = document.querySelector(
            `.indicador-link[data-indicador-id='${appState.indicatorId}']`
        );
        const esAbsoluto =
            indicadorSeleccionado &&
            indicadorSeleccionado.dataset.tipoDato.toLowerCase() === "absoluto";
        const esEstatal =
            esCasoMunicipio &&
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] === "estatal";
        const esUnMunicipio =
            esCasoMunicipio &&
            appState.municipioIds.length === 1 &&
            appState.municipioIds[0] !== "estatal";

        let showMap = false;

        if (esEstatal && esAbsoluto) {
            showMap = true; // Muestra Mapa de Coropletas
        } else if (esUnMunicipio) {
            showMap = true; // Muestra Mapa con Zoom
            displaySingleMunicipalityMap(appState.municipioIds[0]);
        }

        // Mostramos los contenedores que se decidieron
        if (showMap) {
            mapContainer.style.display = "block";
            if (map) map.invalidateSize();
        }
        activeChartContainer.style.display = "block";

        // 4. Construimos el payload para la petición
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

        // 5. Hacemos la llamada fetch usando las constantes leídas del HTML
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
                // 6. Renderizamos los componentes con los datos recibidos
                renderizarGrafico(data, activeChartContainer, activeChartTitle);

                if (showMap && esEstatal && data.mapData) {
                    displayChoroplethMap(data.mapData);
                }
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





    // Listeners para los nuevos selectores de región
















    //  Funciones de Ayuda para la Interfaz (UI Helpers)












    CargaInicial();
    initMap();
    // No llamamos a initMap aquí, se llamará cuando se haga clic en la pestaña del mapa.
});
