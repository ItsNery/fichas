# Evolución del Sistema de Fichas Municipales

## Resumen ejecutivo

Esta versión no representa únicamente un rediseño de la página de inicio. El sistema evolucionó de un portal de consulta de indicadores hacia una plataforma de información municipal con:

- Fichas municipales configurables y orientadas a la lectura pública.
- Perfiles regionales para microrregiones y macrorregiones.
- Banco de indicadores con visualizaciones, comparativas, mapas y exportaciones.
- Primeras capacidades de apoyo a la toma de decisiones mediante polaridad, tendencias y narrativas DSS.
- Administración de metadatos, calidad, auditoría, roles, permisos y lotes de datos.
- API pública documentada con OpenAPI.
- Base técnica preparada para gobernanza de datos y evolución hacia un DSS institucional.

La transformación principal es pasar de **mostrar datos** a **organizar, interpretar, validar y contextualizar información para facilitar decisiones**.

## Alcance de la comparación

La comparación se realizó contra la rama `main` (`547a7ef`, `Agrego analitics`) y considera:

- Los cambios acumulados en la rama actual `beta` hasta `6e75ee9`.
- Los cambios locales presentes en el espacio de trabajo al momento de elaborar este documento.
- 336 rutas de archivo afectadas.
- 37,794 líneas agregadas y 5,592 eliminadas en archivos de texto.

Estas cifras incluyen código, vistas, estilos, migraciones, documentación, pruebas, recursos gráficos y archivos generados. El valor funcional de la versión es mayor que el tamaño de la pantalla inicial porque se modificaron varias capas del sistema.

## Antes y ahora

| Antes de `main` | Versión actual |
|---|---|
| Consulta principalmente puntual de indicadores | Consulta, comparación, interpretación y exploración histórica |
| Ficha municipal con menor estructura editorial | Perfil municipal con hero, mapa, KPIs, dimensiones, narrativas y visualizaciones |
| Configuración visual limitada | Configuración por indicador, variables, orden, historial, narrativa y comparativas |
| Administración centrada en catálogos e importaciones | Administración con salud de datos, diccionario, auditoría, roles, permisos y lotes |
| Polaridad disponible como campo aislado | Polaridad aplicada a lectura de tendencias y señales DSS |
| API pública reducida | API versionada con catálogos, metadatos, indicadores, datos y OpenAPI |
| Cobertura principalmente municipal | Cobertura municipal, microrregional y macrorregional |
| Gráficos como salida final | Gráficos como instrumentos de exploración y comparación |

## 1. Nueva experiencia de fichas municipales

### Nueva entrada al portal

La página de inicio ahora funciona como un punto de entrada para todo el ecosistema de información:

- Buscador unificado para municipios, indicadores, microrregiones y macrorregiones.
- Accesos directos a municipios, Banco de Indicadores y Datos Abiertos.
- Sección editorial que explica las acciones principales: visualizar, comparar y exportar.
- Carrusel de indicadores destacados con valor actual y mini-gráfica histórica.
- Enlaces de búsqueda que llevan directamente al contexto correspondiente.

El cambio importante no es solo visual: la página de inicio dejó de ser una portada estática y pasó a orientar al usuario hacia las rutas de consulta más relevantes.

### Perfil municipal completo

La ficha municipal ahora funciona como una experiencia de análisis y no solo como una lista de cifras:

- Hero visual con nombre del municipio, región, microrregión, cabecera y clima.
- Mapa territorial integrado en el encabezado.
- Acciones visibles para comparar municipios y descargar PDF.
- Barra de indicadores principales con población, PEA, pobreza, presupuesto, marginación y superficie.
- Navegación sticky por dimensiones para saltar rápidamente entre secciones.
- Secciones organizadas por dimensión temática.
- Tarjetas de indicadores con título, año, narrativa, fuente, método y visualización.
- Ranking municipal y contexto estatal o macrorregional.
- Municipios similares por población, región e indicador.
- Carga diferida de gráficos para mejorar el rendimiento inicial.
- Diseño responsive para escritorio, tablet y móvil.
- Tooltips y popovers Bootstrap inicializados globalmente en la experiencia pública.
- Ajustes de accesibilidad para foco de teclado, etiquetas ARIA y reducción de movimiento.

### Lectura más comprensible

Se mejoró la comunicación visual para personas no técnicas:

- Los nombres técnicos dejan de ser el centro de la experiencia.
- Los subtítulos explican qué representa cada visualización.
- La fuente y el método se mantienen disponibles sin saturar la tarjeta.
- Los iconos de tendencia y comparativa se muestran de forma compacta.
- Las explicaciones detalladas se reservan para el tooltip.
- Los indicadores neutros no se presentan artificialmente como mejora o deterioro.
- La señal de tendencia solo aparece cuando existen al menos dos años comparables.
- Los cortes temporales se muestran como un control específico y no como una lista confusa de años.

### Historial y cortes temporales

El campo `anios_historial` ahora controla de manera coherente:

- La cantidad de años de tendencia cargados.
- Los años que se ofrecen en el perfil para explorar.
- La disponibilidad de la señal DSS.
- Las opciones del formulario administrativo.

En la interfaz administrativa los valores se presentan con años reales, por ejemplo:

- `1 corte: 2020`
- `2 cortes: 2020, 2015`
- `3 cortes: 2020, 2015, 2010`

Esto evita que el usuario configure “5 años” cuando la fuente solamente tiene tres cortes disponibles.

## 2. Visualizaciones configurables

Se incorporó un módulo de configuración de fichas que permite decidir cómo se presenta cada indicador:

- KPI.
- Barras.
- Líneas.
- Pirámide.
- Treemap.
- Mapa.
- Dispersión para análisis de relación entre variables.

La configuración permite administrar:

- Indicador y variables asociadas.
- Orden de aparición.
- Ancho de tarjeta.
- Cantidad de historial.
- Título editorial.
- Subtítulo explicativo.
- Plantilla narrativa.
- Icono.
- Comparativa regional o estatal.
- Ajustes visuales avanzados.
- Estado activo o inactivo.

Se agregaron configuraciones activas de ejemplo para probar:

- Evolución temporal de recolección selectiva de residuos mediante líneas.
- Comparación municipal de población en situación de pobreza mediante barras.

También se corrigió el ordenamiento para que el campo `orden` sea respetado de forma determinista, usando el `id` como desempate cuando varias configuraciones comparten el mismo orden.

## 3. Primer ejercicio DSS

### Polaridad de indicadores

Se analizaron y asignaron polaridades al catálogo activo:

- 47 indicadores ascendentes: un valor mayor suele ser favorable.
- 40 indicadores descendentes: un valor menor suele ser favorable.
- 42 indicadores neutros: se muestran como contexto descriptivo.
- 129 indicadores actualizados en el catálogo.

La polaridad ya se utiliza para interpretar:

- Cambios históricos.
- Comparaciones contra el promedio estatal.
- Comparaciones contra el promedio macrorregional.
- Rankings municipales.
- Narrativas automáticas.
- Señales visuales de mejora o deterioro.

### Ejercicio de cambio relativo

Se generó un primer ejercicio DSS sobre el último par de años disponible por municipio:

- 14,980 comparaciones generadas.
- 5,674 mejoras según la polaridad.
- 3,051 deterioros.
- 503 casos estables.
- 8,741 casos sin clasificación por ser neutros o no comparables.

Entregables:

- `docs/dss-ejercicio-polaridad.csv`
- `docs/dss-ejercicio-polaridad.md`
- `docs/dss-indicadores-propuesta.csv`
- `docs/dss-metadata-review.md`

### Cómo se comunica en el perfil

- Verde: cambio favorable de acuerdo con la polaridad.
- Rojo: cambio desfavorable.
- Gris o ausencia de señal: sin comparación suficiente, sin variación relevante o indicador neutro.
- Dispersión: se conserva como análisis de asociación y no se mezcla con señales de desempeño.

## 4. Administración y gobernanza de datos

La administración dejó de ser únicamente un CRUD de catálogos.

### Salud de datos

El panel administrativo ahora contempla:

- Indicadores sin variables.
- Variables huérfanas.
- Indicadores desactualizados respecto al último año disponible.
- Datos atípicos con variaciones superiores al umbral configurado.
- Cobertura de polaridad DSS.
- Conteo de indicadores ascendentes, descendentes y neutros.

### Diccionario y metadatos

Los indicadores pueden documentar:

- Responsable.
- Periodicidad.
- Vigencia.
- Metodología.
- URL metodológica.
- Clasificación de información.
- Estado de publicación.
- Cobertura geográfica.
- Unidad responsable.
- Notas metodológicas.
- Norma técnica.

Las variables pueden documentar:

- Definición operativa.
- Fuente primaria.
- Tipo de valor.
- Rango mínimo y máximo.
- Visibilidad.
- Si son construidas.
- Fórmula y configuración de cálculo.

### Auditoría y control de acceso

Se incorporaron bases para gobernanza institucional:

- Registro de actividad de cambios en indicadores, variables, datos y municipios.
- Roles y permisos con control por módulo.
- Protección de rutas administrativas por capacidad.
- Administración de usuarios, roles y permisos.
- Flujo de lotes de datos con estados de revisión, aprobación y rechazo.

## 5. Importación, datos construidos y trazabilidad

La plataforma amplió su capacidad de operación de datos:

- Importación de dimensiones, temáticas, indicadores y variables.
- Importación de datos históricos desde Excel.
- Importación de indicadores complejos.
- Registro de lotes de datos.
- Seguimiento del origen y estado de cargas.
- Variables construidas con fórmulas configurables.
- Previsualización de resultados calculados.
- Generación y regeneración de históricos derivados.
- Exportaciones de datos históricos y complejos.

Esto permite avanzar desde la carga manual aislada hacia un proceso más controlado y auditable.

## 6. Perfiles regionales

Se incorporaron perfiles para dos niveles territoriales adicionales:

- Microrregiones.
- Macrorregiones.

Incluyen:

- Indicadores agregados por región.
- Comparativas regionales.
- Pirámides y visualizaciones específicas.
- Exportación a PDF.
- Exportación a Excel.
- Lectura territorial complementaria al perfil municipal.

## 7. API pública y datos abiertos

La API pública pasó de una consulta puntual a una estructura versionada:

- Catálogo de municipios.
- Microrregiones.
- Macrorregiones.
- Indicadores.
- Detalle de indicador.
- Metadatos.
- Datos estadísticos.
- Consulta pública compatible con los flujos existentes.
- Documento OpenAPI en `/openapi.json`.
- Documentación web en `/api/docs`.

También se reforzaron los módulos de datos abiertos y exportación en Excel, CSV y PDF.

## 8. Datos territoriales y enriquecimiento de contexto

Se agregaron o ampliaron servicios y datos de contexto municipal:

- Banners municipales con atribución de fuente y licencia.
- Clima predominante por municipio.
- Superficie municipal.
- Slugs para URLs públicas.
- Datos de referencia territorial.
- Mapas temáticos.
- Cálculos de área e intersección geográfica.
- Comparación territorial más consistente.

## 9. Arquitectura técnica

La lógica dejó de concentrarse exclusivamente en el controlador principal. Se incorporaron servicios especializados para:

- Consulta de indicadores.
- Composición de fichas.
- Perfilado municipal.
- Ranking y similitud.
- Mapas.
- Exportaciones.
- Narrativas.
- Correlaciones.
- Lotes de datos.
- Indicadores construidos.
- Datos de referencia municipal.

También se incorporaron:

- Laravel Vite para múltiples entradas frontend.
- ECharts para visualizaciones.
- Integración geoespacial con Turf, Shapefile y Proj4.
- Browsershot para generación de documentos.
- Activity Log para trazabilidad.
- Permission para autorización granular.
- Agentes de usuario para soporte de contexto de navegación.

## 10. Pruebas y documentación

Se añadieron pruebas de servicios y funcionalidades críticas:

- Composición de fichas.
- Ranking.
- Mapas.
- Exportaciones.
- Indicadores.
- Lotes de datos.
- Visibilidad pública.
- Autorización administrativa.
- Perfiles regionales.
- Correlaciones.
- Interpretación de polaridad en narrativas.

Documentación agregada o ampliada:

- Análisis FODA y hoja de ruta hacia gobernanza.
- Plan de implementación de gobernanza.
- Hallazgos de correlaciones DSS.
- Correlaciones destacadas.
- Banners municipales.
- Clasificación climática.
- Revisión de metadatos DSS.
- Ejercicio DSS con polaridad.
- Espaciado y criterios visuales de la ficha.

## 11. Guion sugerido para la presentación

### 1. Empezar por la ficha municipal

Mostrar que el usuario ahora encuentra en un mismo lugar:

- Contexto territorial.
- Indicadores clave.
- Dimensiones temáticas.
- Gráficos.
- Comparativas.
- Histórico.
- Fuentes y metodología.

### 2. Mostrar una comparación

Seleccionar un indicador de pobreza o residuos y explicar que la gráfica ya no solo muestra un número: permite ubicar al municipio frente a su contexto regional.

### 3. Mostrar el DSS

Usar un indicador con polaridad descendente, por ejemplo pobreza o hacinamiento:

- Si baja, la señal es favorable.
- Si sube, la señal es desfavorable.
- Si no hay años comparables, el sistema no inventa una tendencia.

### 4. Mostrar la administración

Entrar a:

- Configuración de fichas.
- Salud de datos.
- Diccionario de indicadores.
- Auditoría o lotes.

El mensaje principal es que la publicación ya tiene una capa de control y documentación.

### 5. Cerrar con la API y la escalabilidad

Mostrar `/api/docs` y explicar que la información puede ser consumida por otros sistemas, tableros o aplicaciones institucionales.

## Mensaje de cierre

La versión actual convierte una plataforma de consulta en una base para un sistema institucional de información y decisión:

> **Antes se publicaban indicadores. Ahora se pueden administrar, contextualizar, comparar, interpretar, auditar y preparar para decisiones.**

## Límites actuales que conviene comunicar con transparencia

- Las fechas de próxima actualización todavía requieren confirmación oficial por fuente.
- El ejercicio DSS es descriptivo y no prueba causalidad.
- Los indicadores neutros no deben interpretarse automáticamente como buenos o malos.
- Las configuraciones de visualización dependen de la calidad y cobertura histórica de cada fuente.
- El siguiente paso natural es convertir las señales DSS en tableros ejecutivos, alertas y seguimiento de metas.
