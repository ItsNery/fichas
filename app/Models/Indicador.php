<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Indicador extends Model
{
    public function scopeVisiblePublicamente($query)
    {
        return $query->where('visible_en_ficha', true)
            ->whereHas('tematica', fn ($tematica) => $tematica->where('visible_en_ficha', true)
                ->whereHas('dimension', fn ($dimension) => $dimension->where('visible_en_ficha', true)));
    }

    use HasFactory, LogsActivity;

    protected $fillable = [
        'tematica_id',
        'nombre_amigable',
        'descripcion',
        'fuente',
        'tipo_dato',
        'tipo_grafico_default',
        'metodo_calculo',
        'solo_resumen',
        'es_complejo',
        'es_construido',
        'priorizar_total',
        'nombre_tecnico',
        'polaridad',
        'orden',
        'visible_en_ficha',
        'responsable',
        'periodicidad',
        'fecha_vigencia_inicio',
        'fecha_vigencia_fin',
        'metodologia',
        'metodologia_url',
        'clasificacion',
        'estado_publicacion',
        'cobertura_geografica',
        'unidad_responsable',
        'notas_metodologicas',
        'norma_tecnica',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "El indicador '{$this->nombre_amigable}' fue {$eventName}"
            );
    }

    public function tematica()
    {
        return $this->belongsTo(Tematica::class);
    }
    public function variables()
    {
        return $this->hasMany(Variable::class);
    }

    public function variablesPublicas()
    {
        return $this->hasMany(Variable::class)->where('visible_en_ficha', true);
    }

    public function configuracionFicha()
    {
        return $this->hasOne(ConfiguracionFicha::class);
    }
}
