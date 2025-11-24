<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TipoLicenciaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'proveedor' => $this->proveedor,
            'descripcion' => $this->descripcion,
            'stock_total' => $this->stock,
            'version' => $this->version,
            'total' => $this->when(isset($this->total), $this->total),
            'disponibles' => $this->when(isset($this->disponibles), $this->disponibles),
            'licencias' => LicenciaResource::collection($this->whenLoaded('licencias')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
