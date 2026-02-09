<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatoIndicadorComplejo extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dato_indicador_complejos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'indicador_id',
        'municipio_id',
        'anio',
        'datos',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'datos' => 'array', // Esto convierte automáticamente el JSON a un array de PHP y viceversa
    ];

    /**
     * Get the indicador that owns the data.
     */
    public function indicador()
    {
        return $this->belongsTo(Indicador::class);
    }

    /**
     * Get the municipio that owns the data.
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
}
