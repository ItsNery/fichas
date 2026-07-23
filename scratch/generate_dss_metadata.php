<?php

use App\Models\Indicador;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$positive = [
    'escolaridad', 'alfabet', 'derechohabiente', 'personal médico', 'acceso al servicio',
    'disponen de drenaje', 'disponen de energía', 'disponen de excusado', 'disponen de sanitario',
    'disponen de computadora', 'recolección', 'tratamiento de aguas', 'plantas potabilizadoras',
    'capacidad instalada', 'volumen suministrado', 'áreas naturales protegidas', 'ocupación',
    'participación económica', 'población económicamente activa', 'personal ocupado', 'valor agregado',
    'producción bruta', 'ingresos por remesas', 'valor de la producción', 'volumen de producción',
    'cuartos ocupados', 'llegada de turistas', 'estadía promedio', 'pib turístico', 'sucursales bancarias',
    'cajeros automáticos', 'tienen una cuenta', 'paradas de transporte', 'recursos federales',
    'fortamun per cápita', 'policías capacitados', 'elementos o policías que conforman', 'llamadas recibidas que fueron atendidas',
    'instrumentos de planeación', 'producción agrícola', 'producción pecuaria', 'producción de leche',
    'residuos recolectados de manera selectiva', 'población con acceso', 'viviendas particulares habitadas que disponen',
];

$negative = [
    'pobreza', 'carencia', 'rezago', 'marginación', 'hacinamiento', 'mortalidad', 'defunciones',
    'divorcios', 'desocupación', 'corrupción', 'inseguridad', 'delictiva', 'homicidios', 'víctimas de delito',
    'accidentes', 'desechan sus residuos de forma inadecuada', 'queman los residuos', 'contaminantes',
    'incendios', 'asentamientos humanos irregulares', 'tiempo promedio de traslado', 'gini', 'ingreso inferior',
    'accesibilidad baja', 'superficie de asentamientos humanos irregulares', 'índice de motorización',
];

$periodicity = static function (string $source, string $name): array {
    $text = Str::lower(Str::ascii($source . ' ' . $name));
    foreach ([
        ['pobreza municipal', 'Quinquenal', 'CONEVAL publica mediciones municipales por cortes quinquenales.'],
        ['censo de población', 'Decenal', 'El Censo de Población y Vivienda tiene periodicidad decenal; cortes intercensales deben documentarse.'],
        ['censos económicos', 'Quinquenal', 'Los Censos Económicos se levantan cada cinco años.'],
        ['censo nacional de gobiernos municipales', 'Bienal', 'La serie de referencia muestra cortes bienales (2021, 2023, 2025).'],
        ['envipe', 'Anual', 'La ENVIPE se publica anualmente.'],
        ['estadísticas de defunciones', 'Anual', 'Las estadísticas de defunciones registradas se publican anualmente.'],
        ['estadística de natalidad', 'Anual', 'Las estadísticas de natalidad se publican anualmente.'],
        ['nacimientos registrados', 'Anual', 'La estadística de nacimientos registrados se publica anualmente.'],
        ['estadística de matrimonios', 'Anual', 'La estadística de matrimonios se publica anualmente.'],
        ['estadística de divorcios', 'Anual', 'La estadística de divorcios se publica anualmente.'],
        ['sesnsp', 'Mensual', 'La incidencia delictiva del SESNSP se actualiza con cortes mensuales.'],
        ['imss', 'Mensual', 'Los registros administrativos del IMSS se actualizan mensualmente; el resumen puede ser anual.'],
        ['datatur', 'Mensual', 'La actividad hotelera de DATATUR tiene reportes mensuales; algunos indicadores son anuales.'],
        ['siap', 'Anual', 'Los datos productivos agrícolas y pecuarios se consolidan por ciclo o año agrícola.'],
        ['cnbv', 'Anual', 'El Panorama de Inclusión Financiera se publica anualmente.'],
        ['conagua', 'Anual', 'Los anuarios estadísticos de CONAGUA se publican por edición anual.'],
        ['conafor', 'Anual', 'El concentrado de incendios forestales se consolida anualmente.'],
        ['semarnat', 'Plurianual', 'Los inventarios de emisiones no tienen un calendario anual uniforme.'],
        ['denue', 'Anual', 'El DENUE tiene actualizaciones periódicas; la serie indicada es anual.'],
        ['vehículos de motor', 'Anual', 'La estadística de vehículos registrados se publica anualmente.'],
        ['marco geoestadístico', 'Anual', 'El marco geoestadístico se actualiza por versión, normalmente anual.'],
    ] as [$needle, $value, $reason]) {
        if (Str::contains($text, Str::ascii($needle))) {
            return [$value, $reason, 'media'];
        }
    }
    return ['Por confirmar', 'La fuente registrada no permite establecer un calendario confiable.', 'baja'];
};

$classify = static function (string $name) use ($positive, $negative): array {
    $text = Str::lower(Str::ascii($name));
    $matches = static function (array $terms) use ($text): bool {
        foreach ($terms as $term) {
            if (Str::contains($text, Str::ascii($term))) {
                return true;
            }
        }
        return false;
    };

    if ($matches($positive) && $matches($negative)) {
        return ['neutro', 'baja', 'El indicador mezcla dimensiones favorables y desfavorables o requiere revisar sus variables.'];
    }
    if ($matches($positive)) {
        return ['asendente', 'media', 'Un valor mayor suele representar mayor cobertura, capacidad o resultado favorable.'];
    }
    if ($matches($negative)) {
        return ['descendente', 'media', 'Un valor menor suele representar menor carencia, riesgo o presión desfavorable.'];
    }
    return ['neutro', 'alta', 'Indicador descriptivo, estructural o sin dirección normativa inequívoca.'];
};

$rows = Indicador::with('tematica.dimension')->orderBy('id')->get();
$out = fopen('php://stdout', 'wb');
fputcsv($out, [
    'id', 'dimension', 'tematica', 'indicador', 'fuente_registrada', 'polaridad_actual',
    'polaridad_propuesta', 'confianza_polaridad', 'justificacion_polaridad', 'periodicidad_propuesta',
    'confianza_periodicidad', 'justificacion_periodicidad', 'ultima_referencia_en_fuente',
    'proxima_actualizacion', 'estado_fecha', 'url_oficial', 'fecha_revision',
]);

foreach ($rows as $indicator) {
    [$polarity, $polarityConfidence, $polarityReason] = $classify($indicator->nombre_amigable);
    [$frequency, $frequencyReason, $frequencyConfidence] = $periodicity($indicator->fuente ?? '', $indicator->nombre_amigable);
    preg_match_all('/\b20\d{2}\b/', $indicator->fuente ?? '', $years);
    $latest = null;
    foreach ($years[0] as $year) {
        $normalized = (int) $year;
        // Exclude future projection horizons such as CONAPO 1990-2040.
        if ($normalized <= (int) date('Y')) {
            $latest = max($latest ?? 0, $normalized);
        }
    }

    $sourceText = Str::lower(Str::ascii($indicator->fuente ?? ''));
    $urls = [];
    foreach ([
        ['inegi', 'https://www.inegi.org.mx/'], ['coneval', 'https://www.coneval.org.mx/'],
        ['conapo', 'https://www.gob.mx/conapo'], ['sep.', 'https://www.planeacion.sep.gob.mx/'],
        ['banxico', 'https://www.banxico.org.mx/'], ['imss', 'https://www.imss.gob.mx/'],
        ['siap', 'https://www.gob.mx/siap'], ['datatur', 'https://www.datatur.sectur.gob.mx/'],
        ['cnbv', 'https://www.gob.mx/cnbv'], ['conagua', 'https://www.gob.mx/conagua'],
        ['semarnat', 'https://www.gob.mx/semarnat'], ['conanp', 'https://www.gob.mx/conanp'],
        ['conafor', 'https://www.gob.mx/conafor'], ['sesnsp', 'https://www.gob.mx/sesnsp'],
    ] as [$needle, $candidate]) {
        if (Str::contains($sourceText, $needle) && !in_array($candidate, $urls, true)) {
            $urls[] = $candidate;
        }
    }

    fputcsv($out, [
        $indicator->id,
        $indicator->tematica?->dimension?->nombre,
        $indicator->tematica?->nombre,
        $indicator->nombre_amigable,
        $indicator->fuente,
        $indicator->polaridad,
        $polarity,
        $polarityConfidence,
        $polarityReason,
        $frequency,
        $frequencyConfidence,
        $frequencyReason,
        $latest ?: null,
        null,
        'Por confirmar con calendario oficial',
        implode(' ; ', $urls) ?: null,
        date('Y-m-d'),
    ]);
}

fclose($out);
