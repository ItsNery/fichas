import { readFile, writeFile } from 'node:fs/promises';
import { open } from 'shapefile';
import proj4 from 'proj4';
import area from '@turf/area';
import intersect from '@turf/intersect';
import { featureCollection } from '@turf/helpers';

proj4.defs('INEGI_LCC', '+proj=lcc +lat_1=17.5 +lat_2=29.5 +lat_0=12.0 +lon_0=-102.0 +x_0=2500000 +y_0=0 +datum=NAD83 +units=m +no_defs');
const toWgs84 = proj4('INEGI_LCC', 'WGS84');

const MAPEO = {
  'Cálido húmedo': 'Cálido húmedo',
  'Cálido subhúmedo': 'Cálido subhúmedo',
  'Seco cálido': 'Seco o muy seco',
  'Seco muy cálido': 'Seco o muy seco',
  'Seco semicálido': 'Seco o muy seco',
  'Seco templado': 'Seco o muy seco',
  'Muy seco cálido': 'Seco o muy seco',
  'Muy seco muy cálido': 'Seco o muy seco',
  'Muy seco semicálido': 'Seco o muy seco',
  'Muy seco templado': 'Seco o muy seco',
  'Semiseco cálido': 'Seco o muy seco',
  'Semiseco muy cálido': 'Seco o muy seco',
  'Semiseco semicálido': 'Seco o muy seco',
  'Semiseco semifrío': 'Seco o muy seco',
  'Semiseco templado': 'Seco o muy seco',
  'Templado húmedo': 'Templado o frío (húmedo o subhúmedo)',
  'Templado subhúmedo': 'Templado o frío (húmedo o subhúmedo)',
  'Semicálido húmedo': 'Templado o frío (húmedo o subhúmedo)',
  'Semicálido subhúmedo': 'Templado o frío (húmedo o subhúmedo)',
  'Semifrío subhúmedo': 'Templado o frío (húmedo o subhúmedo)',
  'Frío': 'Templado o frío (húmedo o subhúmedo)',
};

const CLIMAS_EXCLUIDOS = new Set(['Agua', 'País extranjero']);

function reprojectCoords(coords) {
  if (typeof coords[0] === 'number') {
    const [x, y] = toWgs84.forward(coords);
    return [x, y];
  }
  return coords.map(c => reprojectCoords(c));
}

function reprojectGeometry(geom) {
  if (!geom) return geom;
  if (geom.type === 'Polygon') {
    return { ...geom, coordinates: geom.coordinates.map(ring => ring.map(c => reprojectCoords(c))) };
  }
  if (geom.type === 'MultiPolygon') {
    return { ...geom, coordinates: geom.coordinates.map(poly => poly.map(ring => ring.map(c => reprojectCoords(c)))) };
  }
  return geom;
}

function bbox(feature) {
  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
  const walk = (coords) => {
    if (typeof coords[0] === 'number') {
      const [x, y] = coords;
      if (x < minX) minX = x;
      if (y < minY) minY = y;
      if (x > maxX) maxX = x;
      if (y > maxY) maxY = y;
    } else coords.forEach(walk);
  };
  if (feature.geometry) walk(feature.geometry.coordinates);
  return [minX, minY, maxX, maxY];
}

function intersectsBBox(a, b) {
  return !(a[2] < b[0] || a[0] > b[2] || a[3] < b[1] || a[1] > b[3]);
}

const root = process.cwd();
const shpDir = 'C:\\Users\\NERY~1.POZ\\AppData\\Local\\Temp\\opencode\\inegi_clima';

async function main() {
  console.log('Reading shapefile...');
  const source = await open(`${shpDir}\\unidadesClimaticas.shp`, `${shpDir}\\unidadesClimaticas.dbf`);
  const climateFeatures = [];
  while (true) {
    const result = await source.read();
    if (result.done) break;
    climateFeatures.push(result.value);
  }
  console.log(`  ${climateFeatures.length} climate features loaded`);

  console.log('Reprojecting climate features to WGS84...');
  const reprojected = climateFeatures
    .map(f => ({
      type: 'Feature',
      properties: { TIPO_C: f.properties.TIPO_C },
      geometry: reprojectGeometry(f.geometry),
    }))
    .filter(f => f.geometry && !CLIMAS_EXCLUIDOS.has(f.properties.TIPO_C));
  console.log(`  ${reprojected.length} features after filtering`);

  console.log('Reading municipal GeoJSON...');
  const municipalGeoJSON = JSON.parse(await readFile(`${root}/public/geojson/puebla_municipios_wgs84.geojson`, 'utf8'));
  const municipalities = municipalGeoJSON.features;
  console.log(`  ${municipalities.length} municipalities loaded`);

  const climateBBoxes = reprojected.map(f => ({ bbox: bbox(f), feature: f }));

  const results = [];
  for (let idx = 0; idx < municipalities.length; idx++) {
    const muni = municipalities[idx];
    const cvegeo = String(muni.properties.cvegeo);
    const nombre = muni.properties.nomgeo;
    const muniBBox = bbox(muni);

    const candidates = climateBBoxes
      .filter(c => intersectsBBox(muniBBox, c.bbox))
      .map(c => c.feature);

    const areas = new Map();
    for (const climate of candidates) {
      try {
        const overlap = intersect(featureCollection([muni, climate]));
        if (!overlap) continue;
        const a = area(overlap);
        const cat = MAPEO[climate.properties.TIPO_C];
        if (cat) areas.set(cat, (areas.get(cat) ?? 0) + a);
      } catch {}
    }

    const ordered = [...areas.entries()].sort((a, b) => b[1] - a[1]);
    const totalArea = ordered.reduce((s, [, v]) => s + v, 0);

    const categorias = {};
    for (const climate of candidates) {
      try {
        const overlap = intersect(featureCollection([muni, climate]));
        if (!overlap) continue;
        const a = area(overlap);
        const tipo = climate.properties.TIPO_C;
        categorias[tipo] = (categorias[tipo] ?? 0) + a;
      } catch {}
    }
    const catTotal = Object.values(categorias).reduce((s, v) => s + v, 0);
    const categoriasPct = Object.fromEntries(
      Object.entries(categorias).map(([k, v]) => [k, Number(((v / catTotal) * 100).toFixed(2))])
    );

    const [predominantClimate, predominantArea] = ordered[0] ?? [null, 0];

    results.push({
      cvegeo,
      nombre,
      clima: predominantClimate ?? 'Sin clasificación',
      cobertura_porcentaje: predominantClimate ? Number(((predominantArea / totalArea) * 100).toFixed(2)) : 0,
      categorias: categoriasPct,
    });

    process.stdout.write(`\r${idx + 1}/${municipalities.length}: ${nombre.padEnd(35)} → ${(predominantClimate ?? '?').padEnd(20)} ${predominantClimate ? `${((predominantArea / totalArea) * 100).toFixed(1)}%` : ''}`);
  }

  const output = {
    source: {
      name: 'INEGI. Conjunto de datos de Unidades Climáticas 1:1 000 000',
      url: 'https://www.inegi.org.mx/app/biblioteca/ficha.html?upc=702825267568',
      classification: 'Köppen modificada por E. García (INEGI, 2008)',
      mapeo_categorias: MAPEO,
    },
    methodology: 'Se intersectó la capa de Unidades Climáticas de INEGI con los polígonos municipales de Puebla (WGS84). Para cada municipio se calculó el área de intersección por tipo climático original (TIPO_C) y se asignó la categoría agregada con mayor cobertura.',
    generated_at: new Date().toISOString(),
    municipios: results,
  };

  await writeFile(`${root}/database/data/municipios_clima.json`, JSON.stringify(output, null, 2) + '\n', 'utf8');
  console.log(`\n\nArchivo generado: ${root}/database/data/municipios_clima.json`);
}

main().catch(err => {
  console.error(`\nError: ${err.message}`);
  process.exit(1);
});
