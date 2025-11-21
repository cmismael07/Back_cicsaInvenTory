<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LicenciaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tipo_id' => $this->tipo_licencia_id,
            'tipo_nombre' => $this->tipo_licencia?->nombre,
            'clave' => $this->clave,
            'fecha_compra' => $this->fecha_compra ?? null,
            'fecha_vencimiento' => $this->formatDate($this->fecha_vencimiento),
            'usuario_id' => $this->user_id,
            'usuario_nombre' => $this->user?->name,
            'usuario_departamento' => $this->user?->departamento?->nombre,
        ];
    }

    private function formatDate($value)
    {
        if (is_null($value)) return null;
        if (is_object($value) && method_exists($value, 'toDateString')) {
            return $value->toDateString();
        }
        return (string) $value;
    }
}
