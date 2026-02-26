<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\HistorialMovimientoResource;
use App\Http\Resources\MantenimientoResource;
use App\Http\Resources\EquipoResource;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function dashboardStats()
    {
        $count = \App\Models\Equipo::count();
        Log::debug('ReportController.dashboardStats count', ['equipos_total' => $count]);
        return response()->json(['equipos_total' => $count]);
    }

    public function warrantyReport()
    {
        $equipos = \App\Models\Equipo::whereNotNull('fecha_compra')->get();
        $list = [];
        foreach ($equipos as $e) {
            try {
                $compra = \Carbon\Carbon::parse($e->fecha_compra);
            } catch (\Throwable $th) {
                continue;
            }
            $meses = intval($e->garantia_meses ?? 0);
            // If no warranty or non-positive months, consider it expired (0 days remaining)
            if ($meses <= 0) {
                $vencimiento = $compra; // still provide a date (purchase date)
                $dias = 0;
            } else {
                // Do not mutate original Carbon instance in case it's reused
                $vencimiento = $compra->copy()->addMonths($meses);
                // Calculate seconds difference and convert to days, rounding to nearest integer
                $diffSeconds = $vencimiento->getTimestamp() - now()->getTimestamp();
                $diasRounded = (int) round($diffSeconds / 86400);
                $dias = $diasRounded > 0 ? $diasRounded : 0;
            }
            $list[] = [
                'equipo' => (new EquipoResource($e))->toArray(request()),
                'dias_restantes' => $dias,
                'fecha_vencimiento' => $vencimiento->toDateString(),
            ];
        }
        return response()->json($list);
    }

    public function replacementCandidates()
    {
        $baseQuery = \App\Models\Equipo::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhereRaw('LOWER(estado) NOT LIKE ?', ['%baja%']);
            })
            ->whereHas('tipo_equipo', function ($q) {
                $q->where('considerar_recambio', true);
            });

        $baseTotal = (clone $baseQuery)->count();
        $targetCount = $baseTotal > 0 ? max(1, (int) ceil($baseTotal * 0.2)) : 0;

        $candidates = \App\Models\Equipo::with('tipo_equipo')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhereRaw('LOWER(estado) NOT LIKE ?', ['%baja%']);
            })
            ->whereNull('plan_recambio_id')
            ->whereHas('tipo_equipo', function ($q) {
                $q->where('considerar_recambio', true);
            })
            ->where(function ($q) {
                $q->whereNull('pi_recambio')->orWhere('pi_recambio', '');
            })
            ->whereNotNull('fecha_compra')
            ->get();

        $eligible = $candidates->map(function ($e) {
            try {
                $age = \Carbon\Carbon::parse($e->fecha_compra)->diffInYears(now());
            } catch (\Throwable $ex) {
                return null;
            }
            if ($age < 4) {
                return null;
            }
            return ['equipo' => $e, 'age' => $age];
        })->filter();

        $selected = $eligible
            ->sortByDesc('age')
            ->take($targetCount)
            ->pluck('equipo')
            ->values();

        return EquipoResource::collection($selected);
    }

    public function movementHistory()
    {
        $rows = \App\Models\HistorialMovimiento::with(['equipo','fromUbicacion','toUbicacion','responsable'])->get();
        Log::debug('ReportController.movementHistory count', ['count' => $rows->count(), 'sample_ids' => $rows->pluck('id')->take(10)]);
        return HistorialMovimientoResource::collection($rows);
    }

    public function assignmentHistory()
    {
        // Include common variants: case-insensitive 'asign' (covers Asignado/Asignación/etc.)
        // Also include rows that have a responsable_id set (likely an assignment)
        $rows = \App\Models\HistorialMovimiento::with(['equipo','responsable','toUbicacion'])
            ->where(function($q){
                $q->whereRaw('LOWER(nota) LIKE ?', ['%asign%'])
                  ->orWhereNotNull('responsable_id');
            })->get();
        Log::debug('ReportController.assignmentHistory count', ['count' => $rows->count(), 'sample_ids' => $rows->pluck('id')->take(10)]);
        $collection = $rows->map(function($r){
            $fechaInicio = null;
            if (!empty($r->fecha)) {
                if (is_object($r->fecha) && method_exists($r->fecha, 'toDateTimeString')) {
                    try {
                        $fechaInicio = $r->fecha->toDateTimeString();
                    } catch (\Throwable $e) {
                        $fechaInicio = (string) $r->fecha;
                    }
                } else {
                    try {
                        $fechaInicio = \Carbon\Carbon::parse($r->fecha)->toDateTimeString();
                    } catch (\Throwable $e) {
                        $fechaInicio = (string) $r->fecha;
                    }
                }
            }
            return [
                'id' => $r->id,
                'equipo_codigo' => $r->equipo?->codigo_activo,
                'equipo_modelo' => $r->equipo?->modelo,
                'usuario_nombre' => $r->responsable?->name,
                'usuario_departamento' => $r->responsable?->departamento?->nombre,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => null,
                'ubicacion' => $r->toUbicacion?->nombre ?? $r->fromUbicacion?->nombre,
                // Prefer proxy endpoint to ensure CORS headers are present when frontend fetches blobs
                'archivo' => $r->archivo ? url('api/files/asignaciones/' . ltrim(basename($r->archivo), '/')) : null,
            ];
        });
        return response()->json($collection->values());
    }

    public function maintenanceHistory()
    {
        $rows = \App\Models\Mantenimiento::with('equipo')->get();
        $collection = $rows->map(function($m){
            $fecha = null;
            if (!empty($m->fecha)) {
                try {
                    $fecha = \Carbon\Carbon::parse($m->fecha)->toDateString();
                } catch (\Throwable $e) {
                    $fecha = (string) $m->fecha;
                }
            }
            return [
                'id' => $m->id,
                'fecha' => $fecha,
                'equipo_codigo' => $m->equipo?->codigo_activo,
                'equipo_modelo' => $m->equipo?->modelo,
                'tipo_mantenimiento' => $m->tipo ?? $m->tipo_mantenimiento ?? 'N/A',
                'proveedor' => $m->proveedor,
                'costo' => $m->costo,
                'descripcion' => $m->descripcion,
                'archivo_orden' => $m->archivo_orden ? url('api/files/mantenimientos/' . ltrim(basename($m->archivo_orden), '/')) : null,
            ];
        });
        return response()->json($collection->values());
    }

    public function verifyMaintenanceAlerts()
    {
        // Endpoint utilizado por el frontend para forzar verificación de alertas
        // Por ahora es best-effort: solo registra y responde OK.
        Log::info('ReportController.verifyMaintenanceAlerts called');
        return response()->json(['ok' => true]);
    }
}
