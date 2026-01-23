<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanRecambio extends Model
{
    use HasFactory;

    protected $table = 'plan_recambios';

    protected $fillable = [
        'anio',
        'nombre',
        'creado_por',
        'fecha_creacion',
        'presupuesto_estimado',
        'total_equipos',
        'estado',
    ];

    protected $casts = [
        'fecha_creacion' => 'date',
        'presupuesto_estimado' => 'float',
        'total_equipos' => 'integer',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePlanRecambio::class, 'plan_id');
    }
}
