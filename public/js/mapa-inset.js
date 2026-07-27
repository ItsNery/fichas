function initHeroMapInset(chartDom, feature) {
    if (!chartDom || !feature) return;

    const wrapper = chartDom.closest(".hero-ficha__mapa-contenedor");
    const map = echarts.getMap("puebla");
    if (!wrapper || !map) return;

    function boundsOfGeometry(geometry) {
        const bounds = {
            minX: Infinity,
            minY: Infinity,
            maxX: -Infinity,
            maxY: -Infinity,
        };

        function visit(value) {
            if (typeof value[0] === "number") {
                bounds.minX = Math.min(bounds.minX, value[0]);
                bounds.maxX = Math.max(bounds.maxX, value[0]);
                bounds.minY = Math.min(bounds.minY, value[1]);
                bounds.maxY = Math.max(bounds.maxY, value[1]);
                return;
            }
            value.forEach(visit);
        }

        visit(geometry.coordinates);
        return bounds;
    }

    const stateBounds = map.geoJSON.features
        .map((item) => boundsOfGeometry(item.geometry))
        .reduce(
            (result, bounds) => ({
                minX: Math.min(result.minX, bounds.minX),
                minY: Math.min(result.minY, bounds.minY),
                maxX: Math.max(result.maxX, bounds.maxX),
                maxY: Math.max(result.maxY, bounds.maxY),
            }),
            { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity },
        );
    const bounds = boundsOfGeometry(feature.geometry);
    const width = bounds.maxX - bounds.minX;
    const height = bounds.maxY - bounds.minY;
    if (!width || !height) return;

    const stateWidth = stateBounds.maxX - stateBounds.minX;
    const stateHeight = stateBounds.maxY - stateBounds.minY;
    const relativeArea = (width * height) / (stateWidth * stateHeight);

    // El inset se reserva para municipios que son realmente pequeños en el mapa estatal.
    if (relativeArea >= 0.005) return;

    const zoom = Math.min(
        12,
        Math.max(
            2.2,
            Math.min(
                stateWidth / width,
                stateHeight / height,
            ) * 0.65,
        ),
    );

    const inset = document.createElement("aside");
    inset.className = "hero-ficha__mapa-inset";
    inset.innerHTML = `
        <div class="hero-ficha__mapa-inset-chart"></div>
    `;
    wrapper.appendChild(inset);

    const insetChart = echarts.init(
        inset.querySelector(".hero-ficha__mapa-inset-chart"),
        null,
        { renderer: "svg" },
    );
    const name = feature.properties.name;

    insetChart.setOption({
        backgroundColor: "transparent",
        animation: false,
        series: [{
            type: "map",
            map: "puebla",
            silent: true,
            roam: false,
            center: [
                (bounds.minX + bounds.maxX) / 2,
                (bounds.minY + bounds.maxY) / 2,
            ],
            zoom,
            layoutCenter: ["50%", "56%"],
            layoutSize: "100%",
            label: { show: false },
            itemStyle: {
                areaColor: "rgba(255, 255, 255, .16)",
                borderColor: "rgba(255, 255, 255, .75)",
                borderWidth: 1,
            },
            data: [{
                name,
                itemStyle: {
                    areaColor: "#c79b66",
                    borderColor: "#fff",
                    borderWidth: 2,
                    shadowBlur: 18,
                    shadowColor: "rgba(0, 0, 0, .55)",
                },
            }],
        }],
    });

    window.addEventListener("resize", () => insetChart.resize());
}
