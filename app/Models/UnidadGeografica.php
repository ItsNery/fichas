<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadGeografica extends Model
{
    use HasFactory;

    public const NIVEL_PAIS = 'pais';
    public const NIVEL_ENTIDAD = 'entidad';
    public const CLAVE_MEXICO = '00';
    public const CLAVE_PUEBLA = '21';

    protected $table = 'unidades_geograficas';

    protected $fillable = [
        'nivel',
        'clave_inegi',
        'nombre',
        'slug',
        'parent_id',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function datosHistoricos()
    {
        return $this->hasMany(DatoGeograficoHistorico::class);
    }
}
