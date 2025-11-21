<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\HistorialMovimientoResource;
use App\Http\Resources\MantenimientoResource;
use App\Http\Resources\EquipoResource;

class ReportController extends Controller
{
    public function dashboardStats()
    {
        return response()->json(['equipos_total' => \App\Models\Equipo::count()]);
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
            $vencimiento = $compra->addMonths($meses);
            $dias = now()->diffInDays($vencimiento, false);
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
        // Simple rule: equipos con garantia 0 or older than 5 years
        $candidates = \App\Models\Equipo::where(function($q){
            $q->whereNull('garantia_meses')->orWhere('garantia_meses', 0);
        })->orWhereRaw("TIMESTAMPDIFF(YEAR, fecha_compra, CURDATE()) >= 5")->get();
        return EquipoResource::collection($candidates);
    }

    public function movementHistory()
    {
        return HistorialMovimientoResource::collection(\App\Models\HistorialMovimiento::with(['equipo','fromUbicacion','toUbicacion','responsable'])->get());
    }

    public function assignmentHistory()
    {
        $rows = \App\Models\HistorialMovimiento::with(['equipo','responsable','toUbicacion'])->where('nota','like','%Asignado%')->get();
        $collection = $rows->map(function($r){
            return [
                'id' => $r->id,
                'equipo_codigo' => $r->equipo?->codigo_activo,
                'equipo_modelo' => $r->equipo?->modelo,
                'usuario_nombre' => $r->responsable?->name,
                'usuario_departamento' => $r->responsable?->departamento?->nombre,
                'fecha_inicio' => $r->fecha?->toDateTimeString(),
                'fecha_fin' => null,
                'ubicacion' => $r->toUbicacion?->nombre ?? $r->fromUbicacion?->nombre,
            ];
        });
        return response()->json($collection->values());
    }

    public function maintenanceHistory()
    {
        return MantenimientoResource::collection(\App\Models\Mantenimiento::with('equipo')->get());
    }
}
