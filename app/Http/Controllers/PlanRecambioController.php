<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PlanRecambio;
use App\Models\DetallePlanRecambio;
use App\Models\Equipo;

class PlanRecambioController extends Controller
{
    public function index()
    {
        $plans = PlanRecambio::orderByDesc('fecha_creacion')->get();
        return response()->json($plans);
    }

    public function show($id)
    {
        $plan = PlanRecambio::findOrFail($id);
        $details = DetallePlanRecambio::where('plan_id', $plan->id)->get();
        return response()->json(['plan' => $plan, 'details' => $details]);
    }

    public function save(Request $request)
    {
        $planData = $request->input('plan', $request->only([
            'anio','nombre','creado_por','fecha_creacion','presupuesto_estimado','total_equipos','estado'
        ]));
        $details = $request->input('details', []);

        if (empty($planData['nombre']) || empty($planData['anio'])) {
            return response()->json(['message' => 'nombre y anio son requeridos'], 422);
        }

        $result = null;
        DB::beginTransaction();
        try {
            $plan = PlanRecambio::create([
                'anio' => $planData['anio'],
                'nombre' => $planData['nombre'],
                'creado_por' => $planData['creado_por'] ?? null,
                'fecha_creacion' => $planData['fecha_creacion'] ?? now()->toDateString(),
                'presupuesto_estimado' => $planData['presupuesto_estimado'] ?? 0,
                'total_equipos' => $planData['total_equipos'] ?? count($details),
                'estado' => $planData['estado'] ?? 'ACTIVO',
            ]);

            foreach ($details as $d) {
                $detalle = DetallePlanRecambio::create([
                    'plan_id' => $plan->id,
                    'equipo_id' => $d['equipo_id'] ?? null,
                    'equipo_codigo' => $d['equipo_codigo'] ?? '',
                    'equipo_modelo' => $d['equipo_modelo'] ?? null,
                    'equipo_marca' => $d['equipo_marca'] ?? null,
                    'equipo_antiguedad' => $d['equipo_antiguedad'] ?? 0,
                    'valor_reposicion' => $d['valor_reposicion'] ?? 0,
                ]);

                if (!empty($detalle->equipo_id)) {
                    Equipo::where('id', $detalle->equipo_id)->update(['plan_recambio_id' => $plan->id]);
                }
            }

            DB::commit();
            $result = $plan;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanRecambio save failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'No se pudo guardar el plan', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'plan' => $result], 201);
    }
}
