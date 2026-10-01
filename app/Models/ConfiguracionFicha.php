<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfiguracionFicha extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "La configuración fue {$eventName}"
            );
    }
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

    public function scopeVisibleEnPerfilPublico(Builder $query): Builder
    {
        return $query
            ->where('activo', true)
            ->where('tipo_visualizacion', '!=', 'scatter');
    }

    public function indicador()
    {
        return $this->belongsTo(Indicador::class);
    }

    public function variables()
    {
        return $this->belongsToMany(Variable::class)
            ->orderBy('configuracion_ficha_variable.id');
    }
}
