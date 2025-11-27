<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = ['nombre', 'es_bodega'];

    protected $casts = [
        'es_bodega' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
