<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'color',
        'nombre_tecnico',
        'orden',
    ];

    public function tematicas()
    {
        return $this->hasMany(Tematica::class);
    }
}
