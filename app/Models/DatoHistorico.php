<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DatoHistorico extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "El dato histórico fue {$eventName}"
            );
    }
    protected $fillable = [
        'municipio_id',
        'variable_id',
        'valor',
        'anio',
        'motivo_sin_dato_id',
        'lote_datos_id',
    ];

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
    public function variable()
    {
        return $this->belongsTo(Variable::class);
    }

    /**
     * ¡NUEVO ACCESSOR!
     * Crea un atributo virtual 'valor_display' que traduce el valor si es necesario.
     */
    public function getValorDisplayAttribute()
    {
        $mapa = $this->variable->mapeo_valores;

        // Convertimos el valor a un entero para que coincida con las llaves del JSON ("1", "2", etc.)
        $valorComoEntero = (int) $this->valor;

        // Si existe un mapa Y nuestro valor es una clave válida en él...
        if ($mapa && isset($mapa[$valorComoEntero])) {
            // ...devolvemos el texto correspondiente (ej. "Bajo").
            return $mapa[$valorComoEntero];
        }

        // Si no, devolvemos el valor numérico bien formateado.
        return number_format($this->valor, 2);
    }
    public function motivoSinDato()
    {
        return $this->belongsTo(CatMotivoSinDato::class, 'motivo_sin_dato_id');
    }

    public function loteDatos()
    {
        return $this->belongsTo(LoteDatos::class, 'lote_datos_id');
    }
}
