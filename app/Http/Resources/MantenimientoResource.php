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
            'costo' => (float) ($this->costo ?? 0),
            // Total acumulado para el equipo (suma de todos los mantenimientos registrados)
            'costo_total_acumulado' => $this->calculateTotalAcumulado(),
            // Costo acumulado hasta esta entrada (incluye este registro). Útil para ver evolución.
            'costo_acumulado_hasta_fecha' => $this->calculateAcumuladoHastaEstaFecha(),
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

    private function calculateTotalAcumulado()
    {
        try {
            return (float) (\App\Models\Mantenimiento::where('equipo_id', $this->equipo_id)->sum('costo') ?? 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function calculateAcumuladoHastaEstaFecha()
    {
        try {
            if (empty($this->fecha_inicio)) {
                // fallback to id ordering if no fecha
                return (float) \App\Models\Mantenimiento::where('equipo_id', $this->equipo_id)
                    ->where('id', '<=', $this->id)
                    ->sum('costo');
            }
            return (float) \App\Models\Mantenimiento::where('equipo_id', $this->equipo_id)
                ->where(function($q){
                    $q->where('fecha_inicio', '<', $this->fecha_inicio)
                      ->orWhere(function($q2){ $q2->where('fecha_inicio', '=', $this->fecha_inicio)->where('id', '<=', $this->id); });
                })->sum('costo');
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
