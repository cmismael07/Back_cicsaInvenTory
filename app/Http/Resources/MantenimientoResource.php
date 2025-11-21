<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MantenimientoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'equipo_id' => $this->equipo_id,
            'equipo_codigo' => $this->equipo?->codigo_activo,
            'equipo_modelo' => $this->equipo?->modelo,
            'fecha' => $this->formatDate($this->fecha_inicio),
            'tipo_mantenimiento' => $this->tipo ?? 'Correctivo',
            'proveedor' => $this->proveedor ?? null,
            'costo' => $this->costo ?? 0,
            'descripcion' => $this->descripcion,
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
