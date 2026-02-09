// --- LÓGICA PARA EL MAPA INTERACTIVO (VERSIÓN FINAL) ---

const mapIndicatorSelector = document.getElementById("mapa-indicador-selector");
const mapYearSelector = document.getElementById("mapa-anio-selector");
const mapUpdateBtn = document.getElementById("mapa-update-btn");
const mapLegend = document.getElementById("map-legend");
let map = null;
let geojsonLayer = null;
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
                new Option(link.textContent.trim(), link.dataset.indicadorId)
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
