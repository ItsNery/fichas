import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import area from '@turf/area';
import intersect from '@turf/intersect';
import { featureCollection } from '@turf/helpers';
import { arcgisToGeoJSON } from '@esri/arcgis-to-geojson-utils';

const root = process.cwd();
const municipalPath = path.join(root, 'public/geojson/puebla_municipios_wgs84.geojson');
const outputPath = path.join(root, 'database/data/municipios_clima.json');
const cacheDirectory = path.join(root, 'storage/app/climate-api-cache');
const endpoint = 'https://sigagis.conagua.gob.mx/ArcGIS/rest/services/Climas/MapServer/0/query';

function toArcGisPolygon(geometry) {
    const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;
    return {
        rings: polygons.flat(),
        spatialReference: { wkid: 4326 },
    };
}

async function queryClimateFeatures(feature) {
    const cvegeo = String(feature.properties.cvegeo);
    const cachePath = path.join(cacheDirectory, `${cvegeo}.json`);

    if (existsSync(cachePath)) {
        return JSON.parse(await readFile(cachePath, 'utf8'));
    }

    const body = new URLSearchParams({
        f: 'json',
        where: '1=1',
        geometry: JSON.stringify(toArcGisPolygon(feature.geometry)),
        geometryType: 'esriGeometryPolygon',
        inSR: '4326',
        spatialRel: 'esriSpatialRelIntersects',
        outFields: 'TIPO_C',
        returnGeometry: 'true',
        outSR: '4326',
        geometryPrecision: '5',
        maxAllowableOffset: '0.00005',
    });

    let lastError;
    for (let attempt = 1; attempt <= 3; attempt++) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'content-type': 'application/x-www-form-urlencoded' },
                body,
                signal: AbortSignal.timeout(60000),
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const payload = await response.json();
            if (payload.error) throw new Error(payload.error.message ?? 'Error de ArcGIS');

            await writeFile(cachePath, JSON.stringify(payload), 'utf8');
            return payload;
        } catch (error) {
            lastError = error;
            await new Promise(resolve => setTimeout(resolve, attempt * 1000));
        }
    }

    throw new Error(`${cvegeo}: ${lastError.message}`);
}

function calculatePredominantClimate(municipality, payload) {
    const areas = new Map();

    for (const arcgisFeature of payload.features ?? []) {
        const climateFeature = arcgisToGeoJSON(arcgisFeature);
        const climate = climateFeature.properties?.TIPO_C;
        if (!climate || !climateFeature.geometry) continue;

        try {
            const overlap = intersect(featureCollection([municipality, climateFeature]));
            if (!overlap) continue;
            areas.set(climate, (areas.get(climate) ?? 0) + area(overlap));
        } catch (error) {
            throw new Error(`${municipality.properties.cvegeo}: geometría inválida (${error.message})`);
        }
    }

    const ordered = [...areas.entries()].sort((a, b) => b[1] - a[1]);
    if (!ordered.length) {
        throw new Error(`${municipality.properties.cvegeo}: sin intersección climática`);
    }

    const totalArea = ordered.reduce((sum, [, value]) => sum + value, 0);
    const [climate, predominantArea] = ordered[0];

    return {
        cvegeo: String(municipality.properties.cvegeo),
        nombre: municipality.properties.nomgeo,
        clima: climate,
        cobertura_porcentaje: Number(((predominantArea / totalArea) * 100).toFixed(2)),
        categorias: Object.fromEntries(ordered.map(([name, value]) => [
            name,
            Number(((value / totalArea) * 100).toFixed(2)),
        ])),
    };
}

async function main() {
    await mkdir(cacheDirectory, { recursive: true });
    const geojson = JSON.parse(await readFile(municipalPath, 'utf8'));
    const municipalities = geojson.features.sort((a, b) =>
        String(a.properties.cvegeo).localeCompare(String(b.properties.cvegeo))
    );
    const results = [];

    for (let index = 0; index < municipalities.length; index++) {
        const municipality = municipalities[index];
        const payload = await queryClimateFeatures(municipality);
        const result = calculatePredominantClimate(municipality, payload);
        results.push(result);
        process.stdout.write(`\rProcesados ${index + 1}/${municipalities.length}: ${result.nombre}`.padEnd(100));
    }

    const output = {
        source: {
            name: 'INEGI / Comisión Nacional del Agua',
            url: 'https://sigagis.conagua.gob.mx/ArcGIS/rest/services/Climas/MapServer/0',
            classification: 'Köppen modificada por E. García',
        },
        methodology: 'Categoría con mayor área de intersección respecto al polígono municipal.',
        generated_at: new Date().toISOString(),
        municipios: results,
    };

    await writeFile(outputPath, `${JSON.stringify(output, null, 2)}\n`, 'utf8');
    process.stdout.write(`\nArchivo generado: ${outputPath}\n`);
}

main().catch(error => {
    console.error(`\nNo fue posible generar la clasificación climática: ${error.message}`);
    process.exit(1);
});
