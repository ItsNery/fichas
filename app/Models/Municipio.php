<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'microrregion_id',
        'cvegeo',
        'banner_image_url',
        'logo_url',
        'presidente_municipal',
        'periodo_gobierno',
        'cabecera',
        'clima',
        'superficie',
    ];

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
