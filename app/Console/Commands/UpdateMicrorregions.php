<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class UpdateMicrorregions extends Command
{
    protected $signature = 'update:microrregions';
    protected $description = 'Actualiza microrregions y mapeo en municipios desde Excel';

    public function handle()
    {
        try {
            // 1. Desactivar FK checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 2. Setear a NULL y Truncar (Esto hace commits automáticos, por eso va ANTES de la transacción)
            DB::table('municipios')->update(['microrregion_id' => null]);
            DB::table('microrregions')->truncate();

            // 3. AHORA SÍ, abrimos la transacción para la inserción de datos
            DB::beginTransaction();

            // --- IMPORTAR MICRORREGIONES ---
            $nuevasMicrorregionsPath = 'nuevas_microrregions.xls';
            $fullPathMicrorregions = Storage::path($nuevasMicrorregionsPath);

            if (!Storage::exists($nuevasMicrorregionsPath)) {
                $this->error("No se encontró el archivo de microrregiones en: $nuevasMicrorregionsPath");
                DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Limpiamos antes de salir
                return;
            }

            $this->info("Leyendo archivo de microrregiones: $nuevasMicrorregionsPath");

            $nuevasMicrorregions = Excel::toArray(new \stdClass(), $fullPathMicrorregions)[0];
            array_shift($nuevasMicrorregions); // Salta headers

            $insertData = [];
            foreach ($nuevasMicrorregions as $row) {
                if (empty($row[0]) || empty($row[2])) {
                    continue; // salta filas vacías
                }

                $insertData[] = [
                    'id'              => (int) $row[0],     // Columna A
                    'macrorregion_id' => (int) $row[1],     // Columna B
                    'nombre'          => trim($row[2]),     // Columna C
                ];
            }

            if (empty($insertData)) {
                $this->error("No se encontraron datos válidos en el Excel de microrregiones.");
                DB::rollBack();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                return;
            }

            DB::table('microrregions')->insert($insertData);
            $this->info("Insertadas " . count($insertData) . " nuevas microrregiones.");


            // --- IMPORTAR MAPEO DE MUNICIPIOS ---
            $mapeoPath = 'nuevo_mapeo_municipios.xls';
            $fullPathMapeo = Storage::path($mapeoPath);

            if (!Storage::exists($mapeoPath)) {
                $this->error("No se encontró el archivo de mapeo en: $mapeoPath");
                DB::rollBack();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                return;
            }

            $this->info("Leyendo archivo de mapeo: $mapeoPath");

            $mapeo = Excel::toArray(new \stdClass(), $fullPathMapeo)[0];
            array_shift($mapeo); // Salta headers 

            foreach ($mapeo as $row) {
                if (empty($row[1]) || empty($row[2])) { // Asegura que cvegeo y el ID no estén vacíos
                    continue;
                }

                $cvegeo = trim($row[1]);              // Columna B (índice 1): cvegeo
                $newId = (int) $row[2];               // Columna C (índice 2): microrregion_id directamente del Excel

                DB::table('municipios')
                    ->where('cvegeo', $cvegeo)
                    ->update(['microrregion_id' => $newId]);
                
                $this->info("Actualizado cvegeo: $cvegeo con nuevo ID: $newId");
            }

            // Confirmar transacción y reactivar llaves foráneas
            DB::commit();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Actualización completada exitosamente.');

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('Error: ' . $e->getMessage());
        }
    }
}