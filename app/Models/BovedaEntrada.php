<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BovedaEntrada extends Model
{
    use HasFactory;

    protected $table = 'boveda_entradas';

    protected $fillable = [
        'servicio',
        'usuario',
        'password_hash',
        'url',
        'categoria',
        'notas',
    ];
}
