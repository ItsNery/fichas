<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LoteDatos extends Model
{
    use HasFactory, LogsActivity;

    public const BORRADOR = 'borrador';
    public const EN_REVISION = 'en_revision';
    public const APROBADO = 'aprobado';
    public const RECHAZADO = 'rechazado';

    protected $table = 'lotes_datos';

    protected $fillable = [
        'tipo',
        'estado',
        'archivo_original',
        'archivo_path',
        'archivo_hash',
        'usuario_carga_id',
        'usuario_revision_id',
        'total_filas',
        'filas_insertar',
        'filas_actualizar',
        'observaciones',
        'enviado_revision_at',
        'revisado_at',
        'aplicado_at',
    ];

    protected function casts(): array
    {
        return [
            'enviado_revision_at' => 'datetime',
            'revisado_at' => 'datetime',
            'aplicado_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['estado', 'usuario_revision_id', 'observaciones', 'total_filas'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $event) => "El lote de datos #{$this->id} fue {$event}");
    }

    public function usuarioCarga()
    {
        return $this->belongsTo(User::class, 'usuario_carga_id');
    }

    public function usuarioRevision()
    {
        return $this->belongsTo(User::class, 'usuario_revision_id');
    }

    public function filas()
    {
        return $this->hasMany(LoteDatoHistorico::class, 'lote_datos_id');
    }

    public function datosHistoricos()
    {
        return $this->hasMany(DatoHistorico::class, 'lote_datos_id');
    }

    public function filasComplejas()
    {
        return $this->hasMany(LoteDatoIndicadorComplejo::class, 'lote_datos_id');
    }
}
