// En tu script-ficha.js

// --- 1. LA NUEVA FUNCIÓN REUTILIZABLE ---
function setupIndicatorSearch(inputId, accordionId) {
    const searchInput = document.getElementById(inputId);
    const accordion = document.getElementById(accordionId);
    if (!searchInput || !accordion) return; // Salimos si no existen los elementos

    const allIndicators = accordion.querySelectorAll(".indicador-link");
    const allTematicas = accordion.querySelectorAll(
        ".accordion-item .accordion-item"
    );
    const allDimensions = accordion.querySelectorAll(
        `#${accordionId} > .accordion-item`
    );

    searchInput.addEventListener("keyup", function () {
        const searchTerm = this.value.toLowerCase().trim();

        if (searchTerm === "") {
            allIndicators.forEach(
                (link) => (link.closest("li").style.display = "block")
            );
            allTematicas.forEach((item) => (item.style.display = "block"));
            allDimensions.forEach((item) => (item.style.display = "block"));
            const openPanels = accordion.querySelectorAll(
                ".accordion-collapse.show"
            );
            openPanels.forEach((panel) =>
                new bootstrap.Collapse(panel, { toggle: false }).hide()
            );
            return;
        }

        // Dentro de la función setupIndicatorSearch

        allIndicators.forEach((link) => {
            const indicatorName = link.textContent.toLowerCase();
            const li = link.closest("li");
            const tematicaCollapseEl = document.querySelector(
                link.dataset.tematicaTarget
            );
            const dimensionCollapseEl = document.querySelector(
                link.dataset.dimensionTarget
            );

            if (indicatorName.includes(searchTerm)) {
                // ▼▼▼ AÑADE ESTE LOG DE DEPURACIÓN ▼▼▼
                if (accordion.id === "accordionDimensionsRegions") {
                    // Log solo para el de regiones
                    console.log(
                        `Coincidencia: "${indicatorName}". Intentando abrir:`,
                        {
                            dimensionPanel: dimensionCollapseEl,
                            tematicaPanel: tematicaCollapseEl,
                        }
                    );
                }
                // ▲▲▲ FIN DEL LOG ▲▲▲

                li.style.display = "block"; // Mostramos el indicador
                // Expandimos su temática y dimensión para que sea visible
                if (tematicaCollapseEl)
                    new bootstrap.Collapse(tematicaCollapseEl, {
                        toggle: false,
                    }).show();
                if (dimensionCollapseEl)
                    new bootstrap.Collapse(dimensionCollapseEl, {
                        toggle: false,
                    }).show();
            } else {
                li.style.display = "none"; // Ocultamos el indicador
            }
        });

        allTematicas.forEach((tematica) => {
            const visibleIndicator = tematica.querySelector(
                'li[style*="display: block"]'
            );
            tematica.style.display = visibleIndicator ? "block" : "none";
        });

        allDimensions.forEach((dimension) => {
            const visibleTematica = dimension.querySelector(
                '.accordion-item[style*="display: block"]'
            );
            dimension.style.display = visibleTematica ? "block" : "none";
        });
    });
}

// --- 2. LLAMAMOS A LA FUNCIÓN PARA CADA BUSCADOR ---
// Esto le dice a la función que conecte el buscador 'indicador-search' con el acordeón 'accordionDimensions'
setupIndicatorSearch("indicador-search", "accordionDimensions");
setupIndicatorSearch("indicador-search-regions", "accordionDimensionsRegions");

// Y hacemos lo mismo para el nuevo buscador de las regiones
setupIndicatorSearch("indicador-search-regions", "accordionDimensionsRegions");
