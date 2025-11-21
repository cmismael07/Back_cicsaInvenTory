<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoLicencia extends Model
{
    use HasFactory;

    protected $table = 'tipos_licencia';

    protected $fillable = ['nombre', 'version'];

    public function licencias()
    {
        return $this->hasMany(Licencia::class);
    }
}
