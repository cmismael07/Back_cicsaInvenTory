<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistorialMovimiento extends Model
{
    use HasFactory;

    protected $table = 'historial_movimientos';

    protected $fillable = ['equipo_id', 'from_ubicacion_id', 'to_ubicacion_id', 'fecha', 'nota', 'responsable_id', 'archivo'];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function fromUbicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'from_ubicacion_id');
    }

    public function toUbicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'to_ubicacion_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
