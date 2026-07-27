# Corrección de Recursos FORTAMUN per cápita

**Fecha:** 27 de julio de 2026

## Resumen

Se corrigió la agregación de **Recursos FORTAMUN per cápita** en los perfiles agrupados: macrorregiones, microrregiones y perfil estatal.

El indicador ya está expresado en pesos por habitante. Por esa razón, sus valores municipales no deben sumarse entre sí. La agregación regional ahora utiliza el promedio municipal.

## Problema detectado

La configuración activa es:

| Campo | Valor |
|---|---|
| Configuración | `Recursos FORTAMUN per cápita` |
| ID de configuración | `51` |
| Indicador | `Recursos devengados del FORTAMUN Per Cápita` |
| ID del indicador | `153` |
| Variable | `FORTAMUN (Dev) Per Cápita` |
| ID de variable | `319` |
| Unidad | `Pesos por habitante` |
| Tipo de visualización | `kpi` |
| Tipo de dato | `absoluto` |
| Modo configurado | `avg` |

El indicador se calcula como:

```text
FORTAMUN devengado del año t / población proyectada del año t
```

Por lo tanto, cada registro municipal ya representa un valor per cápita.

La lógica anterior de `RegionController` clasificaba todos los indicadores absolutos como sumables. En consecuencia, para 2025 se obtenía:

- Registros municipales: `217`
- Suma de valores per cápita: `195,406.8340`
- Promedio municipal: `900.4923`

La suma de valores en pesos por habitante no tiene una interpretación válida como valor regional.

## Cambio aplicado

Archivo modificado:

- `app/Http/Controllers/RegionController.php`

La lógica ahora identifica unidades que contienen:

- `por habitante`
- `per cápita`
- `per capita`

Para esas unidades se utiliza:

```php
$aggregationMethod = 'average';
```

Para el resto de los valores absolutos se conserva la suma:

```php
$aggregationMethod = 'sum';
```

Esto conserva el comportamiento de indicadores como población, viviendas o montos acumulables, pero evita sumar tasas monetarias per cápita.

## Resultado esperado

Si dos municipios tienen estos valores:

| Municipio | Valor |
|---|---:|
| Municipio A | `$100` |
| Municipio B | `$300` |

El perfil agrupado muestra:

```text
$200.00 pesos por habitante
```

Antes de la corrección mostraba incorrectamente:

```text
$400.00 pesos por habitante
```

## Consideración metodológica

El promedio municipal es la corrección compatible con la configuración existente (`benchmark_mode = avg`) y con los datos actualmente disponibles.

La agregación estadísticamente más precisa sería un promedio ponderado, calculado como:

```text
suma del FORTAMUN devengado / suma de la población proyectada
```

No se aplicó esa alternativa porque la base actual no contiene de forma consistente la población proyectada correspondiente a cada año del indicador. Usar población de otro año produciría una cifra aparentemente precisa, pero metodológicamente incorrecta.

## Prueba agregada

Se añadió una prueba en:

- `tests/Feature/RegionProfilePyramidTest.php`

La prueba verifica que:

- Dos valores `100` y `300` producen un valor regional de `200`.
- El resultado no sea `400`.

## Validaciones

- `php -l app/Http/Controllers/RegionController.php`
- `php -l tests/Feature/RegionProfilePyramidTest.php`
- `git diff --check`
- `php artisan test tests/Feature/RegionProfilePyramidTest.php`

Resultado de pruebas:

```text
8 passed
40 assertions
```

La caché de aplicación también se limpió después del cambio para evitar que se sirvieran valores regionales calculados con la lógica anterior.
