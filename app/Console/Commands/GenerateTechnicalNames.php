<?php
namespace App\Console\Commands;

use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\Variable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTechnicalNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-technical-names'; // Este será el comando que ejecutaremos

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera retroactivamente los nombres técnicos para los catálogos existentes.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando la generación de nombres técnicos...');

        // --- Para Dimensiones, Temáticas e Indicadores (sin cambios, solo llena vacíos) ---
        $this->line('Procesando Dimensiones...');
        foreach (Dimension::all() as $item) {
            if (empty($item->nombre_tecnico)) {
                $item->nombre_tecnico = Str::slug($item->nombre, '_');
                $item->save();
            }
        }

        $this->line('Procesando Temáticas...');
        foreach (Tematica::all() as $item) {
            if (empty($item->nombre_tecnico)) {
                $item->nombre_tecnico = Str::slug($item->nombre, '_');
                $item->save();
            }
        }

        $this->line('Procesando Indicadores...');
        foreach (Indicador::all() as $item) {
            if (empty($item->nombre_tecnico)) {
                $item->nombre_tecnico = Str::slug($item->nombre_amigable, '_');
                $item->save();
            }
        }

        // --- INICIO DE LA CORRECCIÓN PARA VARIABLES ---
        $this->warn('Forzando la regeneración de nombres técnicos para TODAS las Variables...');

        // Usamos 'with' para cargar la relación y evitar miles de consultas a la BD
        foreach (Variable::with('indicador')->get() as $variable) {
            // Creamos una base para el slug a partir del nombre técnico del indicador padre
            $indicadorSlug = $variable->indicador->nombre_tecnico ?? Str::slug($variable->indicador->nombre_amigable, '_');
            $variableSlug  = Str::slug($variable->nombre_amigable, '_');

            // Combinamos ambos para crear un nombre técnico único y descriptivo
            $nuevoNombreTecnico = $indicadorSlug . '_' . $variableSlug;

            // Actualizamos el registro en la base de datos
            $variable->nombre_tecnico = $nuevoNombreTecnico;
            $variable->save();
        }
        // --- FIN DE LA CORRECCIÓN ---

        $this->info('¡Generación de nombres técnicos completada con éxito!');
        return 0;
    }
}
