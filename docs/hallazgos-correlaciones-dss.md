# Hallazgos de correlaciones para el DSS

Documento de trabajo para registrar correlaciones exploratorias entre variables municipales y sus implicaciones para el análisis territorial. Los resultados describen asociaciones lineales entre municipios; no prueban causalidad.

## Criterio de cálculo

- Se utiliza el último año común disponible para ambas variables.
- Se emparejan únicamente municipios con datos válidos en las dos variables.
- Pearson mide asociación lineal.
- Spearman se utiliza como control de robustez frente a valores atípicos y relaciones no lineales.
- La unidad de observación es el municipio dentro del año analizado.

## 1. Tasa de mortalidad e índice de motorización

### Pregunta analítica

Analizar si una mayor motorización se asocia con un incremento en la mortalidad, como aproximación exploratoria a posibles externalidades del tráfico.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Tasa de mortalidad | ID 71 |
| Índice de motorización | ID 318 |
| Año analizado | 2024 |
| Municipios coincidentes | 217 |
| Pearson | **-0.154** |
| Spearman | -0.125 |

### Hallazgo

La asociación observada es **inversa y débil**. En 2024, los municipios con mayor índice de motorización no muestran una mayor tasa general de mortalidad; la relación es ligeramente negativa.

La relación tampoco es estable entre años:

| Año | Pearson |
|---|---:|
| 2021 | 0.123 |
| 2022 | 0.049 |
| 2023 | -0.096 |
| 2024 | -0.154 |

### Precauciones

- La variable de mortalidad es general y no identifica defunciones por accidentes de tránsito.
- Para probar la hipótesis sustantiva conviene utilizar mortalidad específica por accidentes viales.
- Para estudiar el crecimiento del parque vehicular sería mejor correlacionar el cambio del índice de motorización con mortalidad contemporánea o con un rezago temporal.
- El resultado es descriptivo y no implica causalidad.

## 2. Tasa de natalidad y mortalidad infantil por edad de la madre

### Pregunta analítica

Analizar la relación entre la tasa de natalidad y la mortalidad infantil según edad de la madre, para explorar si la mortalidad infantil se asocia con la natalidad territorial.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Tasa de natalidad | ID 54 |
| Tasa de mortalidad infantil por edad de la madre | ID 73 |
| Año analizado | 2024 |
| Municipios coincidentes | 100 |
| Pearson | **-0.382** |
| Spearman | -0.405 |

### Hallazgo

En 2024 se observa una asociación **inversa débil, cercana a moderada**: los municipios con mayor tasa de natalidad tienden a presentar una menor tasa de mortalidad infantil en este corte.

La relación cambia de intensidad entre años:

| Año | Municipios | Pearson |
|---|---:|---:|
| 2021 | 217 | -0.173 |
| 2022 | 217 | -0.106 |
| 2023 | 217 | -0.006 |
| 2024 | 100 | -0.382 |

### Precauciones

- La cobertura de 2024 baja a 100 municipios, por lo que el resultado requiere cautela.
- La variable disponible es agregada; no permite saber qué grupo específico de edad materna explica la relación.
- No se puede concluir que la natalidad cause una menor mortalidad infantil ni al contrario.
- Conviene repetir el análisis con las categorías desagregadas por edad materna y revisar la cobertura por grupo.

## 3. Población derechohabiente y población en localidades rurales

### Pregunta analítica

Explorar si la población derechohabiente a servicios de salud se relaciona con la concentración de población en localidades rurales.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Población derechohabiente a los servicios de salud | ID 66 |
| Población en localidades rurales | ID 50 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.461** |
| Spearman | 0.711 |
| IC 95% aproximado de Pearson | [0.349, 0.559] |

### Hallazgo

Pearson muestra una asociación lineal **positiva moderada**. Sin embargo, Spearman es considerablemente mayor, lo que indica que la relación monotónica es más fuerte que la relación lineal.

### Precaución principal

Ambas variables están expresadas como **número de habitantes**, no como proporciones. El resultado probablemente está influido por el tamaño total de cada municipio: los municipios más poblados tienden a tener más población derechohabiente y también más población rural en términos absolutos.

Por ello, este cruce no debe interpretarse como evidencia de que la ruralidad produzca mayor derechohabiencia. Para un análisis DSS más útil se recomienda construir indicadores relativos, por ejemplo:

- Población derechohabiente / población total.
- Población en localidades rurales / población total.

Después convendría recalcular Pearson y Spearman con esas proporciones, además de revisar si la cobertura de servicios difiere entre municipios rurales y urbanos.

## 4. Tasa de mortalidad y tasa de personal médico

### Pregunta analítica

Relacionar la disponibilidad de personal médico con la mortalidad general para identificar territorios donde la capacidad instalada del sistema de salud podría ser insuficiente frente a la demanda de atención.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Tasa de mortalidad | ID 71 |
| Tasa de personal médico | ID 75 |
| Año analizado | 2024 |
| Municipios coincidentes | 217 |
| Pearson | **0.101** |
| Spearman | 0.153 |
| IC 95% aproximado de Pearson | [-0.034, 0.233] |

### Hallazgo

En 2024 se observa una asociación **positiva muy débil** entre la tasa de personal médico y la tasa general de mortalidad. La disponibilidad de más personal médico no aparece asociada con una reducción clara de la mortalidad municipal.

La relación fue débil en todos los años disponibles:

| Año | Municipios | Pearson |
|---|---:|---:|
| 2021 | 217 | 0.222 |
| 2022 | 217 | 0.187 |
| 2023 | 217 | 0.166 |
| 2024 | 217 | 0.101 |

### Interpretación para el DSS

El resultado no debe interpretarse como evidencia de que una mayor disponibilidad de personal médico incremente la mortalidad. Es posible que los municipios con mayor mortalidad también concentren más personal médico porque enfrentan una mayor demanda, tienen población más envejecida, reciben pacientes de otros municipios o cuentan con mejores registros de defunciones.

La correlación simple no permite medir si la capacidad instalada es suficiente. Para aproximar esa pregunta convendría incorporar:

- Mortalidad por causas evitables o sensibles a la atención médica.
- Personal médico por tipo de institución y nivel de atención.
- Camas, unidades médicas, quirófanos y servicios de urgencias.
- Tiempo de traslado y accesibilidad territorial.
- Estructura por edad, pobreza, ruralidad y condiciones de salud.
- Rezagos temporales entre disponibilidad de personal y mortalidad.

### Precauciones

- La mortalidad utilizada es general, no mortalidad evitable ni mortalidad por causas específicas.
- El análisis es ecológico y transversal; no evalúa la experiencia individual de atención.
- El IC 95% aproximado incluye valores cercanos a cero, por lo que la asociación lineal es compatible con una relación prácticamente nula.
- La diferencia entre Pearson y Spearman es pequeña y no cambia la lectura de asociación débil.

## 5. Razón de mortalidad materna y tasa de personal médico

### Pregunta analítica

Analizar si una mayor disponibilidad de personal médico se asocia con la mortalidad materna a nivel municipal o regional.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Razón de mortalidad materna | ID 76 |
| Tasa de personal médico | ID 75 |
| Año más reciente común | 2024 |
| Municipios coincidentes con datos válidos | 16 |
| Pearson | **-0.551** |
| Spearman | -0.624 |
| IC 95% aproximado de Pearson | [-0.828, -0.054] |

### Hallazgo

En 2024 aparece una asociación **inversa moderada**, pero el resultado debe considerarse exploratorio y de baja confiabilidad debido a que solo hay 16 municipios con datos válidos en ambas variables.

En años anteriores, con cobertura completa, la relación fue débil o prácticamente nula:

| Año | Municipios | Pearson |
|---|---:|---:|
| 2021 | 217 | 0.216 |
| 2022 | 217 | 0.035 |
| 2023 | 217 | 0.076 |
| 2024 | 16 | -0.551 |

El cambio de signo entre 2023 y 2024 coincide con una reducción muy importante de la muestra, por lo que no es suficiente para afirmar que una mayor disponibilidad de personal médico reduzca la mortalidad materna.

### Interpretación para el DSS

El resultado de 2024 podría sugerir que, entre los municipios con información disponible, una mayor tasa de personal médico coincide con una menor razón de mortalidad materna. Sin embargo, también puede reflejar selección de municipios, diferencias en el registro de eventos poco frecuentes, concentración de servicios especializados o características territoriales no controladas.

Además, la variable disponible mide **mortalidad materna**, no mortalidad infantil. Para analizar ambas dimensiones se requerirían variables separadas y una especificación explícita del resultado de interés.

Para fortalecer el análisis convendría:

- Aumentar la cobertura de datos de mortalidad materna.
- Analizar varios años mediante un panel municipal, no solo un corte transversal.
- Incorporar nacidos vivos o población femenina en edad reproductiva como denominador y revisar la definición exacta de la razón.
- Distinguir personal médico general, obstétrico, enfermería y atención especializada.
- Incorporar distancia a hospitales, urgencias obstétricas, pobreza, ruralidad y edad materna.
- Tratar con cautela los municipios con cero o pocos eventos, porque una razón puede ser inestable.

### Precauciones

- La muestra de 16 municipios en 2024 es insuficiente para una conclusión territorial generalizable.
- El intervalo de confianza aproximado es amplio y llega cerca de cero.
- El análisis es ecológico y no prueba que la disponibilidad de personal médico cause cambios en la mortalidad materna.
- La asociación puede estar afectada por subregistro, derivación de pacientes y concentración regional de servicios.

## 6. Pobreza y rezago educativo

### Pregunta analítica

Examinar la asociación entre la población en situación de pobreza y el rezago educativo para identificar patrones territoriales donde ambas condiciones se presentan de manera concurrente.

### Opción principal: pobreza y rezago educativo

| Dato | Resultado |
|---|---:|
| Población en situación de pobreza | ID 84 |
| Población con rezago educativo | ID 65 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.680** |
| Spearman | 0.637 |
| IC 95% aproximado de Pearson | [0.601, 0.746] |

### Hallazgo

Se observa una asociación **positiva fuerte**: los municipios con mayor porcentaje de población en situación de pobreza tienden a presentar también mayor porcentaje de población con rezago educativo.

Este es el cruce más directo para la pregunta planteada. Puede ayudar a identificar territorios donde ambas condiciones se acumulan y donde las políticas de reducción de pobreza podrían coordinarse con intervenciones educativas.

### Segunda opción: pobreza y grado promedio de escolaridad

| Dato | Resultado |
|---|---:|
| Población en situación de pobreza | ID 84 |
| Grado promedio de escolaridad | ID 64 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **-0.724** |
| Spearman | -0.648 |
| IC 95% aproximado de Pearson | [-0.782, -0.654] |

La asociación es **inversa fuerte**: una mayor proporción de pobreza tiende a coincidir con menor escolaridad promedio. Esta alternativa es útil para medir el gradiente educativo, aunque no representa el rezago educativo de manera tan directa como la primera opción.

### Precauciones

- Las variables provienen del mismo corte censal de 2020; no permiten evaluar evolución temporal.
- La asociación territorial no demuestra que el rezago educativo cause pobreza ni al contrario.
- Conviene revisar municipios atípicos y cruzar los resultados con ruralidad, población indígena, empleo y accesibilidad educativa.
- Pobreza y rezago pueden compartir determinantes estructurales, por lo que el resultado debe interpretarse como concentración territorial, no como efecto causal.

## 7. Pobreza extrema y rezago educativo

### Pregunta analítica

Analizar la asociación entre la pobreza extrema y el rezago educativo para identificar territorios donde ambas condiciones presentan una mayor concentración.

### Opción principal: pobreza extrema y rezago educativo

| Dato | Resultado |
|---|---:|
| Población en situación de pobreza extrema | ID 85 |
| Población con rezago educativo | ID 65 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.725** |
| Spearman | 0.701 |
| IC 95% aproximado de Pearson | [0.657, 0.779] |

### Hallazgo

Se observa una asociación **positiva fuerte**: los municipios con mayor pobreza extrema tienden a concentrar también mayores porcentajes de población con rezago educativo.

La relación es ligeramente más intensa que la obtenida entre pobreza general y rezago educativo, lo que sugiere que el rezago educativo se concentra con mayor claridad en los territorios con privaciones económicas más severas.

### Segunda opción: pobreza extrema y alfabetismo

| Dato | Resultado |
|---|---:|
| Población en situación de pobreza extrema | ID 85 |
| Personas de 15 años y más alfabetas | ID 63 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **-0.742** |
| Spearman | -0.694 |
| IC 95% aproximado de Pearson | [-0.799, -0.675] |

La asociación es **inversa fuerte**: a mayor pobreza extrema tiende a observarse un menor porcentaje de personas alfabetas. Esta alternativa es clara para comunicar la brecha educativa, aunque alfabetismo es una dimensión más específica que rezago educativo.

### Precauciones

- Pobreza extrema y rezago educativo pueden estar relacionados por factores comunes como ruralidad, edad, empleo y acceso a servicios.
- Los resultados identifican concentración territorial, pero no permiten establecer causalidad.
- Para priorización de política pública conviene combinar ambos porcentajes con el número absoluto de personas afectadas.
- Se recomienda actualizar el análisis cuando exista un nuevo corte comparable y conservar la misma definición metodológica.

## 8. Accesibilidad educativa, alfabetismo y escolaridad

### Pregunta analítica

Analizar la relación entre el tiempo promedio de traslado a la escuela y el porcentaje de personas alfabetas de 15 años y más para identificar posibles patrones de accesibilidad educativa.

### Opción principal: traslado de 1 a 2 horas y alfabetismo

| Dato | Resultado |
|---|---:|
| Personas de 15 años y más alfabetas | ID 63 |
| Traslado a la escuela de 1 hora y hasta 2 horas | ID 206 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.016** |
| Spearman | 0.068 |
| IC 95% aproximado de Pearson | [-0.118, 0.149] |

### Hallazgo

No se observa una asociación lineal relevante entre el porcentaje de personas alfabetas y el porcentaje de población cuyo traslado a la escuela tarda de una a dos horas. La relación es prácticamente nula y tampoco cambia sustancialmente al usar Spearman.

Este resultado no significa que la accesibilidad educativa carezca de importancia. La categoría de una a dos horas es solo un tramo de la distribución y no representa por sí sola el tiempo total de traslado ni la dificultad de acceso.

### Segunda opción: alfabetismo y grado promedio de escolaridad

| Dato | Resultado |
|---|---:|
| Personas de 15 años y más alfabetas | ID 63 |
| Grado promedio de escolaridad | ID 64 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.821** |
| Spearman | 0.875 |
| IC 95% aproximado de Pearson | [0.774, 0.859] |

La asociación es **positiva fuerte**: los municipios con mayor alfabetismo tienden a presentar mayor escolaridad promedio. Es una alternativa descriptivamente sólida, pero las dos variables miden dimensiones educativas muy cercanas; sirve más como validación de consistencia que como evidencia de accesibilidad.

### Precauciones

- Para estudiar accesibilidad conviene construir un indicador compuesto con todos los tramos de traslado, dando mayor peso a los recorridos largos.
- También sería útil analizar la proporción que tarda más de dos horas y combinarla con ruralidad, dispersión poblacional y disponibilidad de planteles.
- El cruce alfabetismo-escolaridad tiene solapamiento conceptual y no debe interpretarse como una relación causal.

## 9. Acceso a computadora y rezago social

### Pregunta analítica

Analizar la relación entre las viviendas particulares habitadas que disponen de computadora y el grado de rezago social para explorar el acceso a tecnologías de la información y las condiciones de desarrollo social.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Rezago social | ID 100 |
| Viviendas con computadora | ID 82 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.188** |
| Spearman | 0.505 |
| IC 95% aproximado de Pearson | [0.056, 0.313] |

### Hallazgo

Pearson muestra una asociación lineal **débil y positiva**, mientras que Spearman identifica una relación monotónica **moderada**. La diferencia indica que la relación puede no ser lineal y que el ordenamiento territorial es más consistente que la distancia numérica entre los valores.

### Precauciones metodológicas

- La variable de computadora está expresada como número absoluto de viviendas, por lo que puede estar influida por el tamaño del municipio.
- Para medir brecha digital convendría usar el porcentaje de viviendas con computadora respecto del total de viviendas particulares habitadas.
- “Grado de rezago social” puede funcionar como una variable ordinal; por eso Spearman es más apropiado que Pearson para una lectura principal.
- Deben revisarse las categorías y codificación del grado de rezago antes de interpretar la dirección numérica.
- Para un DSS sería útil complementar computadora con internet, telefonía y disponibilidad de dispositivos, además de población rural y pobreza.

## 10. Escolaridad y ocupación

### Pregunta analítica

Analizar la relación entre el grado promedio de escolaridad y el porcentaje de la población económicamente activa ocupada para identificar la asociación entre el nivel educativo y la participación en el mercado laboral.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Grado promedio de escolaridad | ID 64 |
| Tasa de ocupación | ID 320 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **-0.019** |
| Spearman | -0.199 |
| IC 95% aproximado de Pearson | [-0.152, 0.115] |

### Hallazgo

La relación lineal es **prácticamente nula**. En este corte municipal no se observa una asociación clara entre mayor escolaridad promedio y una mayor tasa de ocupación.

Spearman muestra una asociación inversa débil, lo que podría indicar diferencias de distribución, valores atípicos o una relación no lineal, pero no cambia la conclusión principal.

### Precauciones

- La tasa de ocupación depende de la estructura por edad, participación laboral, género, migración y composición económica local.
- La escolaridad promedio no mide directamente pertinencia de habilidades ni calidad del empleo.
- Para el DSS convendría complementar con desempleo, informalidad, ingreso, sector económico y población joven.

## 11. Computadoras y alfabetismo

### Pregunta analítica

Explorar la relación entre la disponibilidad de computadoras en los hogares y el nivel de alfabetización, para observar cómo el acceso a tecnología puede asociarse con mejores condiciones educativas.

### Opción principal: computadoras y alfabetismo

| Dato | Resultado |
|---|---:|
| Viviendas con computadora | ID 82 |
| Personas de 15 años y más alfabetas | ID 63 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.161** |
| Spearman | 0.567 |
| IC 95% aproximado de Pearson | [0.028, 0.288] |

### Hallazgo

Pearson muestra una relación lineal **débil y positiva**, mientras que Spearman identifica una asociación monotónica **moderada**. Esto sugiere que los municipios con más viviendas con computadora suelen ordenarse hacia mayores niveles de alfabetismo, pero la relación no es proporcional ni lineal.

La diferencia entre ambos coeficientes también puede deberse a que la variable de computadoras está expresada como número absoluto de viviendas y no como porcentaje.

### Alternativa: computadoras y escolaridad

| Dato | Resultado |
|---|---:|
| Viviendas con computadora | ID 82 |
| Grado promedio de escolaridad | ID 64 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **0.324** |
| Spearman | 0.667 |
| IC 95% aproximado de Pearson | [0.201, 0.438] |

La alternativa presenta una relación lineal débil a moderada y una asociación monotónica fuerte. Para el objetivo educativo, esta combinación parece más informativa como diagnóstico territorial, aunque sigue afectada por el conteo absoluto de computadoras.

### Precauciones

- Conviene calcular viviendas con computadora como porcentaje del total de viviendas.
- También se deben incluir internet, dispositivos disponibles, edad, ruralidad, ingresos y cobertura escolar.
- La asociación no demuestra que disponer de computadora produzca mayor alfabetismo o escolaridad.

## 12. Rezago educativo y desocupación

### Pregunta analítica

Analizar la asociación entre el rezago educativo y la desocupación de la población económicamente activa para evaluar la intensidad y dirección de la relación entre ambas variables.

### Variables y cobertura

| Dato | Resultado |
|---|---:|
| Población con rezago educativo | ID 65 |
| Tasa de desocupación | ID 321 |
| Año analizado | 2020 |
| Municipios coincidentes | 217 |
| Pearson | **-0.040** |
| Spearman | -0.198 |
| IC 95% aproximado de Pearson | [-0.173, 0.094] |

### Hallazgo

La relación es **prácticamente nula** en términos lineales y débilmente inversa al considerar rangos. En este corte territorial, los municipios con mayor rezago educativo no muestran una mayor tasa de desocupación.

Esto no significa que el rezago educativo no afecte las oportunidades laborales individuales. La tasa municipal de desocupación puede ocultar informalidad, subempleo, migración, trabajo no remunerado y diferencias en la participación económica.

### Precauciones

- La desocupación no captura la calidad, estabilidad ni remuneración del empleo.
- Es recomendable analizar también tasa de informalidad, ingresos laborales, población no económicamente activa y participación laboral.
- La relación puede aparecer con rezagos temporales o al desagregar por edad, sexo, ruralidad y nivel educativo.

## Lectura general

Estos cruces sirven como diagnóstico exploratorio y para detectar municipios atípicos, pero antes de convertirlos en indicadores públicos o recomendaciones deben revisarse:

- Definición conceptual de cada variable.
- Unidad de medida y denominadores.
- Cobertura temporal y territorial.
- Valores atípicos.
- Posibles relaciones matemáticas o efectos del tamaño poblacional.
- Variables de control necesarias para una interpretación causal.
