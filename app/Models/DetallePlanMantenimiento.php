<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePlanMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'detalles_planes_mantenimiento';

    protected $fillable = [
        'plan_id', 'equipo_id', 'equipo_codigo', 'equipo_tipo', 'equipo_modelo', 'equipo_ubicacion', 'mes_programado', 'estado', 'fecha_ejecucion', 'tecnico_responsable'
    ];

    public function plan()
    {
        return $this->belongsTo(PlanMantenimiento::class, 'plan_id');
    }

    public function ejecuciones()
    {
        return $this->hasMany(EjecucionMantenimiento::class, 'detail_id');
    }
}
