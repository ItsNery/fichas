# Manual de Usuario

## Sistema de Fichas Municipales y Banco de Indicadores

Este manual explica el uso del portal para consultar información territorial, analizar indicadores y administrar sus datos. Está dirigido a personas usuarias del portal público y a personal con acceso al panel administrativo.

## 1. Propósito y alcance

El sistema centraliza información estadística municipal y regional del estado de Puebla. Su función es facilitar consultas, comparativas, análisis histórico y difusión de datos abiertos.

Es una herramienta de apoyo a la toma de decisiones basada en evidencia. No reemplaza el análisis técnico, la validación de las fuentes ni las atribuciones de las instituciones responsables.

El portal no evalúa por sí mismo el cumplimiento de metas institucionales ni asigna ponderaciones entre indicadores. Cuando no existen metas, la lectura analítica se apoya en:

- La tendencia histórica del indicador.
- La polaridad registrada para interpretar si un aumento o disminución es favorable.
- La comparación con otros municipios, microrregiones, macrorregiones o el contexto estatal, según corresponda.
- La fuente, metodología, periodicidad y vigencia documentadas para cada indicador.

## 2. Conceptos básicos

| Concepto | Descripción |
|---|---|
| Dimensión | Agrupación temática de alto nivel, por ejemplo, desarrollo social o economía. |
| Temática | Subgrupo de una dimensión. |
| Indicador | Medida utilizada para describir o analizar un fenómeno. |
| Variable | Dato específico que integra un indicador. |
| Dato histórico | Valor de una variable para un municipio y año determinados. |
| Polaridad | Regla que indica cómo interpretar el cambio de un indicador. |
| Ficha municipal | Perfil sintético de un municipio con indicadores, comparativas y contexto territorial. |

## 3. Consulta pública

No es necesario iniciar sesión para usar las consultas públicas.

### 3.1 Banco de Indicadores

1. Abra **Banco de Indicadores** desde el menú principal.
2. Elija el nivel territorial: **Municipio**, **Microrregión**, **Macrorregión** o **Estatal**.
3. Busque y seleccione un indicador en el catálogo de la izquierda.
4. Seleccione uno o dos municipios, o la región correspondiente.
5. Opcionalmente, elija uno o más años.
6. Presione **Consultar**.

La vista muestra la gráfica, los años disponibles, la definición, fuente y método de cálculo cuando están documentados. Puede usar **Mapa** cuando la visualización sea compatible y **Exportar CSV** para descargar el resultado consultado.

### 3.2 Niveles territoriales

- **Municipio:** consulta uno o dos municipios y permite comparación directa.
- **Microrregión y macrorregión:** presenta resultados agregados según la naturaleza del indicador.
- **Estatal:** está disponible para indicadores con valores absolutos que pueden agregarse correctamente. Indicadores como porcentajes, tasas, índices o grados no deben sumarse; el portal informará cuando una consulta estatal no sea aplicable.

### 3.3 Fichas municipales y regionales

Las fichas reúnen información relevante de un territorio y facilitan pasar al Banco de Indicadores para profundizar en una gráfica.

Para usar una ficha municipal:

1. Abra **Fichas Municipales**.
2. Localice y seleccione el municipio.
3. Revise las secciones temáticas, los indicadores disponibles y su contexto.
4. Use los enlaces de gráfica, PDF o comparación cuando estén disponibles.

Los perfiles estatal, de microrregión y de macrorregión se consultan desde el módulo de perfiles regionales.

### 3.4 Interpretación de tendencia y polaridad

La polaridad no califica un municipio ni establece una meta. Solo permite interpretar la dirección de un cambio:

| Polaridad | Lectura general |
|---|---|
| Ascendente | Un valor mayor se considera favorable. Ejemplo: cobertura o alfabetización. |
| Descendente | Un valor menor se considera favorable. Ejemplo: pobreza o una tasa de incidencia negativa. |
| Neutro | El cambio es descriptivo; no se clasifica automáticamente como favorable o desfavorable. |

Antes de utilizar una tendencia para sustentar una decisión:

1. Confirme el año y la periodicidad de los datos.
2. Revise la fuente y la metodología.
3. Verifique que los valores sean comparables entre años.
4. Considere cambios metodológicos, coberturas incompletas o valores atípicos.
5. Evite interpretar correlación como causalidad.

## 4. Datos abiertos y API

La sección **Datos Abiertos** permite descargar catálogos y series históricas disponibles en los formatos ofrecidos por el portal.

La API pública permite integración con otros sistemas. Sus principales recursos son:

| Recurso | Ruta |
|---|---|
| Municipios | `/api/v1/municipios` |
| Microrregiones | `/api/v1/microrregiones` |
| Macrorregiones | `/api/v1/macrorregiones` |
| Indicadores | `/api/v1/indicadores` |
| Metadatos | `/api/v1/metadata` |
| Datos | `/api/v1/data` |
| Documentación interactiva | `/api/docs` |
| Especificación OpenAPI | `/api/openapi.json` |

Use la documentación de la API para conocer parámetros, formatos y ejemplos de consulta. Los resultados deben interpretarse con los metadatos del indicador y no de manera aislada.

## 5. Acceso administrativo

Ingrese desde **Iniciar sesión** con una cuenta autorizada. Las opciones visibles dependen de los permisos asignados a su cuenta.

| Rol | Uso principal |
|---|---|
| `super_admin` | Administración técnica completa, usuarios, roles y permisos. |
| `gobernanza` | Revisión y aprobación de datos, calidad, auditoría y diccionario de datos. |
| `analista` | Consulta y análisis administrativo sin modificar información. |
| `capturista` | Captura, edición propuesta e importación de información. |
| `consultor` | Consulta administrativa de solo lectura. |

Los roles son una configuración inicial. Las personas administradoras pueden asignar permisos específicos conforme a las responsabilidades institucionales.

## 6. Dashboard ejecutivo

Ruta: **Administración > Dashboard**.

El dashboard resume la operación y gobernanza del sistema:

- Cantidad de datos históricos, indicadores, dimensiones y municipios.
- Completitud de indicadores para el año más reciente disponible.
- Lotes de datos pendientes de revisión.
- Registros por año e indicadores por dimensión.
- Completitud de metadatos del diccionario.
- Estados de publicación y actividad reciente.

La completitud indica presencia de datos, no garantiza por sí misma exactitud, oportunidad o validez metodológica.

## 7. Salud de los datos

Ruta: **Administración > Salud de Datos**.

Este módulo ayuda a detectar situaciones que requieren revisión:

- **Indicadores vacíos:** no tienen variables asociadas.
- **Variables huérfanas:** no están vinculadas a un indicador.
- **Desactualizados:** no tienen datos para el año más reciente registrado en el sistema.
- **Datos atípicos:** presentan una variación superior al umbral configurado respecto al año anterior.
- **Polaridad DSS:** muestra cuántos indicadores tienen polaridad ascendente, descendente, neutra o pendiente de definir.

Una alerta no prueba que un dato sea incorrecto. El personal responsable debe contrastarla con la fuente, metodología, evidencia documental y contexto territorial antes de corregir o aprobar información.

## 8. Diccionario de datos

Ruta: **Administración > Diccionario**.

El diccionario documenta los indicadores y permite consultar o, con permiso, actualizar:

- Responsable y unidad responsable.
- Periodicidad.
- Fechas de vigencia.
- Metodología y liga metodológica.
- Clasificación de la información.
- Estado de publicación.
- Cobertura geográfica.
- Notas metodológicas y norma técnica.

Antes de publicar o usar un indicador en un análisis relevante, procure que estos campos estén completos, especialmente la fuente, metodología, periodicidad y responsable.

## 9. Captura, importación y revisión de datos

### 9.1 Edición manual

Las modificaciones manuales no sustituyen de inmediato el valor publicado. Se generan como una propuesta para revisión. El valor actual permanece visible hasta que una persona con permiso de aprobación autorice el cambio.

### 9.2 Importación masiva

Ruta: **Administración > Importar**.

El módulo admite plantillas para catálogos, datos históricos, datos complejos e instrumentos. Descargue primero la plantilla correspondiente desde el mismo módulo.

Para datos históricos, cada registro requiere como mínimo:

- Municipio, mediante `municipio_cvegeo` o `municipio_id`.
- Variable, mediante `variable_tecnico` o `variable_id`.
- `anio`.
- `valor`, o un motivo de ausencia válido cuando aplique.

Revise los errores de validación antes de enviar un lote a revisión.

### 9.3 Flujo de aprobación

1. La persona capturista crea un borrador o propuesta.
2. El lote se envía a revisión.
3. La persona revisora valida el contenido, fuente y observaciones.
4. Puede aprobarlo o rechazarlo con una explicación.
5. Al aprobarlo, los datos se incorporan al registro histórico y quedan vinculados al lote que los originó.

No apruebe un lote solo porque no presente errores técnicos. También verifique consistencia temporal, unidades de medida, cobertura territorial, fuente y metodología.

## 10. Auditoría

Ruta: **Administración > Auditoría**.

La auditoría conserva el registro de cambios realizados en entidades relevantes, incluidos indicadores, variables, datos históricos y lotes. Úsela para responder:

- Quién realizó un cambio.
- Qué registro se modificó.
- Cuándo ocurrió.
- Qué lote o proceso estuvo involucrado.

La auditoría apoya trazabilidad; no sustituye la revisión documental de la fuente original.

## 11. Buenas prácticas para el uso de información

- Cite el indicador, año, unidad de medida y fuente al elaborar reportes.
- Compare únicamente datos con metodologías y coberturas compatibles.
- No sume porcentajes, tasas, índices o grados, salvo que exista una metodología explícita para hacerlo.
- Use la polaridad como guía de interpretación, no como una regla automática de decisión.
- Revise los datos atípicos antes de difundirlos.
- Documente cualquier corrección mediante el flujo de revisión.
- No utilice información clasificada como confidencial fuera de los usos autorizados.

## 12. Solución de problemas

| Situación | Acción recomendada |
|---|---|
| La gráfica no carga | Confirme que seleccionó indicador y territorio; recargue la página e inténtelo de nuevo. |
| No aparece el nivel estatal | Verifique que el indicador sea de tipo absoluto. Los valores no sumables no se consultan como total estatal. |
| No hay años disponibles | El indicador puede no tener datos para la selección territorial o no contar con variables públicas. |
| Un dato parece incorrecto | Revise fuente, metodología, año, unidad y el panel de Salud de Datos; después genere una propuesta de corrección. |
| No aparece una opción administrativa | Solicite a la persona administradora que revise el rol y los permisos de su cuenta. |
| Una importación falla | Descargue la plantilla vigente, revise encabezados, identificadores, año, valores y el detalle de errores mostrado por el sistema. |

## 13. Soporte

Al solicitar soporte, incluya:

- URL o módulo donde ocurrió el problema.
- Indicador, municipio o región involucrados.
- Año consultado.
- Captura de pantalla del mensaje mostrado.
- Archivo de importación, si aplica, sin incluir información confidencial no autorizada.
