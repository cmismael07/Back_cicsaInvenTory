<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipo extends Model
{
    use HasFactory;

    protected $table = 'equipos';

    protected $fillable = [
        'tipo_equipo_id',
        'ubicacion_id',
        'responsable_id',
        'serie_cargador',
        'procesador',
        'ram',
        'disco_capacidad',
        'disco_tipo',
        'sistema_operativo',
        'plan_recambio_id',
        'pi_compra',
        'pi_recambio',
        'codigo_activo',
        'marca',
        'modelo',
        'serial',
        'estado',
        'fecha_compra',
        'garantia_meses',
        'valor_compra',
        'observaciones'
    ];

    public function tipo_equipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function historialMovimientos()
    {
        return $this->hasMany(HistorialMovimiento::class);
    }
}
