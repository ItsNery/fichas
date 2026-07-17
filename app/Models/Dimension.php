<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Dimension extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "La dimensión '{$this->nombre}' fue {$eventName}"
            );
    }
    protected $fillable = [
        'nombre',
        'color',
        'nombre_tecnico',
        'orden',
        'visible_en_ficha',
    ];

    public function tematicas()
    {
        return $this->hasMany(Tematica::class);
    }
}
