<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatMotivoSinDato extends Model
{
    use HasFactory;

    protected $table = 'cat_motivos_sin_dato';
    protected $fillable = ['codigo', 'nombre'];
}
