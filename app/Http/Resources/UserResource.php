<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        // Construimos la forma que espera el frontend
        $fullName = $this->name ?? '';
        $nombres = $fullName;
        $apellidos = '';
        if (is_string($fullName) && str_contains($fullName, ' ')) {
            [$first, $rest] = explode(' ', $fullName, 2);
            $nombres = $first;
            $apellidos = $rest;
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
