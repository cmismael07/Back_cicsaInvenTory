<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    protected $fillable = ['nombre','descripcion'];

    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'ubicacion_id');
    }
}
