# Propuesta editorial global: plantilla de perfil municipal

**Fecha:** 15 de octubre de 2026

**Alcance:** reglas reutilizables para la ficha de todos los municipios.
**Estado:** propuesta para validación editorial. No modifica configuraciones ni datos.

## Criterio de diseño

La ficha conserva las cuatro dimensiones del catálogo como su estructura principal. No habrá un resumen ejecutivo independiente: cada dimensión inicia con su lectura principal y concentra sus propias lecturas de apoyo.

- **Principal:** responde la pregunta central de la dimensión y se muestra primero.
- **Secundario:** aporta explicación o contraste sin competir con la lectura principal.
- **Contexto:** dato válido cuyo corte no debe interpretarse como situación actual.
- **Consolidar:** existe otra configuración que comunica el mismo indicador; se mantiene una sola vista pública.
- **Revisar cobertura:** no debe publicarse como dato general hasta comprobar cobertura suficiente entre municipios.

## Regla de publicación

Cada dimensión debe conservar al menos una lectura principal. Una ficha completa tendrá normalmente entre cuatro y seis lecturas principales: una por dimensión y una adicional solo donde el tema requiera una segunda pregunta no redundante.

Las configuraciones sin dato para un municipio deben mostrar “Sin cobertura para este municipio” o no renderizarse; nunca deben mostrarse como cero.

## Inventario y propuesta

### Demográfica y Social

| ID | Configuración actual | Visualización | Rol / acción propuesta | Motivo |
| ---: | --- | --- | --- | --- |
| 14 | Pirámide poblacional | Pirámide | Principal · mantener | Lectura demográfica integral. |
| 2 | Población total | Barras | Consolidar con hero/pirámide | Repite la población ya visible en el hero y en la pirámide. |
| 17 | Índice de hacinamiento | Líneas | Secundario · mantener | Aporta condición habitacional. |
| 35 | Población en situación de pobreza | Barras | Contexto · mantener | Requiere año claramente visible si el último corte no es reciente. |
| 22 | Rezago | KPI | Contexto o secundario | Mantener solo si el corte y la definición son comparables. |
| 15 | Cantidad de matrimonios | KPI | Secundario · revisar relevancia | No debe desplazar indicadores estructurales. |
| 36 | Pobreza en el territorio | Mapa | Consolidar con 35 | Mismo indicador; escoger barras para comparación o mapa para distribución, no ambos. |
| 40 | Composición de la población por sexo | Treemap | Consolidar con 14 | La pirámide ya comunica la composición por sexo y edad. |
| 41 | Relación hombres-mujeres | Líneas | Secundario · mantener condicionado | Mantener cuando la serie aporte una lectura distinta de la pirámide. |

### Económico

| ID | Configuración actual | Visualización | Rol / acción propuesta | Motivo |
| ---: | --- | --- | --- | --- |
| 57 | Índice de motorización por 1,000 habitantes | KPI | Secundario · revisar adscripción temática | Es útil, pero no necesariamente representa la lectura económica principal. |
| 37 | Valor agregado por sector | Barras | Principal · mantener | Las barras son la vista más clara para comparar sectores. |
| 42 | Participación económica | KPI | Principal o secundario | Elegirla como principal cuando haya cobertura sólida; de lo contrario dejarla como apoyo. |
| 43 | Evolución de ingresos por remesas | Líneas | Principal o secundario | Puede ser lectura principal en municipios donde las remesas son significativas; requiere regla homogénea de cobertura. |
| 44 | Valor de la producción agrícola | Mapa | Secundario · mantener | Aporta especialización productiva territorial. |
| 45 | Composición del valor agregado sectorial | Treemap | Consolidar con 37 | Misma pregunta editorial; mantener barras. |
| 47 | Balance visual del valor agregado | Treemap | Consolidar con 37 | Misma familia de variables; evitar tercera versión. |

### Gobierno, Seguridad e Impartición de Justicia

| ID | Configuración actual | Visualización | Rol / acción propuesta | Motivo |
| ---: | --- | --- | --- | --- |
| 16 | Número de llamadas recibidas que fueron atendidas | Treemap | Consolidar con 39 | Elegir composición o evolución, no ambas como resultados equivalentes. |
| 39 | Evolución de llamadas atendidas | Líneas | Secundario · mantener | Preferible si la tendencia es editorialmente relevante. |
| 51 | Recursos FORTAMUN per cápita | KPI | Principal · mantener | Lectura clara de capacidad financiera municipal. |
| 52 | Recursos federales por componente | Barras | Secundario · mantener | Desglosa la composición del financiamiento. |
| 53 | Accidentes de tránsito en el territorio | Mapa | Cambiar a barras horizontales | Las categorías de causa se comparan mejor con barras que con mapa. |
| 55 | Componentes de capacitación policial | Barras | Revisar cobertura | No publicar como lectura global hasta asegurar que el indicador tiene datos suficientes en los municipios. |

### Geográfica y Medio Ambiente

| ID | Configuración actual | Visualización | Rol / acción propuesta | Motivo |
| ---: | --- | --- | --- | --- |
| 18 | Áreas afectadas por incendios | Mapa | Principal · mantener | Resume un riesgo territorial relevante. |
| 38 | Composición de emisiones atmosféricas | Treemap | Consolidar con 48 | Mantener una única vista de composición. |
| 48 | Contaminantes atmosféricos por tipo | Barras | Secundario · mantener | Las barras hacen comparables los contaminantes. |
| 50 | Balance visual de emisiones | Treemap | Consolidar con 48 | Tercera visualización para las mismas variables. |
| 56 | KPI de recolección selectiva | KPI | Secundario · mantener | Útil como valor puntual. |
| 34 | Recolección selectiva de residuos | Líneas | Principal o secundario | Elegir líneas si la pregunta es evolución; de lo contrario mantener solo el KPI. |

## Configuración objetivo en el administrador

Para cada configuración que permanezca pública:

1. Asignar `Rol editorial`: principal, secundario o contexto.
2. Definir `Prioridad editorial` dentro de su dimensión.
3. Activar “Destacar dentro de su dimensión” únicamente para las lecturas principales que se quieran enfatizar visualmente.
4. Desactivar las configuraciones marcadas como “Consolidar” después de validar la vista elegida.
5. No usar el rol principal en configuraciones con cobertura insuficiente o datos antiguos sin aclaración temporal.

## Validación pendiente con negocio

1. Confirmar cuál lectura económica debe ser principal: participación económica, remesas, valor agregado o producción agrícola.
2. Definir la regla para publicar datos con corte antiguo: umbral de años, etiqueta y uso como contexto.
3. Determinar el tratamiento público estándar para cobertura incompleta.
4. Confirmar si motorización pertenece a Económico o debe moverse a Geográfica y Medio Ambiente.
5. Aprobar una sola visualización por cada grupo marcado para consolidación.
