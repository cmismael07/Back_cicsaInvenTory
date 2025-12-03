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
            'fecha' => $this->formatFecha($this->fecha),
            'usuario_responsable' => $this->responsable?->name,
            'detalle' => $this->nota,
            'archivo' => $this->archivo ?? null,
        ];
    }

    private function formatFecha($value)
    {
        if (empty($value)) return null;
        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function inferTipoAccion($nota)
    {
        if (!is_string($nota)) $nota = (string) $nota;
        $n = strtolower($nota ?? '');

        // Assignment keywords
        if (str_contains($n, 'asign') || str_contains($n, 'entreg') || str_contains($n, 'entrega')) return 'ASIGNACION';

        // Reception / receive keywords
        if (str_contains($n, 'recep') || str_contains($n, 'recib') || str_contains($n, 'recepcion')) return 'RECEPCION';

        // Decommission / baja
        if (str_contains($n, 'baja') || str_contains($n, 'dar de baja') || str_contains($n, 'dado de baja')) return 'BAJA';

        // Maintenance
        if (str_contains($n, 'manten') || str_contains($n, 'mantenimiento')) return 'MANTENIMIENTO';

        // Creation
        if (str_contains($n, 'creaci') || str_contains($n, 'creado')) return 'CREACION';

        // Heuristics: if no clear keyword, infer from model fields
        // If movement between different ubicaciones, consider it a movement/recepcion
        if (! empty($this->from_ubicacion_id) && ! empty($this->to_ubicacion_id) && $this->from_ubicacion_id != $this->to_ubicacion_id) return 'RECEPCION';

        // If there is a responsable_id set (and no movement), it's likely an assignment
        if (! empty($this->responsable_id)) return 'ASIGNACION';

        return 'EDICION';
    }
}
