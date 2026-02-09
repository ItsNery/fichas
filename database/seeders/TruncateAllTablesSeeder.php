<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TruncateAllTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ejecuta php artisan db:seed --class=TruncateAllTablesSeeder
     * @return void
     */
    public function run()
    {
        // Deshabilitamos temporalmente la revisión de llaves foráneas para evitar errores
        Schema::disableForeignKeyConstraints();

        // Lista de todas las tablas que quieres vaciar
        $tables = [
            'dato_historicos',
            'variables',
            'indicadors',
            // 'tematicas',
            // 'dimensions',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        // Volvemos a habilitar la revisión de llaves foráneas
        Schema::enableForeignKeyConstraints();

        $this->command->info('¡Todas las tablas especificadas han sido vaciadas!');
    }
}