<?php

namespace Database\Seeders;

use App\Models\CatMotivoSinDato;
use Illuminate\Database\Seeder;

class MotivosSinDatoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $motivos =[
            ['codigo' => 'NA', 'nombre' => 'No aplica'],
            ['codigo' => 'ND', 'nombre' => 'No disponible'],
            ['codigo' => 'NSS', 'nombre' => 'No se sabe'],
            ['codigo' => 'NULL', 'nombre' => 'Nulo'],
            ['codigo' => 'MI', 'nombre' => 'Muestra insuficiente'],
        ];
        foreach($motivos as $motivo){
            CatMotivoSinDato::updateOrCreate(['codigo' => $motivo['codigo']], $motivo);
        }
    }
}
