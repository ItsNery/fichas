// En fichas-dashboard.js

// --- LÓGICA PARA EL MAPA INTERACTIVO ---

const mapIndicatorSelector = document.getElementById("mapa-indicador-selector");
const mapYearSelector = document.getElementById("mapa-anio-selector");
const mapUpdateBtn = document.getElementById("mapa-update-btn");
const mapLegend = document.getElementById("map-legend");
let map = null; // Variable global para el objeto del mapa
let geojsonLayer = null; // Variable para la capa de municipios
let mapData = {}; // Variable para guardar los datos del indicador

// Función para inicializar el mapa (se llama una sola vez)
function initMap() {
    if (map) return; // Si ya está inicializado, no hacer nada

    map = L.map("map").setView([19.0414, -98.2063], 8); // Centrado en Puebla
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Llenamos el selector de indicadores con los indicadores absolutos
    document.querySelectorAll(".indicador-link").forEach((link) => {
        if (link.dataset.tipoDato.toLowerCase() === "absoluto") {
            mapIndicatorSelector.add(
                new Option(link.textContent.trim(), link.dataset.indicadorId)
            );
        }
    });

    // Llenamos el selector de años (puedes ajustar el rango)
    for (let year = new Date().getFullYear(); year >= 2010; year--) {
        mapYearSelector.add(new Option(year, year));
    }
}

// Función para obtener el color según el valor
function getColor(value, quintiles) {
    if (value === null) return "#ccc"; // Gris para sin datos
    // Comparamos el valor con los límites de cada quintil
    if (value >= quintiles[3]) return "#e76f51"; // > 80% (más alto)
    if (value >= quintiles[2]) return "#f4a261"; // > 60%
    if (value >= quintiles[1]) return "#e9c46a"; // > 40%
    if (value >= quintiles[0]) return "#2a9d8f"; // > 20%
    return "#264653"; // <= 20% (más bajo)
}

// Función principal para cargar los datos y dibujar el mapa
async function updateMap() {
    mapUpdateBtn.disabled = true;
    mapUpdateBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Cargando...`;

    const indicadorId = mapIndicatorSelector.value;
    const anio = mapYearSelector.value;
    const url = `/fichas/api/mapa-datos/${indicadorId}/${anio}`;

    try {
        const response = await fetch(url);
        mapData = await response.json();

        if (Object.keys(mapData).length === 0) {
            alert(
                "No se encontraron datos para el indicador y año seleccionados."
            );
            if (geojsonLayer) map.removeLayer(geojsonLayer);
            mapLegend.innerHTML = "";
            return;
        }

        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        // --- INICIO DE LA LÓGICA DE CUANTILES ---
        const values = Object.values(mapData)
            .filter((v) => v !== null)
            .sort((a, b) => a - b);
        const min = values[0];
        const max = values[values.length - 1];

        // Calculamos los puntos de corte para los 5 grupos (quintiles)
        const quintiles = [
            values[Math.floor(values.length * 0.2)],
            values[Math.floor(values.length * 0.4)],
            values[Math.floor(values.length * 0.6)],
            values[Math.floor(values.length * 0.8)],
        ];
        // --- FIN DE LA LÓGICA DE CUANTILES ---

        const geojsonResponse = await fetch("/geojson/Prueba1.geojson");
        const geojson = await geojsonResponse.json();

        geojsonLayer = L.geoJSON(geojson, {
            style: function (feature) {
                const municipioId = feature.properties.cvegeo;
                const value = mapData[municipioId] || null;
                return {
                    fillColor: getColor(value, quintiles), // Pasamos los quintiles
                    weight: 1,
                    opacity: 1,
                    color: "white",
                    fillOpacity: 0.8,
                };
            },
            onEachFeature: function (feature, layer) {
                const municipioId = feature.properties.cvegeo;
                const nombre = feature.properties.nomgeo;
                const valor = mapData[municipioId]
                    ? new Intl.NumberFormat().format(mapData[municipioId])
                    : "Sin datos";
                layer.bindPopup(
                    `<strong>${nombre}</strong><br>Valor: ${valor}`
                );
            },
        }).addTo(map);

        mapLegend.innerHTML = `<strong>Leyenda:</strong> De <span style="color:${getColor(
            min,
            quintiles
        )}">${new Intl.NumberFormat().format(
            min
        )}</span> a <span style="color:${getColor(
            max,
            quintiles
        )}">${new Intl.NumberFormat().format(max)}</span>`;
    } catch (error) {
        console.error("Error al actualizar el mapa:", error);
        alert("No se pudieron cargar los datos para el mapa.");
    } finally {
        mapUpdateBtn.disabled = false;
        mapUpdateBtn.innerHTML = `Actualizar Mapa`;
    }
}

// Event Listeners para el mapa
document
    .getElementById("pill-mapa-tab")
    .addEventListener("shown.bs.tab", initMap);
mapUpdateBtn.addEventListener("click", updateMap);
