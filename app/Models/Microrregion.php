<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Microrregion extends Model
{
    use HasFactory;

    public function macrorregion()
    {
        return $this->belongsTo(Macrorregion::class);
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class);
    }
}
