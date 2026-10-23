<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Formula extends Model
{
    use HasFactory, LogsActivity;

    public const AMBITO_MUNICIPAL = 'municipal';
    public const AMBITO_ESTATAL = 'estatal';
    public const AMBITO_REGIONAL = 'regional';

    protected $fillable = [
        'nombre',
        'nombre_tecnico',
        'descripcion',
        'ambito',
        'expresion',
        'slots',
        'activa',
        'usuario_creacion_id',
    ];

    protected $casts = [
        'slots' => 'array',
        'activa' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) =>
                "La fórmula '{$this->nombre}' fue {$eventName}"
            );
    }

    public function getAmbitoLabelAttribute(): string
    {
        return match ($this->ambito) {
            self::AMBITO_MUNICIPAL => 'Municipal',
            self::AMBITO_ESTATAL => 'Estatal (Puebla)',
            self::AMBITO_REGIONAL => 'Regional',
            default => ucfirst((string) $this->ambito),
        };
    }

    public function slots(): array
    {
        return collect($this->slots ?? [])->map(fn ($slot) => [
            'clave' => strtoupper((string) ($slot['clave'] ?? '')),
            'nombre' => (string) ($slot['nombre'] ?? ''),
            'variable_id' => ! empty($slot['variable_id']) ? (int) $slot['variable_id'] : null,
        ])->filter(fn ($slot) => $slot['clave'] !== '')->values()->all();
    }
}
