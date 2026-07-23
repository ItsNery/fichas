# Revisión de metadatos DSS

Archivo de trabajo: `docs/dss-indicadores-propuesta.csv`.

## Alcance

La matriz contiene una propuesta no destructiva para los indicadores actuales. No modifica la base de datos. La fecha de próxima actualización se deja pendiente cuando la fuente no publica un calendario explícito.

## Criterios

- `ascendente`: un valor mayor suele representar mayor cobertura, capacidad o resultado favorable.
- `descendente`: un valor menor suele representar menor carencia, riesgo o presión desfavorable.
- `neutro`: indicador descriptivo, estructural, de composición o sin dirección normativa inequívoca.
- `periodicidad_propuesta`: inferida de la naturaleza de la fuente y de los cortes observados en el campo `fuente`.
- `ultima_referencia_en_fuente`: último año mencionado por la referencia almacenada, no necesariamente la fecha de publicación.
- `proxima_actualizacion`: deliberadamente vacía hasta confirmar el calendario oficial de cada fuente.

## Validación requerida

1. Confirmar polaridad de indicadores compuestos o de capacidad institucional con el área responsable.
2. Confirmar periodicidad directamente en el calendario o ficha metodológica de la fuente.
3. Agregar URL oficial y fecha de consulta por indicador antes de publicar el metadato.
4. No usar una fecha estimada como alerta de vencimiento sin marcarla como estimación.

La generación se ejecuta con `php scratch/generate_dss_metadata.php` y consulta el catálogo vigente de la base de datos configurada en `.env`.
