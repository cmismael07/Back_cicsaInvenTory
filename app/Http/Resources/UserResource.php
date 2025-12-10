<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        // Construimos la forma que espera el frontend
        $fullName = trim($this->name ?? '');
        $nombres = '';
        $apellidos = '';
        if ($fullName === '') {
            $nombres = '';
            $apellidos = '';
        } else {
            $parts = preg_split('/\s+/', $fullName);
            $count = count($parts);
            if ($count === 1) {
                $nombres = $parts[0];
                $apellidos = '';
            } elseif ($count === 2) {
                $nombres = $parts[0];
                $apellidos = $parts[1];
            } else {
                // For 3+ parts, assume last two words are apellidos and the rest are nombres
                $apellidos = $parts[$count - 2] . ' ' . $parts[$count - 1];
                $nombres = implode(' ', array_slice($parts, 0, $count - 2));
            }
        }

        // nombre_usuario viene del campo `username` si existe, sino intentamos derivarlo del email
        $username = $this->username ?? null;
        if (empty($username) && !empty($this->email)) {
            $username = strstr($this->email, '@', true) ?: $this->email;
        }

        return [
            'id' => $this->id,
            'nombre_usuario' => $username,
            'numero_empleado' => $this->numero_empleado ?? null,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'nombre_completo' => trim($fullName),
            'correo' => $this->email,
            'rol' => $this->rol ?? 'Usuario',
            'departamento_id' => $this->departamento_id,
            'departamento_nombre' => $this->departamento?->nombre,
            'puesto_id' => $this->puesto_id,
            'puesto_nombre' => $this->puesto?->nombre,
            'activo' => (bool) ($this->activo ?? true),
        ];
    }
}
