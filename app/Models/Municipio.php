<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Municipio extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "El municipio '{$this->nombre}' fue {$eventName}"
            );
    }

    protected $fillable = [
        'nombre',
        'slug',
        'microrregion_id',
        'cvegeo',
        'banner_image_url',
        'banner_attribution',
        'cabecera',
        'clima',
        'superficie',
    ];

    protected $casts = [
        'superficie' => 'float',
        'banner_attribution' => 'array',
    ];

    public function microrregion()
    {
        return $this->belongsTo(Microrregion::class);
    }
    public function datosHistoricos()
    {
        return $this->hasMany(DatoHistorico::class);
    }
    public function instrumentos()
    {
        return $this->belongsToMany(Instrumento::class, 'instrumento_municipio')->withTimestamps();
    }
}
