<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';

    protected $fillable = ['equipo_id', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado', 'costo', 'proveedor', 'tipo'];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}
