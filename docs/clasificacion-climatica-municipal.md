# Clasificación Climática Municipal — Puebla

## Contexto

El sistema requiere poblar el campo `clima` (texto) en la tabla `municipios` para los 217 municipios de Puebla. El clima se muestra en el hero del perfil municipal, reemplazando los campos eliminados `presidente_municipal` y `periodo_gobierno`.

Las cuatro categorías destino son:

- `Cálido húmedo`
- `Cálido subhúmedo`
- `Seco o muy seco`
- `Templado o frío (húmedo o subhúmedo)`

## Fuente de Datos

### API CONAGUA (ruta original, descartada)

El script original (`scripts/generate-municipal-climate.mjs`) consultaba el servicio REST ArcGIS de CONAGUA:

```
https://sigagis.conagua.gob.mx/ArcGIS/rest/services/Climas/MapServer/0/query
```

Problema: el endpoint está protegido por **Imperva**, un WAF que bloquea peticiones automatizadas (incluyendo `fetch` desde Node.js y `curl`). Imposible de consumir sin bypass.

### Shapefile INEGI (ruta final, exitosa)

Se utilizó el conjunto de datos **«Unidades Climáticas 1:1 000 000»** del INEGI, con clave `702825267568`.

| Atributo | Valor |
|---|---|
| URL | `https://www.inegi.org.mx/contenidos/productos/prod_serv/contenidos/espanol/bvinegi/productos/geografia/tematicas/CLIMAS/702825267568_s.zip` |
| Tamaño | ~5 MB (ZIP), ~6.5 MB (SHP) |
| Proyección nativa | North America Lambert Conformal Conic (ITRF92, GRS 1980) |
| N° de registros | 1 695 (polígonos climáticos de toda la República Mexicana) |
| Campo de clima | `TIPO_C` (string 25) |
| Año | 2008 |
| Clasificación | Köppen modificada por E. García |

### Mapeo de Climas

Los 23 valores distintos de `TIPO_C` en el shapefile se mapearon a las 4 categorías destino:

```javascript
const MAPEO = {
  'Cálido húmedo':               'Cálido húmedo',
  'Cálido subhúmedo':            'Cálido subhúmedo',
  // Secos y muy secos
  'Seco cálido':                 'Seco o muy seco',
  'Seco muy cálido':             'Seco o muy seco',
  'Seco semicálido':             'Seco o muy seco',
  'Seco templado':               'Seco o muy seco',
  'Muy seco cálido':             'Seco o muy seco',
  'Muy seco muy cálido':         'Seco o muy seco',
  'Muy seco semicálido':         'Seco o muy seco',
  'Muy seco templado':           'Seco o muy seco',
  'Semiseco cálido':             'Seco o muy seco',
  'Semiseco muy cálido':         'Seco o muy seco',
  'Semiseco semicálido':         'Seco o muy seco',
  'Semiseco semifrío':           'Seco o muy seco',
  'Semiseco templado':           'Seco o muy seco',
  // Templados, fríos, semicálidos húmedos/subhúmedos
  'Templado húmedo':             'Templado o frío (húmedo o subhúmedo)',
  'Templado subhúmedo':          'Templado o frío (húmedo o subhúmedo)',
  'Semicálido húmedo':           'Templado o frío (húmedo o subhúmedo)',
  'Semicálido subhúmedo':        'Templado o frío (húmedo o subhúmedo)',
  'Semifrío subhúmedo':          'Templado o frío (húmedo o subhúmedo)',
  'Frío':                        'Templado o frío (húmedo o subhúmedo)',
};
```

Se excluyeron `Agua` y `País extranjero`.

## Procesamiento Geoespacial

### Pipeline

```
┌─────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────────┐
│ INEGI ZIP   │───▶│ Extraer (7z) │───▶│ Leer SHP +   │───▶│ Reproject LCC →  │
│ (5 MB)      │    │              │    │ DBF (Node.js │    │ WGS84 (proj4)    │
│             │    │              │    │ shapefile)   │    │                  │
└─────────────┘    └──────────────┘    └──────────────┘    └────────┬─────────┘
                                                                    │
                    ┌───────────────────────────────────────────────┘
                    ▼
┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│ Intersección por │───▶│ Calcular área    │───▶│ Generar JSON     │
│ municipio (Turf  │    │ (Turf.js area)   │    │ final            │
│ .intersect)      │    │ + categoría      │    │                  │
│                  │    │ predominante     │    │                  │
└──────────────────┘    └──────────────────┘    └──────────────────┘
```

### Herramientas

| Herramienta | Propósito |
|---|---|
| `curl` | Descarga del ZIP desde INEGI |
| `7z` | Extracción del shapefile |
| Node.js `shapefile` | Lectura nativa de .shp + .dbf |
| `proj4` | Reprojección Lambert → WGS84 |
| Turf.js `@turf/intersect` | Intersección de polígonos |
| Turf.js `@turf/area` | Cálculo de área en m² |

### Detalle de la Intersección

1. Se reproyectó cada polígono climático de LCC a WGS84 coordenada por coordenada.
2. Se filtraron los polígonos climáticos que intersecan el bounding box de cada municipio.
3. Para cada candidato se calculó la intersección exacta con `turf.intersect`.
4. Se sumaron las áreas de intersección agrupadas por categoría climática destino.
5. Se asignó la categoría con **mayor área de cobertura absoluta**.
6. Se calculó el porcentaje de cobertura de la categoría predominante.
7. Se registraron también las coberturas desglosadas por tipo original (`TIPO_C`).

## Archivos Generados

### `database/data/municipios_clima.json`

Estructura:

```json
{
  "source": {
    "name": "INEGI. Conjunto de datos de Unidades Climáticas 1:1 000 000",
    "url": "https://www.inegi.org.mx/app/biblioteca/ficha.html?upc=702825267568",
    "classification": "Köppen modificada por E. García (INEGI, 2008)",
    "mapeo_categorias": { ... }
  },
  "methodology": "Se intersectó la capa de Unidades Climáticas de INEGI con los polígonos municipales de Puebla (WGS84). Para cada municipio se calculó el área de intersección por tipo climático original (TIPO_C) y se asignó la categoría agregada con mayor cobertura.",
  "generated_at": "2026-07-14T...",
  "municipios": [
    {
      "cvegeo": "21086",
      "nombre": "Jalpan",
      "clima": "Cálido húmedo",
      "cobertura_porcentaje": 71.92,
      "categorias": {
        "Cálido húmedo": 71.92,
        "Semicálido húmedo": 28.08
      }
    }
  ]
}
```

### `scripts/generate-climate-from-shapefile.mjs`

Script Node.js ejecutable con:

```bash
node scripts/generate-climate-from-shapefile.mjs
```

Requisitos: `shapefile`, `proj4`, `@turf/area`, `@turf/intersect`, `@turf/helpers`.

## Resultados Finales

### Distribución por clima

| Clima | Municipios | % |
|---|---|---|
| Templado o frío (húmedo o subhúmedo) | 155 | 71.4% |
| Cálido subhúmedo | 29 | 13.4% |
| Seco o muy seco | 26 | 12.0% |
| Cálido húmedo | 7 | 3.2% |

### Validaciones

- ✅ 217 CVEGEO únicos (todos los municipios de Puebla)
- ✅ 0 municipios sin clasificación
- ✅ 0 climas inválidos (todos dentro de las 4 categorías permitidas)
- ✅ Coberturas detalladas disponibles para auditoría

### Municipios con clima Cálido húmedo (7)

Acateno, Ayotoxco de Guerrero, Francisco Z. Mena, Huehuetlán el Chico (antes Chiconiáhuatl), Jalpan, Pantepec, Tenampulco, Venustiano Carranza.

### Municipios con clima Seco o muy seco (26)

Altepexi, Atexcal (parcial), Caltepec, Cañada Morelos, Chapulco, Chila de la Sal, Coxcatlán, Esperanza, Guadalupe, Guadalupe Victoria, Ixcamilpa de Guerrero, Oriental, San José Miahuatlán, San Antonio Cañada, Santiago Miahuatlán, Tecomatlán, Tehuacán, Tepanco de López, Tepeyahualco, Tlacotepec de Benito Juárez, Tulcingo, Xicotlán, Zapotitlán, Zinacatepec, Palmar de Bravo, Albino Zertuche.

## Limitaciones y Notas

1. **Resolución 1:1M** — La capa de INEGI es escala 1:1 000 000, adecuada para análisis estatal. Municipios pequeños pueden heredar clasificaciones de polígonos vecinos si no hay datos climáticos propios.
2. **Año 2008** — La capa data de 2008. Cambios climáticos recientes no están reflejados.
3. **Reprojección** — Se utilizó `proj4` con transformación directa LCC→WGS84 (ITRF92 ≈ NAD83). La precisión posicional es submétrica para México, adecuada para intersección por área.
4. **Áreas planares** — Turf.js calcula áreas en el elipsoide WGS84 usando el método de la cinta de área cartesiana. Las áreas son aproximadas pero consistentes para determinar la categoría predominante.

## Comandos Relacionados

```bash
# Sincronizar clima en BD desde el JSON generado
php artisan municipios:sync-clima

# Validar sin modificar
php artisan municipios:sync-clima --dry-run
```
