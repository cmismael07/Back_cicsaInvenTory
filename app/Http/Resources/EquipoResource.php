<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipoResource extends JsonResource
{
    public function toArray($request): array
    {
        // Ajustar salida para coincidir con `Equipo` en frontend
        return [
            'id' => $this->id,
            'codigo_activo' => $this->codigo_activo,
            'numero_serie' => $this->serial,
            'serie_cargador' => $this->serie_cargador ?? null,
            'modelo' => $this->modelo,
            'marca' => $this->marca,
            'tipo_equipo_id' => $this->tipo_equipo_id,
            'tipo_nombre' => $this->tipo_equipo?->nombre,
            'fecha_compra' => $this->formatDate($this->fecha_compra),
            'valor_compra' => (float) ($this->valor_compra ?? 0),
            'anos_garantia' => isset($this->garantia_meses) ? (int) floor($this->garantia_meses / 12) : 0,
            'procesador' => $this->procesador ?? null,
            'ram' => $this->ram ?? null,
            'disco_capacidad' => $this->disco_capacidad ?? null,
            'disco_tipo' => $this->disco_tipo ?? null,
            'sistema_operativo' => $this->sistema_operativo ?? null,
            'plan_recambio_id' => $this->plan_recambio_id ?? null,
            'estado' => $this->mapEstado($this->estado),
            'ubicacion_id' => $this->ubicacion_id,
            'ubicacion_nombre' => $this->ubicacion?->nombre,
            'responsable_id' => $this->responsable_id,
            'responsable_nombre' => $this->responsable?->name,
            'observaciones' => $this->observaciones ?? '',
            'ultimo_mantenimiento' => $this->getUltimoMantenimiento(),
        ];
    }

    private function getUltimoMantenimiento()
    {
        try {
            $fecha = $this->mantenimientos()->whereNotNull('fecha_fin')->orderByDesc('fecha_fin')->value('fecha_fin');
            if ($fecha) {
                if (is_object($fecha) && method_exists($fecha, 'toDateString')) return $fecha->toDateString();
                return (string) $fecha;
            }
        } catch (\Throwable $ex) {
            // ignore and return null
        }
        return null;
    }

    private function formatDate($value)
    {
        if (is_null($value)) return null;
        if (is_object($value) && method_exists($value, 'toDateString')) {
            return $value->toDateString();
        }
        return (string) $value;
    }

    private function mapEstado($estado)
    {
        if (empty($estado)) return 'Disponible';
        $e = strtolower((string) $estado);
        return match (true) {
            str_contains($e, 'activo') || $e === 'activo' || $e === 'asignado' => 'Activo',
            str_contains($e, 'mantenimiento') || $e === 'en_mantenimiento' => 'En Mantenimiento',
            $e === 'baja' => 'Baja',
            $e === 'recepcionado' || $e === 'disponible' || $e === 'pendiente' || $e === 'finalizado' => 'Disponible',
            default => ucfirst($e),
        };
    }
}
