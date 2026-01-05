<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasFactory;

    protected $table = 'email_settings';

    protected $casts = [
        'correos_copia' => 'array',
        'notificar_asignacion' => 'boolean',
        'notificar_mantenimiento' => 'boolean',
    ];

    protected $fillable = [
        'remitente', 'correos_copia', 'notificar_asignacion', 'notificar_mantenimiento', 'dias_anticipacion_alerta',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption'
    ];
}
