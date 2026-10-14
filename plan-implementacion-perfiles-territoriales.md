# Plan de implementación: perfiles territoriales orientados a datos

## Propósito

Transformar los perfiles municipales y regionales en una experiencia de exploración territorial inspirada en los principios de Data México y Data USA: síntesis primero, contexto claro, narrativa basada en evidencia y acceso progresivo al detalle.

No se busca reproducir sus interfaces ni reemplazar el Banco de Indicadores. El perfil debe responder **“¿cómo es este territorio?”** y el Banco debe continuar respondiendo **“¿cómo se comporta este indicador?”**.

## Estado actual

- Perfil municipal: `resources/views/municipios/perfil.blade.php`.
- Perfil regional y estatal: `resources/views/regiones/perfil.blade.php`.
- Datos y narrativa municipal: `FichaComposerService`, `FichaDataStore`, `FichaNarratorService` y `FichaProfilerService`.
- Configuración editorial: `configuracion_fichas` y sus variables asociadas.
- Exploración profunda: Banco de Indicadores en `resources/views/fichas.blade.php`.
- Referencia gráfica para legibilidad y procedencia: `resources/views/municipios/panorama_pdf.blade.php`.

## Principios de diseño

1. **Territorio antes que catálogo.** El nombre, ubicación, contexto y datos clave se leen antes de las visualizaciones detalladas.
2. **Una pregunta por visualización.** Una tarjeta solo se muestra si responde una pregunta comprensible para una persona no técnica.
3. **Síntesis antes que exploración.** La página ofrece un resumen; el Banco abre el análisis completo, comparación, exportación y series históricas.
4. **Datos verificables.** Año, fuente, unidad y alcance territorial se muestran junto al resultado.
5. **Sin inferencias engañosas.** Cero, dato faltante, estimación, promedio y total se distinguen visualmente y en texto.
6. **Comparación contextual.** Las comparativas especifican el referente, año, universo y criterio; no se presentan como evaluación automática.
7. **Adaptación por contenido.** El espacio de una visualización depende del número de categorías, longitud de etiquetas, número de series y tipo de gráfico.
8. **Accesibilidad y desempeño.** Navegación por teclado, texto alternativo, foco visible, estados de carga y renderizado progresivo desde móvil.

## Arquitectura de experiencia propuesta

```text
Hero territorial
  └─ Identidad, regionalización, acciones y fecha de actualización

Resumen ejecutivo
  └─ 4–6 datos prioritarios y comparables

Territorio en contexto
  └─ Comparación contra microrregión, macrorregión y/o estado

Secciones temáticas editoriales
  └─ Introducción breve + 1–3 visualizaciones prioritarias
  └─ “Ver datos completos en el Banco”

Fuentes, notas metodológicas y cobertura
```

### Secciones sugeridas

El orden debe ser editorial y configurable, no depender solo del orden técnico de dimensiones.

1. Población y bienestar.
2. Economía y empleo.
3. Educación y salud.
4. Territorio y medio ambiente.
5. Gobierno y seguridad.

Las dimensiones sin indicadores visibles no generan una sección vacía.

## Modelo de tarjeta web

```text
TEMÁTICA
Nombre claro del indicador                                      Año

Visualización o dato principal

Lectura breve: qué representa el dato y su contexto inmediato.

Fuente · Unidad · Alcance territorial
[Metodología] [Ver en Banco]
```

### Tipos visuales

| Contenido | Tratamiento web |
| --- | --- |
| Un valor absoluto, tasa o porcentaje | KPI destacado con unidad, año y comparación opcional. |
| 2–5 categorías | Barras verticales si las etiquetas son cortas; horizontales si son largas. |
| 6+ categorías | Barras horizontales con altura adaptable o tabla accesible de apoyo. |
| Serie temporal | Línea con año claramente visible y tendencia opcional. |
| Pirámide | Ancho completo, altura ampliada y leyenda visible. |
| Dato categórico | Etiqueta destacada y explicación breve; no se fuerza una gráfica. |
| Sin dato | Estado explícito con razón o periodo disponible; nunca se sustituye por cero. |

## Alcance funcional

### Mantener

- Hero territorial existente, navegación por dimensiones y enlaces al Banco.
- Configuración de fichas, variables visibles, polaridad y orden.
- Tendencias, comparativas y narrativa, siempre que tengan contexto suficiente.
- Perfiles estatal, macrorregional y microrregional.

### Incorporar

- Resumen ejecutivo configurable de indicadores clave.
- Bloque de contexto territorial configurable por indicador.
- Componentes reutilizables para tarjeta de indicador, metadatos y estado sin dato.
- Reglas de tamaño y orientación de gráficas equivalentes a las del panorama, pero adaptadas al navegador.
- Fuente, año, unidad y notas visibles sin abrir un bloque secundario.
- Indicador de actualización del perfil y cobertura de datos.

### No incorporar en esta fase

- Duplicar filtros, exportación, selección de años o comparación multiterritorial del Banco.
- Sustituir el Banco de Indicadores.
- Generar narrativas automáticas para indicadores sin reglas editoriales revisadas.
- Cambiar valores históricos o reglas de agregación.

## Fases de implementación

### Fase 0 — Auditoría editorial y de datos

**Objetivo:** definir qué se muestra y con qué interpretación.

1. Inventariar configuraciones visibles por perfil, dimensión y tipo de visualización.
2. Revisar unidades, polaridad, fuentes, años y cobertura de cada indicador.
3. Clasificar indicadores en: resumen ejecutivo, comparativa, visualización temática, solo Banco o pendiente de revisión.
4. Detectar variables duplicadas, unidades incompatibles y configuraciones sin datos.
5. Definir máximo de indicadores por sección para evitar una página-catálogo.

**Entregables:** matriz editorial de indicadores y lista de correcciones de catálogo.

### Fase 1 — Fundación de componentes y estados

**Objetivo:** crear elementos reutilizables sin rediseñar todo el perfil de una vez.

1. Crear componente de metadatos compactos: fuente, año, unidad y cobertura.
2. Crear estado vacío estándar: sin dato, sin cobertura, indicador no comparable y error de carga.
3. Crear tarjeta base de indicador con variantes KPI, gráfica, categoría y comparativa.
4. Extraer tokens visuales compartidos: color de dimensión, borde, espaciado, tipografía y foco.
5. Asegurar que cero sea distinto de ausencia de dato en Blade y JavaScript.

**Criterios de aceptación:**

- Fuente y año visibles en toda tarjeta con dato.
- Ninguna ausencia de dato se muestra como `0`.
- Componentes funcionan a 320 px, 768 px y 1440 px.

### Fase 2 — Resumen ejecutivo municipal

**Objetivo:** que el primer vistazo explique el municipio sin desplazamiento excesivo.

1. Definir 4–6 configuraciones prioritarias por municipio.
2. Mostrar valor, unidad, año y referente territorial cuando sea comparable.
3. Limitar las interpretaciones automáticas a configuraciones con polaridad y universo válidos.
4. Agregar enlace contextual al Banco desde cada dato.
5. Mantener el hero actual, reduciendo información duplicada entre hero y tarjetas.

**Criterios de aceptación:**

- Cada KPI responde qué mide, de cuándo es y cuál es su unidad.
- Las comparativas identifican explícitamente el referente.
- El resumen no depende de gráficas pesadas para ser útil.

### Fase 3 — Secciones temáticas narrativas

**Objetivo:** sustituir la cuadrícula técnica por una lectura organizada.

1. Añadir introducción editorial breve por sección.
2. Mostrar 1–3 visualizaciones principales antes de información secundaria.
3. Asignar tamaño de tarjeta desde el contenido real:
   - Categorías numéricas disponibles.
   - Longitud de etiquetas.
   - Número de series.
   - Tipo de gráfico.
4. Aplicar barras horizontales para etiquetas largas o categorías numerosas.
5. Reservar ancho completo y mayor altura para pirámides, series densas y comparaciones multiserie.
6. Mover metodología extensa a un desplegable accesible sin ocultar fuente y año.

**Criterios de aceptación:**

- No hay etiquetas recortadas o superpuestas en gráficos soportados.
- Una visualización no muestra series con unidades incompatibles.
- Cada tarjeta tiene una pregunta implícita y una lectura comprensible.

### Fase 4 — Contexto territorial y comparativas

**Objetivo:** explicar la posición del municipio sin convertir el perfil en tablero técnico.

1. Definir por configuración si compara contra microrregión, macrorregión, estado o ninguno.
2. Mostrar comparativas como texto y visual breve, no solo como insignia de ranking.
3. Incluir universo y año: por ejemplo, “posición 12 de 217 municipios con datos, 2024”.
4. Ocultar comparativas cuando el dato no sea metodológicamente comparable.
5. Validar polaridad y lenguaje para evitar calificar un dato meramente informativo.

**Criterios de aceptación:**

- Todo ranking indica universo, año y criterio.
- Las etiquetas de desempeño solo aparecen con polaridad válida.
- La comparación se puede entender sin abrir el Banco.

### Fase 5 — Homologación regional y estatal

**Objetivo:** conservar la misma experiencia entre municipio, microrregión, macrorregión y estado.

1. Aplicar los componentes del perfil municipal a `regiones/perfil.blade.php`.
2. Distinguir visualmente datos oficiales territoriales y agregaciones municipales.
3. Revisar textos de cobertura para evitar interpretar una agregación como medición territorial oficial.
4. Vincular perfiles regionales con el nuevo índice `/perfiles-regionales`.

**Criterios de aceptación:**

- La estructura de lectura es equivalente en los cuatro niveles territoriales.
- El origen territorial del dato es visible donde corresponda.

### Fase 6 — Calidad, accesibilidad y rendimiento

**Objetivo:** validar que el rediseño sea confiable y sostenible.

1. Pruebas para cero, nulo, categorías, series múltiples y datos sin cobertura.
2. Pruebas de reglas de dimensión/orientación de gráficas.
3. Pruebas de rutas al Banco preservando indicador y territorio.
4. Revisión manual en 320 px, 768 px y 1440 px.
5. Navegación por teclado, foco visible, semántica de títulos y alternativa tabular para gráficas.
6. Carga diferida de ECharts y gráficas fuera de viewport.
7. Medición de cantidad de consultas y tamaño de respuesta.

## Cambios técnicos previsibles

| Área | Acción prevista |
| --- | --- |
| `resources/views/municipios/perfil.blade.php` | Reorganizar jerarquía, integrar componentes y variantes de tarjeta. |
| `resources/views/regiones/perfil.blade.php` | Reutilizar la misma estructura con diferencias territoriales explícitas. |
| `public/js/perfil.js` | Centralizar reglas de orientación, dimensiones, etiquetas y estados de ECharts. |
| `FichaComposerService` / `FichaDataStore` | Exponer metadatos mínimos consistentes para tarjetas. |
| `ConfiguracionFicha` | Incorporar únicamente campos que no puedan resolverse con la configuración actual. |
| Pruebas de perfil | Cubrir estados, comparativas, accesibilidad básica y rutas al Banco. |

## Decisiones pendientes

1. ¿Qué indicadores integran el resumen ejecutivo por dimensión y territorio?
2. ¿La narrativa debe ser solo configurada editorialmente, automática con plantillas, o híbrida?
3. ¿Qué comparativas son válidas para porcentajes, tasas, índices y datos categóricos?
4. ¿Se necesita una tabla de datos accesible desplegable en cada gráfica o un enlace al Banco es suficiente?
5. ¿Qué fuente define la fecha de actualización del perfil: el dato más reciente, la configuración o ambas?

## Ruta de entrega sugerida

1. Fase 0 y matriz editorial.
2. Fase 1 y Fase 2 sobre un municipio piloto: Chignahuapan.
3. Validación editorial, técnica y móvil del piloto.
4. Fase 3 y Fase 4 para todos los perfiles municipales.
5. Fase 5 para estatal y regiones.
6. Fase 6 como criterio de liberación.

## Definición de terminado

El rediseño se considera listo cuando un visitante puede identificar, sin recurrir al Banco:

- qué caracteriza al territorio;
- de qué año y fuente son los datos;
- cómo se compara el territorio cuando la comparación es válida;
- dónde explorar la serie completa;
- y cuándo no hay información suficiente para concluir algo.
