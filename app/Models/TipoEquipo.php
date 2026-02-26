<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    use HasFactory;

    protected $table = 'tipo_equipos';

    protected $fillable = ['nombre', 'descripcion', 'frecuencia_anual', 'considerar_recambio'];

    protected $casts = [
        'considerar_recambio' => 'boolean',
    ];

    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'tipo_equipo_id');
    }
}
