# Plan de Implementación: Proyecciones de Población

## 1. Objetivo

Incorporar las proyecciones municipales de población de 1990 a 2040 como datos históricos reutilizables dentro del portal.

Los datos se cargarán inicialmente como un catálogo interno y permanecerán ocultos para el público. Su función será servir como fuente para pirámides poblacionales, denominadores per cápita y futuros indicadores demográficos.

## 2. Fuente

Directorio:

```text
public/documentos/Proyecciones19902040Puebla
```

Archivo principal:

```text
1_Grupo_Quinq_21_PU.xlsx
```

Características confirmadas:

- 217 municipios.
- 51 años: 1990-2040.
- Dos registros por municipio y año: `HOMBRES` y `MUJERES`.
- 22,134 registros de datos más encabezados.
- Población proyectada a mitad de año.
- Identificador municipal `CLAVE`, compatible con `CVEGEO`.
- 18 grupos de edad: `0-4` hasta `80-84` y `85 años y más`.
- Campo `POB_TOTAL` para cada sexo.

Columnas relevantes:

```text
CLAVE
CLAVE_ENT
NOM_ENT
NOM_MUN
SEXO
AÑO
POB_00_04
POB_05_09
POB_10_14
POB_15_19
POB_20_24
POB_25_29
POB_30_34
POB_35_39
POB_40_44
POB_45_49
POB_50_54
POB_55_59
POB_60_64
POB_65_69
POB_70_74
POB_75_79
POB_80_84
POB_85_mm
POB_TOTAL
```

El archivo `4_Descriptor_BD_21_PU.xlsx` documenta la estructura y debe conservarse como referencia metodológica.

## 3. Ubicación En El Catálogo

### Restricción obligatoria

El nuevo indicador debe integrarse en la dimensión existente:

```text
Demográfica y Social
```

La dimensión corresponde actualmente al registro con ID `1`. No se debe crear una dimensión nueva para las proyecciones.

### Nueva temática oculta

```text
Proyecciones demográficas
```

Configuración:

```text
dimension_id = 1
visible_en_ficha = false
```

### Nuevo indicador oculto

```text
Proyecciones de población municipal 1990-2040
```

Configuración recomendada:

```text
tipo_dato = absoluto
es_complejo = false
visible_en_ficha = false
solo_resumen = false
```

La descripción debe indicar que la fuente corresponde a reconstrucciones y proyecciones de población municipal a mitad de año para 1990-2040.

## 4. Variables

Se crearán variables con nombres técnicos estables e idempotentes.

### Grupos quinquenales

Por cada grupo de edad se crearán dos variables:

```text
proyeccion_poblacion_hombres_00_04
proyeccion_poblacion_mujeres_00_04
```

El patrón se repetirá hasta:

```text
proyeccion_poblacion_hombres_85_mas
proyeccion_poblacion_mujeres_85_mas
```

### Totales por sexo

```text
proyeccion_poblacion_hombres_total
proyeccion_poblacion_mujeres_total
```

### Total construido

```text
proyeccion_poblacion_total
```

Todas las variables tendrán:

```text
unidad_medida = Habitantes
visible_en_ficha = false
es_kpi = false
es_destacada = false
```

No se reutilizarán automáticamente las variables públicas actuales de la pirámide. Sus grupos de edad no coinciden exactamente: el portal actual separa edades superiores a 85 años, mientras que la fuente usa una sola categoría `85 años y más`.

## 5. Lectura Y Transformación

El Excel está en formato ancho y el portal usa registros largos.

### Formato de origen

```text
CLAVE | SEXO | AÑO | POB_00_04 | ... | POB_85_mm | POB_TOTAL
```

### Formato interno esperado

```text
municipio_cvegeo | anio | variable_tecnico | valor
```

Ejemplo:

```text
21001 | 1990 | proyeccion_poblacion_hombres_00_04 | 3859
21001 | 1990 | proyeccion_poblacion_hombres_05_09 | 3549
21001 | 1990 | proyeccion_poblacion_hombres_total | 21338
21001 | 1990 | proyeccion_poblacion_mujeres_00_04 | 3746
21001 | 1990 | proyeccion_poblacion_mujeres_total | 21987
```

Reglas:

1. `CLAVE` se relaciona con `Municipio.cvegeo`.
2. `SEXO = HOMBRES` se asigna a variables masculinas.
3. `SEXO = MUJERES` se asigna a variables femeninas.
4. `AÑO` se guarda como `anio`.
5. Cada columna `POB_*` se asigna a su variable correspondiente.
6. Valores vacíos se registran como dato faltante, no como cero.
7. Los valores numéricos se conservan sin redondeo innecesario.
8. El identificador `CVEGEO` se trata como texto durante la lectura para no perder ceros iniciales.

## 6. Importador

No se debe adaptar manualmente el Excel ni cargarlo directamente con el importador genérico.

Se creará un proceso específico, preferentemente mediante un comando controlado:

```text
app/Console/Commands/ImportarProyeccionesPoblacion.php
```

La lógica reutilizable puede vivir en:

```text
app/Services/ProyeccionesPoblacionService.php
```

Responsabilidades:

- Leer el archivo mediante PhpSpreadsheet/Laravel Excel.
- Crear o actualizar el catálogo oculto.
- Transformar el formato ancho a largo.
- Validar municipios, años, sexo y valores.
- Crear el lote de datos.
- Importar por chunks y lotes.
- Generar un resumen de resultados.

El proceso debe ser idempotente. Ejecutarlo dos veces no debe crear variables duplicadas ni registros duplicados.

## 7. Flujo Gobernado De Datos

La importación debe respetar el flujo existente de `LoteDatos`:

1. Leer y validar el archivo.
2. Crear un lote en estado borrador.
3. Guardar las filas normalizadas en las tablas del lote.
4. Mostrar filas válidas, inválidas, inserciones y actualizaciones.
5. Enviar el lote a revisión.
6. Aprobar el lote.
7. Publicar los datos internamente.

Las variables continuarán ocultas aunque el lote sea aprobado.

Por el volumen estimado, el proceso debe usar:

- Lectura por chunks.
- Inserciones por lotes.
- Clave única `municipio_id + variable_id + anio`.
- Evitar `updateOrCreate` fila por fila cuando sea posible.
- Job en cola si el tiempo de ejecución supera el límite HTTP.

## 8. Variable Total Construida

Después de importar los totales por sexo, se generará:

```text
proyeccion_poblacion_total =
proyeccion_poblacion_hombres_total +
proyeccion_poblacion_mujeres_total
```

Se utilizará el mecanismo existente de variables construidas:

```text
formula_tipo = sumatoria
```

Esto permite que otros indicadores utilicen la población total sin duplicar datos.

## 9. Validaciones De Calidad

El importador debe producir un reporte con:

- Municipios encontrados.
- Municipios no encontrados.
- Años mínimo y máximo.
- Filas leídas.
- Filas válidas.
- Filas con errores.
- Duplicados.
- Valores negativos.
- Valores faltantes.

Validaciones matemáticas:

- Cada municipio debe tener 51 años.
- Cada municipio y año debe tener hombres y mujeres.
- La suma de hombres y mujeres debe coincidir con la población total construida.
- La suma de los grupos quinquenales debe compararse con `POB_TOTAL`.
- Las diferencias por redondeo deben reportarse, no ocultarse.

## 10. Uso En FORTAMUN

Una vez disponible `proyeccion_poblacion_total`, se podrá crear una variable construida para FORTAMUN:

```text
FORTAMUN per cápita proyectado =
FORTAMUN DEVENGADO × 1000 /
proyeccion_poblacion_total
```

El factor `1000` se debe a que `FORTAMUN DEVENGADO` está expresado en miles de pesos.

Para las vistas agrupadas, la agregación correcta será:

```text
suma de FORTAMUN DEVENGADO
/
suma de proyeccion_poblacion_total
```

No debe usarse un promedio simple de valores per cápita cuando se necesite un resultado regional ponderado.

Para soportarlo correctamente habrá que añadir una estrategia de agregación como:

```text
weighted_ratio
```

La configuración de la variable construida deberá conservar los IDs del numerador, denominador y multiplicador.

## 11. Usos Posteriores En El Portal

### Población

- Pirámides por año.
- Evolución 1990-2040.
- Comparación observada/proyectada.
- Proyección de población total.

### Demografía

- Población infantil.
- Población en edad laboral.
- Adultos mayores.
- Índice de envejecimiento.
- Razón de dependencia.
- Crecimiento poblacional.

### Indicadores per cápita

- FORTAMUN por habitante.
- FAISMUN por habitante.
- Gasto público por habitante.
- Accidentes por mil habitantes.
- Residuos por habitante.
- Servicios de salud por habitante.

## 12. Pruebas

Se crearán pruebas para:

- Reconocimiento de encabezados.
- Transformación de hombres y mujeres.
- Mapeo de `CVEGEO`.
- Conversión de años.
- Rechazo de municipios inválidos.
- Rechazo de sexos desconocidos.
- Detección de duplicados.
- Conteo de 217 municipios.
- Conteo de 51 años.
- Generación del total poblacional.
- Cálculo FORTAMUN per cápita.
- Agregación regional ponderada.

Archivos sugeridos:

```text
tests/Unit/ProyeccionesPoblacionServiceTest.php
tests/Feature/ProyeccionesPoblacionImportTest.php
```

## 13. Orden De Ejecución

1. Crear pruebas de lectura y transformación.
2. Crear la temática dentro de la dimensión `1`.
3. Crear el indicador oculto.
4. Crear las variables ocultas.
5. Implementar el importador por chunks.
6. Validar el archivo completo.
7. Crear y aprobar el lote.
8. Generar la población total construida.
9. Validar totales y cobertura.
10. Integrar la población como denominador de FORTAMUN.
11. Implementar agregación regional ponderada.
12. Crear visualizaciones públicas en una fase posterior.

## 14. Criterio De Aceptación

La primera etapa se considerará terminada cuando:

- El indicador exista dentro de `Demográfica y Social`.
- La temática, el indicador y sus variables estén ocultos.
- Los 217 municipios estén identificados correctamente.
- Los años 1990-2040 estén disponibles.
- Los grupos de edad y sexo estén importados.
- La población total construida sea reproducible.
- El lote haya sido validado y aprobado.
- Ninguna ficha pública muestre todavía las variables internas.

## 15. Ejecución Realizada

El plan se ejecutó con el archivo local y quedó aplicado en la base de datos.

Catálogo creado:

- Dimensión: `Demográfica y Social`, ID `1`.
- Temática oculta: `Proyecciones demográficas`, ID `36`.
- Indicador oculto: `Proyecciones de población municipal 1990-2040`, ID `158`.
- Variables creadas: `39`.
- Todas las variables de proyección permanecen ocultas.

Importación:

- Lote histórico aprobado: `#10`.
- Registros fuente importados: `420,546`.
- Filas nuevas: `420,546`.
- Filas actualizadas: `0`.
- Años cubiertos: `1990-2040`.
- Municipios cubiertos: `217`.

Variables construidas:

- Población proyectada total: ID `361`.
- Registros generados: `11,067`.
- FORTAMUN per cápita proyectado: ID `362`.
- Fórmula: `FORTAMUN DEVENGADO × 1000 / población proyectada total`.
- Registros generados: `868`.

La configuración KPI `Recursos FORTAMUN per cápita` fue actualizada para usar la variable proyectada. La variable histórica anterior se conservó para no romper otras configuraciones.

La agregación regional de variables construidas por división ahora calcula el cociente de sumas. En el perfil Estatal, el valor de 2025 quedó en aproximadamente `$887.84` pesos por habitante, calculado como:

```text
FORTAMUN devengado estatal × 1000 / población proyectada estatal
```

Validaciones ejecutadas:

- La suma de hombres y mujeres coincide con la población total en 2025 para los 217 municipios.
- El indicador y la temática permanecen ocultos.
- El perfil Estatal responde correctamente.
- `RegionProfilePyramidTest`: `8 passed`, `40 assertions`.

Comando reproducible:

```bash
php artisan proyecciones:importar --aprobar
```

Para generar únicamente la integración FORTAMUN después de que las proyecciones ya estén importadas:

```bash
php artisan proyecciones:importar --solo-fortamun --fortamun
```
