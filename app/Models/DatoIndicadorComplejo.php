<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DatoIndicadorComplejo extends Model
{
    use HasFactory, LogsActivity;
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
        'lote_datos_id',
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

    public function loteDatos()
    {
        return $this->belongsTo(LoteDatos::class, 'lote_datos_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
