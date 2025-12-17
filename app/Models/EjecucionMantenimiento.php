<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EjecucionMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'ejecuciones_mantenimiento';

    protected $fillable = ['detail_id', 'fecha', 'tecnico', 'observaciones', 'archivo'];

    public function detalle()
    {
        return $this->belongsTo(DetallePlanMantenimiento::class, 'detail_id');
    }
}
