<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instrumento extends Model
{
    use HasFactory;

    protected $table = 'instrumentos_planeacion';

    protected $fillable = ['nombre', 'descripcion'];

    public function municipios()
    {
        return $this->belongsToMany(Municipio::class, 'instrumento_municipio')->withTimestamps();
    }
}
