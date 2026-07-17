<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoteDatoHistorico extends Model
{
    use HasFactory;

    protected $table = 'lote_dato_historicos';

    protected $fillable = [
        'lote_datos_id',
        'fila_origen',
        'municipio_id',
        'variable_id',
        'anio',
        'valor',
        'motivo_sin_dato_id',
        'accion',
        'valor_original',
        'motivo_sin_dato_original_id',
        'dato_historico_updated_at',
    ];

    protected function casts(): array
    {
        return ['dato_historico_updated_at' => 'datetime'];
    }

    public function lote()
    {
        return $this->belongsTo(LoteDatos::class, 'lote_datos_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    public function variable()
    {
        return $this->belongsTo(Variable::class);
    }

    public function motivoSinDato()
    {
        return $this->belongsTo(CatMotivoSinDato::class, 'motivo_sin_dato_id');
    }
}
