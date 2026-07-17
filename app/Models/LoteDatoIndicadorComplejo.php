<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoteDatoIndicadorComplejo extends Model
{
    use HasFactory;

    protected $fillable = [
        'lote_datos_id', 'fila_origen', 'indicador_id', 'municipio_id', 'anio',
        'datos', 'datos_originales', 'dato_complejo_updated_at', 'accion',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'datos_originales' => 'array',
            'dato_complejo_updated_at' => 'datetime',
        ];
    }

    public function lote()
    {
        return $this->belongsTo(LoteDatos::class, 'lote_datos_id');
    }

    public function indicador()
    {
        return $this->belongsTo(Indicador::class);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
}
