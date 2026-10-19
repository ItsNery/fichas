<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Indicador extends Model
{
    /**
     * Periodicidades declarables: clave => intervalo en años y etiqueta.
     */
    public const PERIODICIDADES = [
        'Anual' => ['intervalo' => 1, 'etiqueta' => 'Anual (cada 1 año)'],
        'Bienal' => ['intervalo' => 2, 'etiqueta' => 'Bienal (cada 2 años)'],
        'Trienal' => ['intervalo' => 3, 'etiqueta' => 'Trienal (cada 3 años)'],
        'Cuatrienal' => ['intervalo' => 4, 'etiqueta' => 'Cuatrienal (cada 4 años)'],
        'Quinquenal' => ['intervalo' => 5, 'etiqueta' => 'Quinquenal (cada 5 años)'],
        'Irregular' => ['intervalo' => null, 'etiqueta' => 'Irregular (sin ciclo fijo)'],
    ];

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
