<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Tematica extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "La temática '{$this->nombre}' fue {$eventName}"
            );
    }
        protected $fillable = [
        'dimension_id',
        'nombre',
        'nombre_tecnico',
        'orden',
        'visible_en_ficha',
    ];
    
    public function dimension()
    {
        return $this->belongsTo(Dimension::class);
    }
    public function indicadores()
    {
        return $this->hasMany(Indicador::class);
    }
}
