<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasFactory;

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
