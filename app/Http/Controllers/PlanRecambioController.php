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
    protected function detalleModelAvailable(): bool
    {
        return class_exists(\App\Models\DetallePlanRecambio::class);
    }

    protected function detalleBaseQuery()
    {
        if ($this->detalleModelAvailable()) {
            return \App\Models\DetallePlanRecambio::query();
        }
        return DB::table('detalle_plan_recambios');
    }

    public function index()
    {
        $plans = PlanRecambio::orderByDesc('fecha_creacion')->get();
        return response()->json($plans);
    }

    public function show($id)
    {
        $plan = PlanRecambio::findOrFail($id);
        $details = $this->detalleBaseQuery()->where('plan_id', $plan->id)->get();
        return response()->json(['plan' => $plan, 'details' => $details]);
    }

    public function save(Request $request)
    {
        $planData = $request->input('plan', $request->only([
            'id','anio','nombre','creado_por','fecha_creacion','presupuesto_estimado','total_equipos','estado','pi_recambio'
        ]));
        $rawDetails = $request->input('details', []);

        $details = collect($rawDetails)->map(function ($d) {
            $equipoId = $d['equipo_id'] ?? $d['equipoId'] ?? $d['id_equipo'] ?? null;
            return [
                'equipo_id' => $equipoId,
                'equipo_codigo' => $d['equipo_codigo'] ?? $d['equipoCodigo'] ?? '',
                'equipo_modelo' => $d['equipo_modelo'] ?? $d['equipoModelo'] ?? null,
                'equipo_marca' => $d['equipo_marca'] ?? $d['equipoMarca'] ?? null,
                'equipo_antiguedad' => $d['equipo_antiguedad'] ?? $d['equipoAntiguedad'] ?? 0,
                'valor_reposicion' => $d['valor_reposicion'] ?? $d['valorReposicion'] ?? 0,
            ];
        })->values()->all();

        if (empty($planData['nombre']) || empty($planData['anio'])) {
            return response()->json(['message' => 'nombre y anio son requeridos'], 422);
        }

        foreach ($details as $idx => $d) {
            if (empty($d['equipo_id'])) {
                return response()->json([
                    'message' => 'Detalle inválido: equipo_id es requerido en todos los items',
                    'detail_index' => $idx,
                ], 422);
            }
            if (!Equipo::where('id', $d['equipo_id'])->exists()) {
                return response()->json([
                    'message' => 'Detalle inválido: equipo_id no existe',
                    'detail_index' => $idx,
                    'equipo_id' => $d['equipo_id'],
                ], 422);
            }
        }

        $result = null;
        DB::beginTransaction();
        try {
            $payload = [
                'anio' => $planData['anio'],
                'nombre' => $planData['nombre'],
                'creado_por' => $planData['creado_por'] ?? null,
                'fecha_creacion' => $planData['fecha_creacion'] ?? now()->toDateString(),
                'presupuesto_estimado' => $planData['presupuesto_estimado'] ?? 0,
                'total_equipos' => $planData['total_equipos'] ?? count($details),
                'estado' => $planData['estado'] ?? 'ACTIVO',
                'pi_recambio' => $planData['pi_recambio'] ?? null,
            ];

            $plan = null;
            if (!empty($planData['id']) && PlanRecambio::where('id', $planData['id'])->exists()) {
                $plan = PlanRecambio::find($planData['id']);
                $plan->update($payload);

                // Track existing equipos to release removed ones
                $oldEquipos = $this->detalleBaseQuery()->where('plan_id', $plan->id)
                    ->pluck('equipo_id')
                    ->filter()
                    ->values();

                // Replace previous details for this plan
                $this->detalleBaseQuery()->where('plan_id', $plan->id)->delete();

                // Determine equipos that were removed from the plan
                $newEquipos = collect($details)
                    ->map(fn ($d) => $d['equipo_id'] ?? null)
                    ->filter()
                    ->values();

                $removedEquipos = $oldEquipos->diff($newEquipos)->values();
                if ($removedEquipos->count() > 0) {
                    Equipo::whereIn('id', $removedEquipos)->update([
                        'plan_recambio_id' => null,
                        'pi_recambio' => null,
                    ]);
                }
            } else {
                $plan = PlanRecambio::create($payload);
            }

            foreach ($details as $d) {
                $detallePayload = [
                    'plan_id' => $plan->id,
                    'equipo_id' => $d['equipo_id'],
                    'equipo_codigo' => $d['equipo_codigo'],
                    'equipo_modelo' => $d['equipo_modelo'],
                    'equipo_marca' => $d['equipo_marca'],
                    'equipo_antiguedad' => $d['equipo_antiguedad'],
                    'valor_reposicion' => $d['valor_reposicion'],
                ];

                if ($this->detalleModelAvailable()) {
                    \App\Models\DetallePlanRecambio::create($detallePayload);
                } else {
                    DB::table('detalle_plan_recambios')->insert($detallePayload);
                }

                if (!empty($d['equipo_id'])) {
                    $updates = ['plan_recambio_id' => $plan->id];
                    if (!empty($payload['pi_recambio']) && ($payload['estado'] ?? '') === 'ACTIVO') {
                        $updates['pi_recambio'] = $payload['pi_recambio'];
                    }
                    Equipo::where('id', $d['equipo_id'])->update($updates);
                }
            }

            DB::commit();
            $result = $plan;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanRecambio save failed', [
                'error' => $e->getMessage(),
                'plan' => $planData,
                'details_count' => count($details),
            ]);
            return response()->json(['message' => 'No se pudo guardar el plan', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'plan' => $result], 201);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $plan = PlanRecambio::findOrFail($id);
            $detailEquipos = $this->detalleBaseQuery()->where('plan_id', $plan->id)->pluck('equipo_id')->filter()->values();

            $this->detalleBaseQuery()->where('plan_id', $plan->id)->delete();
            $plan->delete();

            if ($detailEquipos->count() > 0) {
                Equipo::whereIn('id', $detailEquipos)->update([
                    'plan_recambio_id' => null,
                    'pi_recambio' => null,
                ]);
            }

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanRecambio delete failed', ['error' => $e->getMessage(), 'plan_id' => $id]);
            return response()->json(['message' => 'No se pudo eliminar el plan', 'error' => $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        $piRecambio = strtoupper(trim((string) $request->input('pi_recambio', '')));
        if ($piRecambio === '') {
            return response()->json(['message' => 'pi_recambio es requerido'], 422);
        }

        $nombre = $request->input('nombre');

        DB::beginTransaction();
        try {
            $plan = PlanRecambio::findOrFail($id);
            $updatePayload = [
                'estado' => 'ACTIVO',
                'pi_recambio' => $piRecambio,
            ];
            if (!empty($nombre)) {
                $updatePayload['nombre'] = $nombre;
            }
            $plan->update($updatePayload);

            $detailEquipos = $this->detalleBaseQuery()->where('plan_id', $plan->id)->pluck('equipo_id')->filter()->values();
            if ($detailEquipos->count() > 0) {
                Equipo::whereIn('id', $detailEquipos)->update([
                    'plan_recambio_id' => $plan->id,
                    'pi_recambio' => $piRecambio,
                ]);
            }

            DB::commit();
            return response()->json(['ok' => true, 'plan' => $plan]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanRecambio approve failed', ['error' => $e->getMessage(), 'plan_id' => $id]);
            return response()->json(['message' => 'No se pudo aprobar el plan', 'error' => $e->getMessage()], 500);
        }
    }
}
