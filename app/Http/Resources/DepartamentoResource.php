<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class DepartamentoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'es_bodega' => (bool) ($this->es_bodega ?? false),
            'ciudad_id' => $this->ciudad_id ?? null,
            'ciudad_nombre' => $this->ciudad_id ? DB::table('ciudades')->where('id', $this->ciudad_id)->value('nombre') : null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
