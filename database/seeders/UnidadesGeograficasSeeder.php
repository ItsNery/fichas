<?php

namespace Database\Seeders;

use App\Models\UnidadGeografica;
use Illuminate\Database\Seeder;

class UnidadesGeograficasSeeder extends Seeder
{
    public function run(): void
    {
        $mexico = UnidadGeografica::updateOrCreate(
            [
                'nivel' => UnidadGeografica::NIVEL_PAIS,
                'clave_inegi' => UnidadGeografica::CLAVE_MEXICO,
            ],
            [
                'nombre' => 'Estados Unidos Mexicanos',
                'slug' => 'mexico',
                'parent_id' => null,
                'activo' => true,
            ]
        );

        UnidadGeografica::updateOrCreate(
            [
                'nivel' => UnidadGeografica::NIVEL_ENTIDAD,
                'clave_inegi' => UnidadGeografica::CLAVE_PUEBLA,
            ],
            [
                'nombre' => 'Puebla',
                'slug' => 'puebla',
                'parent_id' => $mexico->id,
                'activo' => true,
            ]
        );
    }
}
