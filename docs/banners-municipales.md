# Banners Automáticos para Perfiles Municipales

## Problema

Cada municipio necesitaba una imagen de fondo (banner) de alta calidad para el hero del perfil. Buscar una imagen manualmente para cada uno de los 217 municipios es inviable.

## Arquitectura de la Solución

```
┌──────────────────┐     ┌─────────────────────┐     ┌─────────────────┐
│ Wikipedia API    │────▶│ BannerImageService  │────▶│ DB: municipios  │
│ page/summary     │     │                     │     │ banner_image_url│
│                  │     │ 1. Wikipedia (150)  │     │ banner_attr.    │
│                  │     │ 2. Picsum (60)      │     │ (JSON)          │
│                  │     │                     │     │                 │
└──────────────────┘     └─────────────────────┘     └─────────────────┘
                                  │
                          ┌───────┴───────┐
                          ▼               ▼
                     resumen_v3       perfil.blade.php
                     resumen_test    (hero credit btn)
```

## Flujo de Resolución de Imagen

El `BannerImageService::resolve(Municipio)` sigue este orden:

### 1. Wikipedia API (principal)

Se consulta la API REST de Wikipedia en español con el nombre del municipio:

```
GET https://es.wikipedia.org/api/rest_v1/page/summary/{titulo}
```

Se prueba con varios títulos hasta encontrar una página válida:

1. `Municipio_de_{Nombre}_(Puebla)`
2. `{Nombre}_(Puebla)`
3. `Municipio_de_{Nombre}`
4. `{Nombre},_Puebla`

De la respuesta JSON se extrae:

| Campo | Uso |
|---|---|
| `originalimage.source` | URL de la imagen principal del artículo |
| `content_urls.desktop.page` | URL de la página para atribución |
| `type` | Se descarta si es `disambiguation` |

**Resultado:** 150 municipios obtuvieron imagen vía Wikipedia.

**Atribución:** autor = "Wikipedia", licencia = "CC BY-SA 4.0"

### 2. Picsum (fallback)

Para municipios sin imagen en Wikipedia, se usa Picsum con una seed determinista:

```
https://picsum.photos/seed/{id}/1920/650
```

Cada municipio tiene **siempre la misma imagen** (seed = su ID numérico). No requiere atribución (fotos CC0/dominio público).

**Resultado:** 60 municipios con Picsum.

### 3. Municipios con banner existente

Los que ya tenían `banner_image_url` poblado antes del sync no se modifican.

**Resultado:** 7 ya tenían banner.

## Tabla de Resultados

| Fuente | Municipios | Atribución |
|---|---|---|
| Wikipedia | 150 | Creative Commons BY-SA 4.0 |
| Picsum (fallback) | 60 | CC0 / Dominio público |
| Ya existente | 7 | Variable |
| **Total** | **217** | |

### Cabeceras con imagen de Wikipedia

Acajete, Acateno, Acatlán, Acatzingo, Acteopan, Ahuacatlán, Ahuazotepec, Ajalpan, Albino Zertuche, Aljojuca, Altepexi, Amozoc, Aquixtla, Atempan, Atexcal, Atlixco, Atoyatempan, Atzitzihuacán, Atzitzintla, Ayotoxco de Guerrero, Calpan, Camocuautla, Cañada Morelos, Caxhuacan, Chalchicomula de Sesma, Chapulco, Chiautla, Chiautzingo, Chichiquila, Chiconcuautla, Chietla, Chignautla, Chilchotla, Coatzingo, Cohuecan, Coronango, Coxcatlán, Coyomeapan, Cuapiaxtla de Madero, Cuautinchán, Cuautlancingo, Cuetzalan del Progreso, Cuyoaco, Domingo Arenas, Esperanza, General Felipe Ángeles, Guadalupe Victoria, Honey, Huaquechula, Huatlatlauca, Huauchinango, Huehuetla, Huehuetlán el Grande, Huejotzingo, Hueyapan, Hueytamalco, Huitzilan de Serdán, Huitziltepec, Ixcamilpa de Guerrero, Ixcaquixtla, Ixtacamaxtitlán, Izúcar de Matamoros, Jalpan, Jolalpan, Jonotla, Jopala, Juan C. Bonilla, Juan Galindo, La Magdalena Tlatlauquitepec, Lafragua, Libres, Mazapiltepec de Juárez, Molcaxac, Naupan, Nauzontla, Nealtican, Nicolás Bravo, Nopalucan, Ocoyucan, Olintla, Oriental, Pahuatlán, Palmar de Bravo, Petlalcingo, Quecholac, Rafael Lara Grajales, San Andrés Cholula, San Gabriel Chilac, San Gregorio Atzompa, San Jerónimo Tecuanipan, San Jerónimo Xayacatlán, San José Chiapa, San José Miahuatlán, San Juan Atenco, San Martín Texmelucan, San Matías Tlalancaleca, San Miguel Xoxtla, San Nicolás Buenos Aires, San Nicolás de los Ranchos, San Pedro Cholula, San Pedro Yeloixtlahuaca, San Salvador el Verde, San Salvador Huixcolotla, Santa Inés Ahuatempan, Santa Isabel Cholula, Soltepec, Tecali de Herrera, Tecamachalco, Tecomatlán, Tehuacán, Tehuitzingo, Tepanco de López, Tepango de Rodríguez, Tepatlaxco de Hidalgo, Tepeaca, Tepeojuma, Tepetzintla, Tepexi de Rodríguez, Tepeyahualco, Tepeyahualco de Cuauhtémoc, Tetela de Ocampo, Teziutlán, Tianguismanalco, Tlachichuca, Tlacotepec de Benito Juárez, Tlacuilotepec, Tlahuapan, Tlaltenango, Tlaola, Tlapacoya, Tlapanalá, Tlatlauquitepec, Tochimilco, Tochtepec, Totoltepec de Guerrero, Tulcingo, Tuzamapan de Galeana, Tzicatlacoyan, Venustiano Carranza, Xayacatlán de Bravo, Xicotepec, Xicotlán, Xiutetelco, Xochiapulco, Xochitlán de Vicente Suárez, Yehualtepec, Zacapala, Zacapoaxtla, Zacatlán, Zapotitlán, Zapotitlán de Méndez, Zaragoza, Zautla, Zihuateutla, Zinacatepec

### Cabeceras con Picsum (fallback)

Ahuatlán, Ahuehuetitla, Amixtlán, Atlequizayan, Atzala, Axutla, Caltepec, Chigmecatitlán, Chila, Chila de la Sal, Chinantla, Coatepec, Cohetzala, Coyotepec, Cuautempan, Cuayuca de Andrade, Eloxochitlán, Epatlán, Francisco Z. Mena, Guadalupe, Hermenegildo Galeana, Huehuetlán el Chico, Hueytlalpan, Ixtepec, Juan N. Méndez, Los Reyes de Juárez, Mixtla, Ocotepec, Pantepec, Piaxtla, Quimixtlán, San Antonio Cañada, San Diego la Mesa Tochimiltzingo, San Felipe Teotlalcingo, San Felipe Tepatlán, San Juan Atzompa, San Martín Totoltepec, San Miguel Ixitlán, San Pablo Anicano, San Salvador el Seco, San Sebastián Tlacotepec, Santa Catarina Tlaltempan, Santiago Miahuatlán, Santo Tomás Hueyotlipan, Tenampulco, Teopantlán, Teotlalco, Tepemaxalco, Tepexco, Teteles de Avila Castillo, Tilapa, Tlanepantla, Tlaxco, Vicente Guerrero, Xochiltepec, Xochitlán Todos Santos, Yaonáhuac, Zongozotla, Zoquiapan, Zoquitlán

## Botón de Créditos

Se agregó un botón discreto en la esquina inferior derecha del hero:

```
┌───────────────────────────────────────┐
│                                       │
│                                       │
│                                       │
│          (contenido hero)             │
│                                       │
│                                  [📷] │ ← tooltip al hover
└───────────────────────────────────────┘
```

- Visible solo cuando el banner tiene atribución (`banner_attribution` no es null).
- Muestra al hacer hover: *"Foto por Wikipedia — CC BY-SA 4.0"* (tooltip Bootstrap).
- Estilo: círculo semitransparente de 32px, opacidad reducida para no distraer.
- Sin atribución para Picsum: las imágenes son CC0 y no requieren crédito.

## Estructura de Datos

### `banner_attribution` (columna JSON)

```json
{
    "author": "Wikipedia",
    "license": "CC BY-SA 4.0",
    "source_url": "https://es.wikipedia.org/wiki/Municipio_de_Puebla_(Puebla)"
}
```

Cuando es `null`, el botón de créditos no se muestra.

## Comandos

```bash
# Poblar banners para todos los municipios sin imagen
php artisan municipios:sync-banners

# Validar sin escribir en BD
php artisan municipios:sync-banners --dry-run
```

El comando omite municipios que ya tienen `banner_image_url`, permitiendo ejecutarlo múltiples veces sin sobrescribir.

## Límites y Consideraciones

1. **Disponibilidad de Wikipedia:** 150/217 municipios tienen artículo con imagen. Los 67 restantes (pequeños o sin artículo propio) usan Picsum.
2. **Resolución:** Wikipedia sirve la imagen original del artículo; no todas son 1920px de ancho. El navegador las escala con `background-size: cover`.
3. **Cache:** Las consultas a Wikipedia se cachean 7 días (usando `Cache::remember`), evitando consultas repetidas.
4. **Timeouts:** Cada petición tiene timeout de 5s. Si falla, se pasa al siguiente intento de título.
5. **User-Agent:** Se envía `PortalMunicipalPuebla/1.0` para identificación cortés.
