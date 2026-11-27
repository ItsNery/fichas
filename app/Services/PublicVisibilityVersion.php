<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PublicVisibilityVersion
{
    public function fingerprint(): string
    {
        $tables = [
            'dimensions' => ['id', 'visible_en_ficha'],
            'tematicas' => ['id', 'dimension_id', 'visible_en_ficha'],
            'indicadors' => ['id', 'tematica_id', 'visible_en_ficha', 'clasificacion', 'estado_publicacion'],
            'variables' => ['id', 'indicador_id', 'visible_en_ficha'],
            'configuracion_fichas' => ['id', 'indicador_id', 'activo', 'tipo_visualizacion'],
            'configuracion_ficha_variable' => ['id', 'configuracion_ficha_id', 'variable_id'],
        ];

        $state = [];
        foreach ($tables as $table => $columns) {
            $state[$table] = DB::table($table)->orderBy('id')->get($columns)->all();
        }

        return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
    }
}
