<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $examples = [
            // Demográfica y Social: treemap faltante y líneas con comparativa.
            [1, [1, 2, 3], 'treemap', 'Composición de la población por sexo', 30, false, 'avg'],
            [3, [48], 'lineas', 'Relación hombres-mujeres: evolución y contexto', 31, true, 'avg'],

            // Económico: completa los tipos restantes.
            [60, [123], 'kpi', 'Participación económica', 32, false, 'avg'],
            [57, [117], 'lineas', 'Evolución de ingresos por remesas', 33, true, 'avg'],
            [67, [138], 'mapa', 'Valor de la producción agrícola en el territorio', 34, false, 'avg'],
            [56, [113, 114, 115, 116], 'treemap', 'Composición del valor agregado sectorial', 35, false, 'avg'],
            [56, [113, 114], 'scatter', 'Relación entre sectores económicos', 36, false, 'avg'],
            [56, [113, 114, 115, 116], 'piramide', 'Balance visual del valor agregado', 37, false, 'avg'],

            // Geográfica y Medio Ambiente: mapa, líneas y treemap ya existen.
            [96, [185], 'kpi', 'Recolección selectiva de residuos', 38, false, 'avg'],
            [98, [187, 188, 189, 190], 'barras', 'Contaminantes atmosféricos por tipo', 39, true, 'sum'],
            [98, [187, 188], 'scatter', 'Relación entre contaminantes atmosféricos', 40, false, 'avg'],
            [98, [187, 188, 189, 190], 'piramide', 'Balance visual de emisiones', 41, false, 'avg'],

            // Gobierno, Seguridad e Impartición de Justicia: treemap y líneas ya existen.
            [153, [319], 'kpi', 'Recursos FORTAMUN per cápita', 42, false, 'avg'],
            [137, [229, 230, 315, 316], 'barras', 'Recursos federales por componente', 43, true, 'avg'],
            [143, [289, 290, 291, 292, 293, 294], 'mapa', 'Accidentes de tránsito en el territorio', 44, false, 'avg'],
            [138, [231, 232], 'scatter', 'Corrupción percibida por autoridad', 45, false, 'avg'],
            [141, [252, 253, 254], 'piramide', 'Componentes de capacitación policial', 46, false, 'avg'],
        ];

        foreach ($examples as [$indicadorId, $variableIds, $type, $title, $order, $comparison, $mode]) {
            if (DB::table('configuracion_fichas')->where('titulo_reporte', $title)->exists()) {
                continue;
            }

            if (!DB::table('indicadors')->where('id', $indicadorId)->exists()) {
                continue;
            }

            $configId = DB::table('configuracion_fichas')->insertGetId([
                'indicador_id' => $indicadorId,
                'seccion' => 'showcase',
                'orden' => $order,
                'tipo_visualizacion' => $type,
                'anios_historial' => $type === 'treemap' || $type === 'piramide' || $type === 'kpi' ? 1 : 3,
                'titulo_reporte' => $title,
                'subtitulo_reporte' => $comparison ? 'Comparativa regional por ' . ($mode === 'sum' ? 'sumatoria' : 'promedio') : 'Vista demostrativa del indicador',
                'plantilla_narrativa' => 'En el último corte, {municipio} registra {valor}.',
                'clase_grid' => 'col-md-6',
                'icono' => 'fa-solid fa-chart-simple',
                'mostrar_comparativa' => $comparison,
                'ajustes_visuales' => json_encode(['benchmark_mode' => $mode]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($variableIds as $variableId) {
                if (DB::table('variables')->where('id', $variableId)->exists()) {
                    DB::table('configuracion_ficha_variable')->insert([
                        'configuracion_ficha_id' => $configId,
                        'variable_id' => $variableId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('configuracion_fichas')
            ->whereBetween('orden', [30, 46])
            ->delete();
    }
};
