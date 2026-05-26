<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicador_id',
        'nombre_tecnico',
        'nombre_amigable',
        'unidad_medida',
        'es_destacada',
        'es_kpi',
        'mapeo_valores',
        'orden',
    ];

    protected $casts = [
        'mapeo_valores' => 'array',
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
