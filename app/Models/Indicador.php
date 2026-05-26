<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicador extends Model
{
    use HasFactory;

    protected $fillable = [
        'tematica_id',
        'nombre_amigable',
        'descripcion',
        'fuente',
        'tipo_dato',
        'tipo_grafico_default',
        'metodo_calculo',
        'solo_resumen',
        'priorizar_total',
        'nombre_tecnico',
        'polaridad',
        'orden',
    ];

    public function tematica()
    {
        return $this->belongsTo(Tematica::class);
    }
    public function variables()
    {
        return $this->hasMany(Variable::class);
    }

    public function configuracionFicha()
    {
        return $this->hasOne(ConfiguracionFicha::class);
    }
}
