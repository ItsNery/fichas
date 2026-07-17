# Correlaciones destacadas entre variables municipales

Fecha del análisis: 15 de julio de 2026.

## Objetivo

Identificar relaciones lineales fuertes entre variables municipales que puedan convertirse en gráficas de dispersión útiles para análisis territorial. El propósito no fue elegir mecánicamente los coeficientes más cercanos a `-1` o `1`, sino encontrar relaciones comparables, interpretables y no tautológicas.

## Universo analizado

- 294 variables registradas en el sistema.
- 217 municipios del estado de Puebla.
- Datos históricos disponibles entre 2010 y 2025.
- 210 variables conservaron cobertura y variación suficientes para el barrido.
- 172 pares normalizados alcanzaron `|r| >= 0.50` antes de la curaduría temática.
- Para cada variable se utilizó su último año disponible.

El análisis es reproducible con:

```bash
php scripts/analyze-variable-correlations.php 100 normalizada
```

## Reglas de comparabilidad

Se consideraron comparables:

- Porcentajes con porcentajes.
- Tasas, razones, índices y promedios con otras medidas normalizadas.
- Medidas expresadas por habitante, vivienda, cada mil o cada diez mil personas.
- Valores absolutos únicamente cuando compartían exactamente la misma unidad; estos se auditaron, pero no se seleccionaron si la relación era explicada principalmente por el tamaño de la población.

Se excluyeron:

- Variables categóricas codificadas mediante `mapeo_valores`.
- Pares con menos de 100 municipios comparables.
- Variables con menos de ocho valores distintos.
- Dos variables pertenecientes al mismo indicador compuesto.
- Cruces entre porcentajes y totales sin normalización.
- Relaciones donde una variable era componente, complemento o transformación casi directa de la otra.

## Cálculos

Para cada par se alinearon los municipios que tenían datos en ambas variables. El coeficiente de Pearson se calculó como:

```text
r = sum((xi - promedioX)(yi - promedioY))
    / sqrt(sum((xi - promedioX)^2) * sum((yi - promedioY)^2))
```

Como control de robustez también se calculó Spearman sobre rangos promedio, incluyendo el tratamiento de empates. Los intervalos de confianza descriptivos al 95% se obtuvieron mediante la transformación de Fisher:

```text
z = atanh(r)
error = 1 / sqrt(n - 3)
IC95% = tanh(z +/- 1.96 * error)
```

Los finalistas exigen:

- `n = 217` municipios.
- `|r| >= 0.60`.
- Pearson y Spearman con el mismo signo.
- Una explicación territorial plausible.
- Ausencia de dependencia directa por usar totales poblacionales.

## Diez finalistas

### 1. Tasa de natalidad y tasa de fecundidad general

| Dato | Resultado |
|---|---:|
| Variables | IDs 54 y 55 |
| Años | 2024 / 2024 |
| Municipios | 217 |
| Pearson | **0.945** |
| IC 95% | [0.929, 0.958] |
| Spearman | 0.940 |

**Por qué aporta:** ambas son tasas y eliminan el efecto del tamaño municipal. La relación permite observar si una natalidad elevada coincide con una fecundidad elevada entre mujeres en edad reproductiva. Los municipios alejados de la tendencia pueden revelar diferencias en estructura por edades, migración o registro de nacimientos.

**Precaución:** comparten el fenómeno de nacimientos, aunque usan denominadores distintos; sirve mejor para detectar casos atípicos que para afirmar causalidad.

### 2. Escolaridad promedio y rezago educativo

| Dato | Resultado |
|---|---:|
| Variables | IDs 64 y 65 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **-0.939** |
| IC 95% | [-0.953, -0.921] |
| Spearman | -0.957 |

**Por qué aporta:** confirma una relación inversa muy consistente entre años de escolaridad y rezago. La gráfica es útil para localizar municipios cuyo rezago es mayor o menor de lo esperable dado su nivel promedio de escolaridad.

**Precaución:** son conceptos cercanos; el valor analítico está en los residuos y municipios atípicos, no en demostrar que ambos fenómenos están relacionados.

### 3. Alumnos por escuela primaria y alumnos por maestro de primaria

| Dato | Resultado |
|---|---:|
| Variables | IDs 104 y 110 |
| Años | 2025 / 2025 |
| Municipios | 217 |
| Pearson | **0.874** |
| IC 95% | [0.838, 0.902] |
| Spearman | 0.915 |

**Por qué aporta:** relaciona tamaño promedio de los planteles con carga docente. Los municipios por encima de la tendencia pueden tener escuelas grandes con una carga por maestro relativamente alta; los ubicados debajo pueden mostrar mayor disponibilidad docente relativa.

**Precaución:** no mide calidad educativa ni permite atribuir resultados de aprendizaje.

### 4. Sucursales bancarias y cajeros automáticos por cada diez mil adultos

| Dato | Resultado |
|---|---:|
| Variables | IDs 160 y 161 |
| Años | 2024 / 2024 |
| Municipios | 217 |
| Pearson | **0.777** |
| IC 95% | [0.718, 0.825] |
| Spearman | 0.762 |

**Por qué aporta:** ambas variables están normalizadas por población adulta y representan dos canales distintos de infraestructura financiera. Permite detectar municipios con una red bancaria desequilibrada, por ejemplo, con sucursales pero pocos cajeros o viceversa.

### 5. Alfabetización y carencia de servicios básicos en la vivienda

| Dato | Resultado |
|---|---:|
| Variables | IDs 63 y 93 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **-0.744** |
| IC 95% | [-0.798, -0.678] |
| Spearman | -0.766 |

**Por qué aporta:** cruza dos dimensiones distintas del bienestar: capital educativo e infraestructura básica. La asociación inversa ayuda a localizar territorios donde el rezago material y el educativo se concentran, así como excepciones que requieren una lectura local.

### 6. Índice de hacinamiento y población en situación de pobreza

| Dato | Resultado |
|---|---:|
| Variables | IDs 83 y 84 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **0.740** |
| IC 95% | [0.673, 0.795] |
| Spearman | 0.750 |

**Por qué aporta:** relaciona una condición concreta de vivienda con una medida integral de pobreza. Puede apoyar la focalización de políticas de ampliación, mejoramiento de vivienda y reducción de carencias.

### 7. Escolaridad promedio y población en situación de pobreza

| Dato | Resultado |
|---|---:|
| Variables | IDs 64 y 84 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **-0.724** |
| IC 95% | [-0.782, -0.654] |
| Spearman | -0.648 |

**Por qué aporta:** muestra la asociación territorial entre capital humano y pobreza. Los municipios que se separan de la tendencia pueden señalar economías locales donde una mayor escolaridad todavía no se traduce en mejores condiciones de ingreso, o donde otros factores compensan una escolaridad baja.

**Precaución:** la diferencia entre Pearson y Spearman es moderada; conviene revisar valores atípicos antes de usarla para decisiones específicas.

### 8. Carencia por calidad de vivienda y carencia de servicios básicos

| Dato | Resultado |
|---|---:|
| Variables | IDs 92 y 93 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **0.708** |
| IC 95% | [0.635, 0.769] |
| Spearman | 0.747 |

**Por qué aporta:** aunque ambas pertenecen al ámbito de vivienda, miden problemas distintos: materiales y espacios frente a disponibilidad de servicios. La gráfica permite distinguir municipios donde las dos carencias se acumulan y aquellos donde solo una requiere atención prioritaria.

### 9. Ocupantes promedio por vivienda e índice de hacinamiento

| Dato | Resultado |
|---|---:|
| Variables | IDs 78 y 83 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **0.670** |
| IC 95% | [0.589, 0.737] |
| Spearman | 0.683 |

**Por qué aporta:** diferencia el tamaño medio del hogar de una condición efectiva de hacinamiento. Los municipios alejados de la tendencia pueden mostrar que más ocupantes no necesariamente implican insuficiencia de espacio, o viceversa.

### 10. Baja accesibilidad carretera y disposición inadecuada de residuos

| Dato | Resultado |
|---|---:|
| Variables | IDs 172 y 186 |
| Años | 2020 / 2020 |
| Municipios | 217 |
| Pearson | **0.620** |
| IC 95% | [0.531, 0.696] |
| Spearman | 0.616 |

**Por qué aporta:** conecta aislamiento territorial con una práctica ambiental. La relación sugiere que las barreras de acceso pueden coincidir con menor cobertura o mayor dificultad logística para la recolección formal de residuos. Es el cruce más transversal de los finalistas y puede orientar análisis de infraestructura y servicios públicos.

**Precaución:** requiere contrastar disponibilidad real del servicio de recolección y dispersión poblacional; no prueba que la accesibilidad sea la causa de la disposición inadecuada.

## Pares fuertes descartados

| Par | Pearson | Motivo de descarte |
|---|---:|---|
| Alumnos por grupo de primaria / alumnos por maestro de primaria | 0.999 | Prácticamente identidad operativa en los datos disponibles. |
| Disposición inadecuada / quema de residuos | 0.998 | La quema puede formar parte de la definición de disposición inadecuada. |
| Pobreza / ingreso inferior a la línea de pobreza | 0.960 | Solapamiento conceptual y metodológico. |
| No pobre y no vulnerable / al menos una carencia social | -0.962 | Categorías parcialmente complementarias. |
| Pobreza por ingresos / pobreza extrema por ingresos | 0.926 | Una categoría está contenida en la otra. |
| Totales de población, viviendas o población ocupada | 0.99 a 1.00 | El tamaño municipal domina la relación; no representa asociación sustantiva. |

## Lectura recomendada

- El signo indica dirección, no conveniencia: una correlación negativa puede ser igual de informativa que una positiva.
- Un coeficiente alto no implica causalidad.
- La gráfica debe mostrar los 217 puntos, destacar el municipio consultado y permitir identificar valores atípicos.
- Si los años de las variables difieren, la ficha debe indicarlo de manera explícita.
- Antes de publicar una relación conviene revisar la definición operativa, el origen y la periodicidad de ambas variables.
- Pearson debe acompañarse de Spearman cuando haya valores extremos o una posible relación no lineal.

## Recomendación de implementación

Los pares 4, 5, 6, 7 y 10 son los mejores candidatos para comunicación pública porque cruzan dimensiones distintas o instrumentos de política diferentes. Los pares 1, 2, 3, 8 y 9 son especialmente útiles para control de consistencia, diagnóstico sectorial y detección de municipios atípicos.
