<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Macrorregion extends Model
{
    use HasFactory;
    public function microrregiones()
    {
        return $this->hasMany(Microrregion::class);
    }
}
