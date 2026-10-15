# Auditoría inicial del perfil municipal: Chignahuapan

**Fecha:** 14 de octubre de 2026

**Alcance:** inventario editorial y cobertura de la configuración pública de fichas para el municipio de Chignahuapan.
**No se modificaron configuraciones, catálogo ni datos.**

## Fuente de verdad

El perfil municipal se construye desde **Configuración de fichas** (`configuracion_fichas`), mediante `ConfiguracionFicha::visibleEnPerfilPublico()`.

- Incluye configuraciones con `activo = true`.
- Excluye configuraciones de tipo `scatter`.
- Si una configuración tiene variables propias, usa esas; de lo contrario, usa las variables públicas del indicador.
- El municipio piloto es Chignahuapan (ID 53).

## Hallazgos principales

### Cobertura

- Hay **27 configuraciones activas** distribuidas en cuatro dimensiones públicas.
- Se detectó una configuración excluida de la tabla de este reporte por ser `scatter`.
- Solo una configuración activa no tiene datos para Chignahuapan: **Componentes de capacitación policial** (configuración 55).
- La cobertura no es homogénea: hay datos de 2025, 2024, 2023 y 2020 dentro de una misma experiencia.

### Agrupación por dimensiones

La ficha municipal se organiza por las dimensiones del catálogo de indicadores. Esta es la estructura correcta para el perfil: Configuración de fichas controla cuáles indicadores se publican, sus variables, orden, tipo de visualización y narrativa; la dimensión del indicador define el bloque temático donde aparece.

Las configuraciones cuyo campo `seccion` es `showcase` aparecen en la dimensión real de su indicador. Al ser configuraciones locales de prueba, no requieren migración ni corrección para el piloto definitivo.

### Duplicación de contenido

| Indicador | Configuraciones activas | Observación |
| --- | --- | --- |
| Población total según sexo | 2 y 40 | Barras y treemap para el mismo indicador. |
| Porcentaje de población en pobreza | 35 y 36 | Barras y mapa para el mismo indicador. |
| Valor agregado por sector | 37, 45 y 47 | Barras y dos treemaps para el mismo indicador. |
| Contaminantes atmosféricos | 38, 48 y 50 | Treemap, barras y otro treemap del mismo indicador. |
| Recolección selectiva | 56 y 34 | KPI y serie temporal del mismo indicador. |
| Llamadas atendidas | 16 y 39 | Treemap y serie temporal del mismo indicador. |

Estas duplicaciones pueden ser útiles para un laboratorio visual, pero no para un perfil territorial de lectura guiada. En el piloto deben consolidarse como una visualización principal y, cuando aporte valor, una vista secundaria progresiva.

## Inventario recomendado para el piloto

### Mantener como anclas de lectura

| Dimensión | Configuración | Último dato de Chignahuapan | Recomendación inicial |
| --- | --- | ---: | --- |
| Demográfica y Social | Pirámide poblacional | 2025 | Mantener a ancho completo. |
| Demográfica y Social | Población total | 2025 | Mantener en hero o resumen; evitar duplicar con treemap. |
| Demográfica y Social | Índice de hacinamiento | 2025 | Mantener si la narrativa y la unidad son claras. |
| Demográfica y Social | Pobreza | 2020 | Mantener con año visible; elegir barras **o** mapa, no ambos inicialmente. |
| Económico | Remesas | 2025 | Candidata para resumen o sección económica. |
| Económico | Producción agrícola | 2025 | Mantener como dato territorial, sujeto a revisar la visualización de mapa. |
| Gobierno | FORTAMUN per cápita | 2025 | Candidata a resumen con unidad claramente visible. |
| Gobierno | Recursos federales por componente | 2025 | Mantener como barras categóricas. |
| Geográfica y Medio Ambiente | Áreas afectadas por incendios | 2024 | Mantener con contexto de temporalidad. |
| Geográfica y Medio Ambiente | Recolección selectiva | 2024 | Elegir KPI **o** tendencia como vista principal. |

### Consolidar antes del rediseño

1. **Población por sexo:** conservar la pirámide y/o la composición; no mostrar barras, treemap y pirámide como resultados equivalentes.
2. **Pobreza:** conservar una vista principal; la otra debe ir a detalle o al Banco.
3. **Valor agregado sectorial:** elegir una única vista principal. Barras son preferibles cuando el objetivo es comparar sectores; treemap cuando se prioriza composición.
4. **Emisiones:** elegir barras o treemap, no tres variantes.
5. **Llamadas atendidas:** diferenciar explícitamente composición y tendencia; si ambas son necesarias, jerarquizarlas como principal/secundaria.
6. **Recolección selectiva:** elegir KPI para el resumen o tendencia para sección, según la pregunta editorial.

### Corregir o pausar

| Configuración | Hallazgo | Acción sugerida |
| --- | --- | --- |
| 55 · Componentes de capacitación policial | Sin datos para Chignahuapan. | Desactivar en piloto o mostrar estado “Sin cobertura” solo si aporta contexto. |
| Pobreza, rezago, participación económica y emisiones | Último corte 2020. | Mantener solo con año muy visible; no presentarlos como situación actual. |
| Accidentes de tránsito por causa | El indicador es categórico, pero la configuración usa `mapa`. | Cambiar a barras horizontales como visualización principal. |
| Configuraciones `showcase` | Funcionan en el perfil real aunque su sección diga `showcase`. | Reasignar sección editorial o desactivarlas una vez definido el piloto. |

## Propuesta de resumen ejecutivo para validar

El hero ya comunica población, marginación y pobreza. El resumen ejecutivo no debe repetir los tres sin aportar lectura adicional.

Propuesta inicial para revisión editorial:

1. Población total (2025), como contexto demográfico si no queda suficientemente visible en hero.
2. Índice de hacinamiento (2025), como condición habitacional.
3. Recursos FORTAMUN per cápita (2025), como capacidad financiera.
4. Ingresos por remesas (2025), como señal económica.
5. Promedio diario de recolección selectiva (2024) o áreas afectadas por incendios (2024), como señal ambiental.
6. Una lectura de pobreza o rezago (2020), marcada claramente como último corte disponible.

La selección final requiere validación de las áreas responsables, especialmente sobre qué indicadores representan de forma legítima a cada municipio.

## Decisiones requeridas antes de implementar la Fase 1

1. Confirmar qué configuraciones permanecen activas para el piloto de Chignahuapan.
2. Definir una sola visualización principal para cada indicador duplicado.
3. Decidir si los datos 2020 se mantienen en la lectura principal o se trasladan a contexto histórico.
4. Confirmar los 4–6 indicadores del resumen ejecutivo.
5. Confirmar el orden editorial definitivo de los indicadores dentro de cada dimensión.

## Siguiente paso técnico propuesto

Con las decisiones anteriores, implementar **Fase 1** sin alterar aún toda la página:

1. Componente de metadatos visibles: fuente, año, unidad y cobertura.
2. Estado estándar de “sin dato” distinto de cero.
3. Nueva tarjeta base de perfil para una configuración piloto.
4. Aplicación inicial a dos configuraciones contrastantes: un KPI y una gráfica categórica.
