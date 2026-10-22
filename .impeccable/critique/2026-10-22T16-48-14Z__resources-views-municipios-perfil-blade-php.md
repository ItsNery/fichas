---
target: Vista de perfil municipal
total_score: 24
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 2
target_identity: "file:C:\\laragon\\www\\fichas_municipales\\resources\\views\\municipios\\perfil.blade.php"
target_fingerprint: "sha256:5f00ce907785447d32e4706f884efacb25a30d1cc2f4865552a8d96510f4988c"
target_path: "C:\\laragon\\www\\fichas_municipales\\resources\\views\\municipios\\perfil.blade.php"
timestamp: 2026-10-22T16-48-14Z
slug: resources-views-municipios-perfil-blade-php
---
## Salud de diseño

| # | Heurística | Puntuación | Hallazgo clave |
|---|---|---:|---|
| 1 | Visibilidad del estado | 2/4 | Cargas progresivas visibles, pero sin expectativa ni recuperación clara. |
| 2 | Correspondencia con el mundo real | 3/4 | Lenguaje territorial claro; algunos términos técnicos requieren contexto. |
| 3 | Control y libertad | 3/4 | Breadcrumbs, exportaciones y modal con salida clara. |
| 4 | Consistencia | 3/4 | Paleta y tipografía consistentes; gramática de tarjetas heterogénea. |
| 5 | Prevención de errores | 2/4 | Faltan referencias visibles para interpretar vigencia y polaridad. |
| 6 | Reconocimiento | 3/4 | Las dimensiones son encontrables, pero matices dependen de iconos y tooltips. |
| 7 | Eficiencia | 2/4 | Hay rutas a comparar y banco, sin una lectura analítica compacta. |
| 8 | Diseño minimalista | 2/4 | Las tarjetas superponen valor, año, icono, tendencia, narrativa, gráfica y metadatos. |
| 9 | Recuperación de errores | 2/4 | Las cargas prolongadas no ofrecen un siguiente paso consistente. |
| 10 | Ayuda y documentación | 2/4 | Fuente y método aparecen tarde, bajo divulgación. |

**Total: 24/40 — aceptable; requiere mejoras relevantes.**

## Veredicto de especificidad

La entrada sí se siente propia del SEI Puebla: fotografía de la localidad, mapa municipal, paleta vino–verde–oro y tipografías institucionales. El problema aparece en el cuerpo de indicadores: muchas tarjetas, iconos de marca de agua, pills y sombras ligeras podrían pertenecer a cualquier dashboard. No hay que reemplazar la identidad institucional; hay que llevarla a la capa de datos.

El detector no encontró patrones mecánicos en la plantilla. Es una señal limitada: la revisión visual sí encontró sobrecarga por composición y jerarquía, aspectos que el detector no puede inferir.

## Qué conservar

- Hero territorial: fotografía, nombre y mapa generan reconocimiento inmediato.
- Paleta y tipos institucionales: orientan las dimensiones sin competir con el dato.
- Rutas visibles a comparación, PDF, panorama y Banco de Indicadores.

## Prioridades

### P1 — La primera lectura no está editada

Las tarjetas llegan antes que una conclusión: una persona debe armar el sentido del municipio por sí misma. Sustituir el arranque del bloque de datos por una `Lectura rápida` de tres o cuatro hallazgos elegidos, cada uno con valor, año y fuente. Mantener el catálogo completo debajo.

### P1 — Cada tarjeta intenta decir demasiado

Reemplazar la mezcla de icono, fecha, porcentaje de tendencia, narrativa, comparaciones, gráfica y metadatos por tres capas: `resultado actual + año`, una `interpretación breve`, y un bloque opcional de `comparación / historia / método`. La unidad debe vivir junto al valor; la dirección debe decirse en texto y no depender de color o tooltip.

### P2 — Móvil pierde significado en las acciones

Las acciones del hero pasan a iconos compactos y la navegación de dimensiones se desplaza horizontalmente. Mantener objetivos táctiles de 44 px como mínimo; agrupar acciones secundarias bajo “Más acciones” y dar una pista visible de desplazamiento o un selector de dimensiones.

### P2 — La procedencia llega tarde

Mover `Último dato: año · fuente` al renglón del resultado principal. Reservar método, cálculo y notas para el divulgable. Es una mejora de confianza, no decoración.

### P3 — Limpiar los hábitos visuales genéricos

Eliminar iconos de marca de agua y la convivencia de cards cuadradas, redondeadas y pills. Definir un único borde/radio para contenedores de datos; emplear vino, verde y oro en reglas de sección, jerarquía de valores y claves del mapa, no en adornos. El espacio visual debe pertenecer a valor, tiempo y fuente.

## Personas

- **Alex, analista:** encuentra Comparar y Banco, pero debe recorrer una corriente larga de cards para identificar tres datos clave. Necesita una síntesis fija y una vía de comparación dirigida.
- **Sam, teclado/lector de pantalla:** hay etiquetas ARIA, pero la interpretación de tendencia y contexto depende todavía de iconos, color y hover. Debe recibir el significado en texto visible y foco inequívoco.
- **Casey, móvil:** el mapa se oculta apropiadamente, pero acciones sin etiqueta y navegación horizontal hacen difícil retomar la lectura tras una interrupción.

## Observaciones menores

- El cierre del modal dice `Close` en una interfaz en español.
- La capitalización de algunos nombres de dimensión parece mecánica.
- Las tarjetas de municipios similares son útiles, pero llegan tarde y repiten acciones.

## Preguntas a considerar

- ¿Qué tres hechos debería comprender alguien sobre Atzala en los primeros diez segundos?
- ¿Qué indicador amerita una gráfica en primera vista, y cuáles solo necesitan valor, año y fuente?
- ¿Qué información institucional debe estar siempre visible para que un dato sea creíble?
