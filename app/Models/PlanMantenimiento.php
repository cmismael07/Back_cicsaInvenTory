<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'planes_mantenimiento';

    protected $fillable = [
        'nombre', 'anio', 'creado_por', 'fecha_creacion', 'estado', 'ciudad_id', 'ciudad_nombre'
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePlanMantenimiento::class, 'plan_id');
    }
}
