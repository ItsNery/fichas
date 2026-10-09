<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DatoGeograficoHistorico extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'unidad_geografica_id',
        'variable_id',
        'anio',
        'valor',
        'motivo_sin_dato_id',
        'lote_datos_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "El dato geográfico fue {$event}");
    }

    public function unidadGeografica()
    {
        return $this->belongsTo(UnidadGeografica::class);
    }

    public function variable()
    {
        return $this->belongsTo(Variable::class);
    }

    public function motivoSinDato()
    {
        return $this->belongsTo(CatMotivoSinDato::class, 'motivo_sin_dato_id');
    }

    public function loteDatos()
    {
        return $this->belongsTo(LoteDatos::class, 'lote_datos_id');
    }

    public function getValorDisplayAttribute(): ?string
    {
        if ($this->valor === null) {
            return null;
        }

        $mapa = $this->variable?->mapeo_valores;
        $clave = (int) $this->valor;

        return $mapa && isset($mapa[$clave])
            ? $mapa[$clave]
            : number_format((float) $this->valor, 2);
    }
}
