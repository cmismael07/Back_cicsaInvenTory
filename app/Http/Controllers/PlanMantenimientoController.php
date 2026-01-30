<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanMantenimiento;
use App\Models\DetallePlanMantenimiento;
use App\Models\EjecucionMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\EmailSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\DynamicEmailService;

class PlanMantenimientoController extends Controller
{
    protected function sendMaintenanceExecutionNotificationIfEnabled(DetallePlanMantenimiento $detail, ?EjecucionMantenimiento $exec = null): void
    {
        try {
            Log::info('sendMaintenanceExecutionNotificationIfEnabled called', ['detail_id' => $detail->id ?? null, 'equipo_id' => $detail->equipo_id ?? null]);
            $settings = EmailSetting::first();
            if (! $settings) {
                Log::info('sendMaintenanceExecutionNotificationIfEnabled: no EmailSetting row found, skipping', ['detail_id' => $detail->id ?? null]);
                return;
            }
            if (! ($settings->notificar_mantenimiento ?? false)) {
                Log::info('sendMaintenanceExecutionNotificationIfEnabled: notificar_mantenimiento disabled', ['detail_id' => $detail->id ?? null]);
                return;
            }

            if (empty($detail->equipo_id)) return;
            $equipo = Equipo::find($detail->equipo_id);
            if (! $equipo) {
                Log::info('sendMaintenanceExecutionNotificationIfEnabled: equipo not found', ['detail_id' => $detail->id ?? null]);
                return;
            }
            if (empty($equipo->responsable_id)) {
                Log::info('sendMaintenanceExecutionNotificationIfEnabled: equipo has no responsable', ['equipo_id' => $equipo->id]);
                return;
            }

            $user = User::find($equipo->responsable_id);
            Log::info('sendMaintenanceExecutionNotificationIfEnabled: responsable lookup', ['equipo_id' => $equipo->id, 'responsable_id' => $equipo->responsable_id, 'user_found' => $user ? true : false]);
            $to = $user?->email ?? $user?->correo ?? null;
            if (empty($to)) {
                Log::info('sendMaintenanceExecutionNotificationIfEnabled: destinatario vacío (sin email)', ['equipo_id' => $equipo->id, 'responsable_id' => $equipo->responsable_id]);
                return;
            }

            $cc = is_array($settings->correos_copia) ? $settings->correos_copia : [];
            $codigo = $equipo->codigo_activo ?? ('Equipo #' . $equipo->id);
            $subject = "Mantenimiento registrado - {$codigo}";

            $lines = [];
            $lines[] = 'Se registró una ejecución de mantenimiento en el plan anual.';
            $lines[] = "Equipo: {$codigo}";
            if (!empty($detail->mes_programado)) $lines[] = 'Mes programado: ' . $detail->mes_programado;
            if ($exec) {
                if (!empty($exec->fecha)) $lines[] = 'Fecha ejecución: ' . $exec->fecha;
                if (!empty($exec->tecnico)) $lines[] = 'Técnico: ' . $exec->tecnico;
                if (!empty($exec->observaciones)) {
                    $lines[] = '';
                    $lines[] = 'Observaciones:';

                        $cc = is_array($settings->correos_copia) ? $settings->correos_copia : [];
                        $to = null;
                        if (! empty($equipo->responsable_id)) {
                            $user = User::find($equipo->responsable_id);
                            Log::info('sendMaintenanceExecutionNotificationIfEnabled: responsable lookup', ['equipo_id' => $equipo->id ?? null, 'responsable_id' => $equipo->responsable_id, 'user_found' => $user ? true : false]);
                            $to = $user?->email ?? $user?->correo ?? null;
                        }

                        if (empty($to)) {
                            if (!empty($cc)) {
                                Log::info('sendMaintenanceExecutionNotificationIfEnabled: sin responsable con email, usando correos_copia como destino', ['equipo_id' => $equipo->id ?? null, 'cc_count' => count($cc)]);
                                $to = array_shift($cc);
                            } else {
                                Log::info('sendMaintenanceExecutionNotificationIfEnabled: sin responsable ni correos_copia, no hay destinatario', ['equipo_id' => $equipo->id ?? null]);
                                return;
                            }
                        }
            $sent = app(DynamicEmailService::class)->sendRaw($to, $subject, $body, $cc);
            Log::info('sendMaintenanceExecutionNotificationIfEnabled: sendRaw result', ['detail_id' => $detail->id ?? null, 'to' => $to, 'sent' => $sent]);
        } catch (\Throwable $e) {
            Log::warning('sendMaintenanceExecutionNotificationIfEnabled failed', ['error' => $e->getMessage()]);
        }
    }

    protected function normalizePlanEstado($raw)
    {
        if ($raw === null) return null;
        $s = trim((string)$raw);
        $l = strtolower($s);
        if (in_array($l, ['en_proceso','en proceso','enproceso','en-proceso','enproceso'])) return 'En Proceso';
        if (in_array($l, ['realizado','done','completed','completado'])) return 'Realizado';
        if (in_array($l, ['retrasado','late'])) return 'Retrasado';
        if (in_array($l, ['pendiente','pending'])) return 'Pendiente';
        return ucfirst($s);
    }
    public function index()
    {
        logger()->info('PlanMantenimientoController@index called');
        $plans = PlanMantenimiento::with('detalles')->get();
        // Normalize detalle.estado for frontend compatibility
        foreach ($plans as $p) {
            foreach ($p->detalles as $d) {
                $d->estado = $this->normalizePlanEstado($d->estado);
            }
        }
        logger()->debug('Planes obtenidos', ['count' => $plans->count()]);
        return $plans;
    }

    public function show($id)
    {
        logger()->info('PlanMantenimientoController@show called', ['id' => $id]);
        $plan = PlanMantenimiento::with('detalles')->findOrFail($id);
        // Normalize detalle.estado values
        foreach ($plan->detalles as $d) {
            $d->estado = $this->normalizePlanEstado($d->estado);
        }
        logger()->debug('Plan obtenido', ['id' => $plan->id, 'detalles' => $plan->detalles->count()]);
        $payload = [
            'plan' => $plan,
            'details' => $plan->detalles,
        ];
        // Include `data` wrapper for clients expecting `resp.data`
        $payload['data'] = $payload;
        return response()->json($payload);
    }

    public function store(Request $request)
    {
        logger()->info('PlanMantenimientoController@store called', ['request' => $request->all()]);
        // Normalize incoming fecha_creacion (accept ISO8601 from frontend)
        $data = $request->only(['nombre','anio','creado_por','fecha_creacion','estado','ciudad_id','ciudad_nombre']);
        if ($request->filled('fecha_creacion')) {
            try {
                $dt = Carbon::parse($request->input('fecha_creacion'));
                $data['fecha_creacion'] = $dt->toDateTimeString();
            } catch (\Throwable $e) {
                logger()->warning('PlanMantenimientoController@store invalid fecha_creacion, clearing', ['raw' => $request->input('fecha_creacion')]);
                $data['fecha_creacion'] = null;
            }
        }

        // If creado_por not provided, prefer the authenticated user's display name
        if (empty($data['creado_por'])) {
            try {
                $user = $request->user();
                if ($user) {
                    $data['creado_por'] = $user->name ?? $user->username ?? null;
                }
            } catch (\Throwable $e) {
                // ignore if auth is not available
            }
        }
        $details = $request->input('details', []);
        $plan = PlanMantenimiento::create($data);
        foreach ($details as $d) {
            $d['plan_id'] = $plan->id;
            // Normalize incoming detail estado
            if (isset($d['estado'])) {
                $d['estado'] = $this->normalizePlanEstado($d['estado']);
            }
            DetallePlanMantenimiento::create($d);
        }
        $plan->load('detalles');
        logger()->info('Plan creado', ['plan_id' => $plan->id, 'detalles' => $plan->detalles->count()]);
        return response()->json(['plan' => $plan], 201);
    }

    public function updateDetailMonth(Request $request, $detailId)
    {
        $month = (int) $request->input('month');
        logger()->info('PlanMantenimientoController@updateDetailMonth called', ['detailId' => $detailId, 'month' => $month]);
        $detail = DetallePlanMantenimiento::findOrFail($detailId);
        $old = $detail->mes_programado;
        $detail->mes_programado = $month;
        $detail->save();
        logger()->debug('Detalle actualizado mes_programado', ['detailId' => $detailId, 'from' => $old, 'to' => $month]);
        return response()->json($detail);
    }

    public function registerExecution(Request $request, $detailId)
    {
        logger()->info('PlanMantenimientoController@registerExecution called', ['detailId' => $detailId, 'hasFile' => $request->hasFile('archivo')]);
        $detail = DetallePlanMantenimiento::findOrFail($detailId);
        $fecha = $request->input('fecha');
        $tecnico = $request->input('tecnico');
        $observaciones = $request->input('observaciones');
        $archivoPath = null;
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $path = $file->store('mantenimientos', 'public');
            $archivoPath = $path;
        }
        try {
            $exec = EjecucionMantenimiento::create([
                'detail_id' => $detail->id,
                'fecha' => $fecha,
                'tecnico' => $tecnico,
                'observaciones' => $observaciones,
                'archivo' => $archivoPath,
            ]);

            if ($exec) {
                // Ensure persisted and log full record
                $exec->refresh();
                logger()->info('Ejecucion creada', ['exec_id' => $exec->id, 'detail_id' => $detail->id, 'exec' => $exec->toArray()]);
            } else {
                logger()->warning('Ejecucion create returned falsy', ['detail_id' => $detail->id]);
            }
        } catch (\Throwable $e) {
            logger()->error('Error creando ejecucion: ' . $e->getMessage(), ['detail_id' => $detail->id, 'exception' => $e]);
            return response()->json(['message' => 'Error al crear ejecucion', 'error' => $e->getMessage()], 500);
        }

        // Notificación por correo (si está habilitada)
        $this->sendMaintenanceExecutionNotificationIfEnabled($detail, $exec ?? null);

        // Mark the plan detail as completed and update equipo status
        // Use frontend-friendly display values from types.ts
        $detail->estado = 'Realizado';
        $detail->save();

        // Refresh from DB and log final estado to help diagnose persistence issues
        $detail->refresh();
        logger()->info('Detalle after save', ['detail_id' => $detail->id, 'estado_db' => $detail->estado]);

        if (!empty($detail->equipo_id)) {
            $equipo = Equipo::find($detail->equipo_id);
            if ($equipo) {
                // Map to frontend 'Activo' status
                $equipo->estado = 'Activo';
                $equipo->save();
                logger()->debug('Equipo actualizado a operativo', ['equipo_id' => $equipo->id]);
            }
        }

        // If there is a Mantenimiento record that references this detail, mark it finalized
        try {
            $m = Mantenimiento::where('plan_detail_id', $detail->id)
                ->where('equipo_id', $detail->equipo_id)
                ->whereIn('estado', ['pendiente', 'en_mantenimiento'])
                ->orderByDesc('fecha_inicio')
                ->first();
            if ($m) {
                $m->estado = 'finalizado';
                $m->fecha_fin = now();
                $m->save();
                logger()->info('Mantenimiento asociado finalizado', ['mantenimiento_id' => $m->id, 'detail_id' => $detail->id]);
            }
        } catch (\Throwable $e) {
            logger()->warning('No se pudo marcar mantenimiento asociado como finalizado', ['error' => $e->getMessage()]);
        }

        // Return the execution plus the updated detail for convenience
        return response()->json([
            'ejecucion' => $exec,
            'detalle' => $detail,
        ], 201);
    }

    public function getExecutions($detailId)
    {
        logger()->info('PlanMantenimientoController@getExecutions called', ['detailId' => $detailId]);
        $execs = EjecucionMantenimiento::where('detail_id', $detailId)->get();
        logger()->debug('Ejecuciones encontradas', ['detailId' => $detailId, 'count' => $execs->count()]);
        return $execs;
    }

    public function getEvidence($detailId)
    {
        logger()->info('PlanMantenimientoController@getEvidence called', ['detailId' => $detailId]);
        $execs = EjecucionMantenimiento::where('detail_id', $detailId)->get();
        $payload = $execs->map(function ($e) {
            return [
                'id' => $e->id,
                'detalle_id' => $e->detail_id,
                'fecha' => $e->fecha,
                'tecnico' => $e->tecnico,
                'observaciones' => $e->observaciones,
                'archivo' => $e->archivo ? url('api/files/mantenimientos/' . ltrim(basename($e->archivo), '/')) : null,
            ];
        });
        logger()->debug('Evidencias encontradas', ['detailId' => $detailId, 'count' => $payload->count()]);
        return response()->json($payload->values());
    }

    // Genera una propuesta de detalles de mantenimiento para una ciudad dada
    public function generateProposal(Request $request)
    {
        $ciudadId = $request->input('ciudad_id') ?? $request->input('ciudadId') ?? $request->input('city_id');
        if (empty($ciudadId)) {
            return response()->json(['message' => 'ciudad_id es requerido'], 422);
        }

        logger()->info('PlanMantenimientoController@generateProposal called', ['ciudad_id' => $ciudadId]);

        $equipos = Equipo::with(['tipo_equipo','ubicacion'])->whereHas('ubicacion', function ($q) use ($ciudadId) {
            $q->where('ciudad_id', $ciudadId);
        })->get();

        // Default start month (if client provides a preferred month)
        $mesDefault = (int) ($request->input('mes') ?? 1);

        $detalles = [];
        foreach ($equipos as $e) {
            $freq = 1;
            if (!empty($e->tipo_equipo) && isset($e->tipo_equipo->frecuencia_anual)) {
                $freq = (int) $e->tipo_equipo->frecuencia_anual;
            }
            // Skip types explicitly excluded (0)
            if ($freq <= 0) continue;

            // Distribute months across the year: for i=0..freq-1 -> month = floor((i*12)/freq) + 1
            for ($i = 0; $i < $freq; $i++) {
                $month = (int) (floor(($i * 12) / $freq) + 1);
                // If client provided a specific start month, rotate sequence so first month >= mesDefault
                if ($mesDefault > 1) {
                    // rotate by finding offset between mesDefault and first generated month
                    $offset = ($mesDefault - $month + 12) % 12;
                    $rotated = ($month + $offset - 1) % 12 + 1;
                    $month = $rotated;
                }

                $detalles[] = [
                    'equipo_id' => $e->id,
                    'equipo_codigo' => $e->codigo_activo ?? $e->serial ?? null,
                    'equipo_tipo' => $e->tipo_equipo->nombre ?? null,
                    'equipo_modelo' => $e->modelo,
                    'equipo_ubicacion' => $e->ubicacion->nombre ?? null,
                    'mes_programado' => $month,
                    'estado' => 'Pendiente',
                ];
            }
        }

        logger()->debug('Proposal generated', ['ciudad_id' => $ciudadId, 'equipos' => $equipos->count()]);

        return response()->json(['ciudad_id' => $ciudadId, 'count' => count($detalles), 'detalles' => $detalles]);
    }

    public function startFromPlan(Request $request, $detailId)
    {
        logger()->info('PlanMantenimientoController@startFromPlan called', ['detailId' => $detailId, 'payload' => $request->all()]);
        $detail = DetallePlanMantenimiento::findOrFail($detailId);
        if (empty($detail->equipo_id)) {
            logger()->warning('startFromPlan: detalle sin equipo', ['detailId' => $detailId]);
            return response()->json(['message' => 'Detalle no tiene equipo asociado'], 422);
        }
        $equipo = Equipo::find($detail->equipo_id);
        if (!$equipo) return response()->json(['message' => 'Equipo no encontrado'], 404);

        // If equipo already in maintenance, avoid duplicating
        if (!empty($equipo->estado) && stripos($equipo->estado, 'manten') !== false) {
            logger()->warning('startFromPlan: equipo ya en mantenimiento', ['equipo_id' => $equipo->id, 'estado' => $equipo->estado]);
            return response()->json(['message' => 'Equipo ya se encuentra en mantenimiento', 'equipo' => $equipo], 409);
        }

        try {
            $m = new Mantenimiento();
            $m->equipo_id = $equipo->id;
            // Link back to plan detail when starting from plan
            $m->plan_detail_id = $detail->id;
            $m->fecha_inicio = now();
            $m->tipo = 'Planificado';
            $m->proveedor = $request->input('proveedor');
            $m->costo = 0;
            $m->descripcion = $request->input('motivo') ?? 'Iniciado desde plan de mantenimiento';
            $m->save();
        } catch (\Throwable $e) {
            // Log but continue: starting mantenimiento is best-effort for plan flow
            logger()->error('Error creando Mantenimiento desde plan: '.$e->getMessage(), ['exception' => $e]);
        }

        // set a consistent estado value used elsewhere (frontend expects 'En Mantenimiento')
        // Persist the plan start reason onto the equipo.observaciones so frontend shows origen/problema
        if (!empty($m->descripcion)) {
            $equipo->observaciones = $m->descripcion;
        }
        $equipo->estado = 'En Mantenimiento';
        $equipo->save();
        logger()->info('Equipo marcado en mantenimiento', ['equipo_id' => $equipo->id]);

        // Mark the plan detail as in maintenance so frontend reflects the change
        // Use display value 'En Proceso' to match frontend `EstadoPlan.EN_PROCESO`
        $detail->estado = 'En Proceso';
        $detail->save();
        logger()->info('Detalle marcado EN_PROCESO', ['detail_id' => $detail->id]);

        // Reload detail to ensure fresh attributes and relations
        $detail = DetallePlanMantenimiento::find($detail->id);

        $payload = [
            'message' => 'Mantenimiento iniciado',
            'equipo' => $equipo,
            'detalle' => $detail,
            'mantenimiento' => isset($m) ? $m : null,
        ];

        // Also include `data` wrapper for clients (e.g. axios) that access `resp.data`.
        $payload['data'] = $payload;

        return response()->json($payload);
    }
}
