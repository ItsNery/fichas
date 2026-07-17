<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Variable extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'indicador_id',
        'nombre_tecnico',
        'nombre_amigable',
        'unidad_medida',
        'es_destacada',
        'es_kpi',
        'visible_en_ficha',
        'es_construida',
        'formula_tipo',
        'formula_config',
        'mapeo_valores',
        'orden',
        'tipo_valor',
        'valor_minimo',
        'valor_maximo',
        'definicion_operativa',
        'fuente_primaria',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "La variable '{$this->nombre_amigable}' fue {$eventName}"
            );
    }

    protected $casts = [
        'visible_en_ficha' => 'boolean',
        'mapeo_valores'  => 'array',
        'formula_config' => 'array',
    ];

    public function indicador()
    {
        return $this->belongsTo(Indicador::class);
    }
    public function datosHistoricos()
    {
        return $this->hasMany(DatoHistorico::class);
    }

    public function configuracionesFicha()
    {
        return $this->belongsToMany(ConfiguracionFicha::class);
    }
}
