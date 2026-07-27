<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo mapa con ampliación</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <style>
        :root {
            color-scheme: dark;
            font-family: Arial, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: #152b3a;
            color: #fff;
        }

        .page {
            width: min(1100px, 100% - 32px);
            margin: 0 auto;
            padding: 32px 0;
        }

        h1 { margin: 0 0 8px; font-size: 1.7rem; }
        p { margin: 0 0 20px; color: rgba(255, 255, 255, .72); }

        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        select {
            min-width: 260px;
            padding: 9px 12px;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 6px;
            background: #203e50;
            color: #fff;
        }

        .map-stage {
            position: relative;
            height: min(70vh, 620px);
            min-height: 380px;
            overflow: hidden;
            border-radius: 16px;
            background: rgba(255, 255, 255, .05);
        }

        #map-main { width: 100%; height: 100%; }

        .map-inset {
            position: absolute;
            right: 20px;
            bottom: 20px;
            width: min(32%, 290px);
            height: 34%;
            min-width: 210px;
            min-height: 170px;
            overflow: hidden;
            border: 3px solid #fff;
            border-radius: 10px;
            background: #203e50;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .45);
        }

        .map-inset__title {
            position: absolute;
            z-index: 1;
            top: 0;
            left: 0;
            right: 0;
            padding: 7px 10px;
            background: rgba(21, 43, 58, .88);
            color: #fff;
            font-size: .75rem;
            font-weight: bold;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        #map-inset { width: 100%; height: 100%; }

        @media (max-width: 700px) {
            .map-stage { min-height: 480px; }
            .map-inset { width: 45%; min-width: 180px; height: 30%; }
        }
    </style>
</head>
<body>
    <main class="page">
        <h1>Mapa completo con ampliación municipal</h1>
        <p>El mapa principal conserva todo Puebla. El recuadro muestra el detalle del municipio seleccionado.</p>

        <div class="toolbar">
            <label for="municipio">Municipio:</label>
            <select id="municipio"></select>
        </div>

        <section class="map-stage">
            <div id="map-main"></div>
            <aside class="map-inset">
                <div class="map-inset__title">Detalle del municipio</div>
                <div id="map-inset"></div>
            </aside>
        </section>
    </main>

    <script>
        const geojsonUrl = @json(asset('geojson/municipios_puebla_slim.geojson'));
        const select = document.getElementById('municipio');
        const mainChart = echarts.init(document.getElementById('map-main'), null, { renderer: 'svg' });
        const insetChart = echarts.init(document.getElementById('map-inset'), null, { renderer: 'svg' });

        function boundsOfGeometry(geometry) {
            const bounds = { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity };

            function visit(value) {
                if (typeof value[0] === 'number') {
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

        function mapStyle(selectedName) {
            return {
                type: 'map',
                map: 'puebla-demo',
                silent: true,
                roam: false,
                layoutCenter: ['50%', '52%'],
                layoutSize: '96%',
                label: { show: false },
                itemStyle: {
                    areaColor: 'rgba(255, 255, 255, .16)',
                    borderColor: 'rgba(255, 255, 255, .62)',
                    borderWidth: 1
                },
                data: [{
                    name: selectedName,
                    itemStyle: {
                        areaColor: '#c79b66',
                        borderColor: '#fff',
                        borderWidth: 2,
                        shadowBlur: 18,
                        shadowColor: 'rgba(0, 0, 0, .55)'
                    }
                }]
            };
        }

        function render(feature, stateBounds) {
            const name = feature.properties.name;
            const bounds = boundsOfGeometry(feature.geometry);
            const center = [
                (bounds.minX + bounds.maxX) / 2,
                (bounds.minY + bounds.maxY) / 2
            ];
            const widthRatio = (stateBounds.maxX - stateBounds.minX) / (bounds.maxX - bounds.minX);
            const heightRatio = (stateBounds.maxY - stateBounds.minY) / (bounds.maxY - bounds.minY);
            const zoom = Math.min(12, Math.max(2.2, Math.min(widthRatio, heightRatio) * .65));

            mainChart.setOption({
                backgroundColor: 'transparent',
                animation: false,
                series: [mapStyle(name)]
            }, true);

            insetChart.setOption({
                backgroundColor: 'transparent',
                animation: false,
                series: [{
                    ...mapStyle(name),
                    layoutCenter: ['50%', '57%'],
                    layoutSize: '100%',
                    center,
                    zoom,
                    itemStyle: {
                        areaColor: 'rgba(255, 255, 255, .16)',
                        borderColor: 'rgba(255, 255, 255, .75)',
                        borderWidth: 1
                    }
                }]
            }, true);
        }

        fetch(geojsonUrl)
            .then(response => response.json())
            .then(geojson => {
                geojson.features.forEach(feature => {
                    feature.properties.name = feature.properties.nomgeo.toUpperCase().trim();
                });
                echarts.registerMap('puebla-demo', geojson);

                const featureBounds = geojson.features.map(feature => boundsOfGeometry(feature.geometry));
                const stateBounds = featureBounds.reduce((result, bounds) => ({
                    minX: Math.min(result.minX, bounds.minX),
                    minY: Math.min(result.minY, bounds.minY),
                    maxX: Math.max(result.maxX, bounds.maxX),
                    maxY: Math.max(result.maxY, bounds.maxY)
                }), { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity });

                geojson.features
                    .slice()
                    .sort((a, b) => {
                        const aBounds = boundsOfGeometry(a.geometry);
                        const bBounds = boundsOfGeometry(b.geometry);
                        return (aBounds.maxX - aBounds.minX) * (aBounds.maxY - aBounds.minY)
                            - (bBounds.maxX - bBounds.minX) * (bBounds.maxY - bBounds.minY);
                    })
                    .forEach(feature => {
                        const option = document.createElement('option');
                        option.value = feature.properties.cvegeo;
                        option.textContent = feature.properties.name;
                        select.appendChild(option);
                    });

                const renderSelected = () => {
                    const feature = geojson.features.find(item => String(item.properties.cvegeo) === select.value);
                    if (feature) render(feature, stateBounds);
                };

                select.addEventListener('change', renderSelected);
                select.selectedIndex = 0;
                renderSelected();
                window.addEventListener('resize', () => {
                    mainChart.resize();
                    insetChart.resize();
                });
            })
            .catch(() => {
                document.querySelector('.map-stage').textContent = 'No se pudo cargar el GeoJSON.';
            });
    </script>
</body>
</html>
