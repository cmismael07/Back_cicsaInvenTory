<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HistorialMovimientoResource extends JsonResource
{
    public function toArray($request): array
    {
        // Normalize to the frontend `HistorialMovimiento` shape
        return [
            'id' => $this->id,
            'equipo_id' => $this->equipo_id,
            'equipo_codigo' => $this->equipo?->codigo_activo,
            // Try to infer an action type from the nota text, default to 'EDICION'
            'tipo_accion' => $this->inferTipoAccion($this->nota),
            'fecha' => $this->fecha?->toDateTimeString(),
            'usuario_responsable' => $this->responsable?->name,
            'detalle' => $this->nota,
        ];
    }

    private function inferTipoAccion($nota)
    {
        if (!is_string($nota)) return 'EDICION';
        $n = strtolower($nota);
        if (str_contains($n, 'asign')) return 'ASIGNACION';
        if (str_contains($n, 'recepcion')) return 'RECEPCION';
        if (str_contains($n, 'baja')) return 'BAJA';
        if (str_contains($n, 'mantenimiento')) return 'MANTENIMIENTO';
        if (str_contains($n, 'creaci')) return 'CREACION';
        return 'EDICION';
    }
}
