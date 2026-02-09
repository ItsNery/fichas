<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tematica extends Model
{
    use HasFactory;
        protected $fillable = [
        'dimension_id',
        'nombre',
        'nombre_tecnico',
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
