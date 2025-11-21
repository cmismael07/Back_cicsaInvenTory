<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Licencia extends Model
{
    use HasFactory;

    protected $table = 'licencias';

    protected $fillable = ['tipo_licencia_id', 'user_id', 'clave', 'fecha_compra', 'fecha_vencimiento', 'stock'];

    public function tipo_licencia()
    {
        return $this->belongsTo(TipoLicencia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asignaciones()
    {
        return $this->belongsToMany(User::class, 'licencia_user');
    }

    public function getDisponibleAttribute()
    {
        return $this->stock - $this->asignaciones()->count();
    }
}
