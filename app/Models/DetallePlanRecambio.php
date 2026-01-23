<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePlanRecambio extends Model
{
    use HasFactory;

    protected $table = 'detalle_plan_recambios';

    protected $fillable = [
        'plan_id',
        'equipo_id',
        'equipo_codigo',
        'equipo_modelo',
        'equipo_marca',
        'equipo_antiguedad',
        'valor_reposicion',
    ];

    protected $casts = [
        'equipo_antiguedad' => 'integer',
        'valor_reposicion' => 'float',
    ];

    public function plan()
    {
        return $this->belongsTo(PlanRecambio::class, 'plan_id');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }
}
