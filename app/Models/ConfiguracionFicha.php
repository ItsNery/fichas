<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionFicha extends Model
{
    protected $table = 'configuracion_fichas';

    protected $fillable = [
        'indicador_id',
        'seccion',
        'orden',
        'tipo_visualizacion',
        'anios_historial',
        'titulo_reporte',
        'subtitulo_reporte',
        'plantilla_narrativa',
        'clase_grid',
        'icono',
        'mostrar_comparativa',
        'ajustes_visuales',
        'activo'
    ];

    protected $casts = [
        'ajustes_visuales' => 'array',
        'activo' => 'boolean',
        'mostrar_comparativa' => 'boolean',
    ];

    public function indicador()
    {
        return $this->belongsTo(Indicador::class);
    }

    public function variables()
    {
        return $this->belongsToMany(Variable::class);
    }
}
