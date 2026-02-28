<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\EjecucionMantenimiento;
use App\Models\DetallePlanMantenimiento;
use App\Models\HistorialMovimiento;
use App\Models\Ubicacion;
use App\Models\Departamento;
use App\Models\User;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Resources\EquipoResource;
use App\Http\Resources\MantenimientoResource;
use App\Http\Resources\HistorialMovimientoResource;
use App\Services\DynamicEmailService;

class EquipoController extends Controller
{
    protected function sendMaintenanceNotificationIfEnabled(Equipo $equipo, ?Mantenimiento $mantenimiento, ?string $detalle = null): void
    {
        try {
            Log::info('sendMaintenanceNotificationIfEnabled called', ['equipo_id' => $equipo->id ?? null, 'mantenimiento_id' => $mantenimiento->id ?? null]);
            $settings = EmailSetting::first();
            if (! $settings) {
                Log::info('sendMaintenanceNotificationIfEnabled: no EmailSetting row found, skipping', ['equipo_id' => $equipo->id ?? null]);
                return;
            }
            if (! ($settings->notificar_mantenimiento ?? false)) {
                Log::info('sendMaintenanceNotificationIfEnabled: notificar_mantenimiento disabled', ['equipo_id' => $equipo->id ?? null]);
                return;
            }

            $cc = is_array($settings->correos_copia) ? $settings->correos_copia : [];

            $to = null;
            if (! empty($equipo->responsable_id)) {
                $user = User::find($equipo->responsable_id);
                Log::info('sendMaintenanceNotificationIfEnabled: responsable lookup', ['equipo_id' => $equipo->id ?? null, 'responsable_id' => $equipo->responsable_id, 'user_found' => $user ? true : false]);
                $to = $user?->email ?? $user?->correo ?? null;
            }

            // Fallback: si no hay responsable o el responsable no tiene email,
            // enviar al primer correo de `correos_copia` y colocar el resto en cc.
            if (empty($to)) {
                if (!empty($cc)) {
                    Log::info('sendMaintenanceNotificationIfEnabled: sin responsable con email, usando correos_copia como destino', ['equipo_id' => $equipo->id ?? null, 'cc_count' => count($cc)]);
                    $to = array_shift($cc);
                } else {
                    Log::info('sendMaintenanceNotificationIfEnabled: sin responsable ni correos_copia, no hay destinatario', ['equipo_id' => $equipo->id ?? null]);
                    return;
                }
            }

            $codigo = $equipo->codigo_activo ?? ('Equipo #' . $equipo->id);
            $subject = "Mantenimiento finalizado - {$codigo}";

            $lines = [];
            $lines[] = "Se registró la finalización del mantenimiento.";
            $lines[] = "Equipo: {$codigo}";
            if (!empty($equipo->modelo)) $lines[] = "Modelo: {$equipo->modelo}";
            if ($mantenimiento) {
                if (!empty($mantenimiento->proveedor)) $lines[] = "Proveedor: {$mantenimiento->proveedor}";
                if (isset($mantenimiento->costo)) $lines[] = "Costo: {$mantenimiento->costo}";
                if (!empty($mantenimiento->tipo)) $lines[] = "Tipo: {$mantenimiento->tipo}";
            }
            if (!empty($detalle)) {
                $lines[] = "";
                $lines[] = "Detalle:";
                $lines[] = $detalle;
            }
            $body = implode("\n", $lines);

            // Collect possible attachments from historial (archivo / archivos)
            $attachments = [];
            if ($hist) {
                if (!empty($hist->archivo)) {
                    $attachments[] = $hist->archivo;
                } elseif (!empty($hist->archivos)) {
                    // could be JSON or comma-separated; normalize to array
                    if (is_array($hist->archivos)) {
                        $attachments = array_merge($attachments, $hist->archivos);
                    } elseif (is_string($hist->archivos)) {
                        $decoded = json_decode($hist->archivos, true);
                        if (is_array($decoded)) $attachments = array_merge($attachments, $decoded);
                        else $attachments = array_merge($attachments, array_map('trim', explode(',', $hist->archivos)));
                    }
                }
            }

            $sent = app(DynamicEmailService::class)->sendRaw($to, $subject, $body, $cc, $attachments);
            Log::info('sendMaintenanceNotificationIfEnabled: sendRaw result', ['equipo_id' => $equipo->id ?? null, 'to' => $to, 'cc_count' => count($cc), 'sent' => $sent]);
        } catch (\Throwable $e) {
            // Best-effort: no romper el flujo principal
            Log::warning('sendMaintenanceNotificationIfEnabled failed', ['error' => $e->getMessage()]);
        }
    }

    protected function sendAssignNotificationIfEnabled(Equipo $equipo, ?HistorialMovimiento $hist = null): void
    {
        try {
            Log::info('sendAssignNotificationIfEnabled called', ['equipo_id' => $equipo->id ?? null, 'hist_id' => $hist->id ?? null]);
            $settings = EmailSetting::first();
            if (! $settings) {
                Log::info('sendAssignNotificationIfEnabled: no EmailSetting row found, skipping', ['equipo_id' => $equipo->id ?? null]);
                return;
            }
            if (! ($settings->notificar_asignacion ?? false)) {
                Log::info('sendAssignNotificationIfEnabled: notificar_asignacion disabled', ['equipo_id' => $equipo->id ?? null]);
                return;
            }

            $cc = is_array($settings->correos_copia) ? $settings->correos_copia : [];

            $to = null;
            $assignedName = null;
            if (! empty($equipo->responsable_id)) {
                $user = User::find($equipo->responsable_id);
                Log::info('sendAssignNotificationIfEnabled: responsable lookup', ['equipo_id' => $equipo->id ?? null, 'responsable_id' => $equipo->responsable_id, 'user_found' => $user ? true : false]);
                $to = $user?->email ?? $user?->correo ?? null;
                $assignedName = $user?->name ?? $user?->nombre ?? null;
            }

            if (empty($to)) {
                if (! empty($cc)) {
                    Log::info('sendAssignNotificationIfEnabled: sin responsable con email, usando correos_copia como destino', ['equipo_id' => $equipo->id ?? null, 'cc_count' => count($cc)]);
                    $to = array_shift($cc);
                } else {
                    Log::info('sendAssignNotificationIfEnabled: sin responsable ni correos_copia, no hay destinatario', ['equipo_id' => $equipo->id ?? null]);
                    return;
                }
            }

            $codigo = $equipo->codigo_activo ?? ('Equipo #' . $equipo->id);
            $subject = "Asignación de equipo - {$codigo}";

            $lines = [];
            $lines[] = "Se ha asignado el equipo: {$codigo}";
            if (! empty($assignedName)) $lines[] = "Asignado a: {$assignedName}";
            if (! empty($to)) $lines[] = "Correo destino: {$to}";
            if ($hist && ! empty($hist->nota)) {
                $lines[] = "";
                $lines[] = "Observaciones:";
                $lines[] = $hist->nota;
            }
            $body = implode("\n", $lines);

            $sent = app(DynamicEmailService::class)->sendRaw($to, $subject, $body, $cc);
            Log::info('sendAssignNotificationIfEnabled: sendRaw result', ['equipo_id' => $equipo->id ?? null, 'to' => $to, 'cc_count' => count($cc), 'sent' => $sent]);
        } catch (\Throwable $e) {
            Log::warning('sendAssignNotificationIfEnabled failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Buscar recursivamente posibles claves de id dentro de un array/request
     * Retorna el primer valor encontrado o null.
     */
    protected function findIdInPayload(array $payload, array $candidates)
    {
        foreach ($candidates as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                $val = $payload[$key];
                if (is_array($val) && array_key_exists('id', $val)) return $val['id'];
                return $val;
            }
        }

        // search nested arrays
        foreach ($payload as $v) {
            if (is_array($v)) {
                $found = $this->findIdInPayload($v, $candidates);
                if ($found !== null && $found !== '') return $found;
            }
        }

        return null;
    }

    /**
     * Normaliza valores de estado variados (snake_case, lowercase, english)
     * a las cadenas de despliegue usadas por el frontend (types.ts).
     */
    protected function normalizeEstadoValue($raw)
    {
        if ($raw === null) return null;
        $s = trim((string)$raw);
        $l = strtolower($s);

        if (strpos($l, 'manten') !== false) return 'En Mantenimiento';
        if (in_array($l, ['activo', 'activa', 'active', 'operativo', 'oper']) ) return 'Activo';
        if (in_array($l, ['baja', 'dado_baja', 'dar_baja'])) return 'Baja';
        if (in_array($l, ['disponible', 'dispon'])) return 'Disponible';
        if (in_array($l, ['para_baja', 'para-baja', 'para baja', 'para_baja'])) return 'Para Baja';
        if (in_array($l, ['recepcionado', 'recepcion', 'recepcionado'])) return 'Disponible';
        // Plan statuses
        if (in_array($l, ['en_proceso', 'en proceso', 'enproceso'])) return 'En Proceso';
        if (in_array($l, ['realizado', 'realizado'])) return 'Realizado';
        if (in_array($l, ['pendiente', 'pendiente'])) return 'Pendiente';

        // Fallback: capitalize first letter
        return ucfirst($s);
    }
    public function index()
    {
        $req = request()->all();
        $ciudadId = null;
        foreach (['ciudad_id','ciudadId','city_id','cityId','sede_id','sedeId'] as $k) {
            if (array_key_exists($k, $req) && ! empty($req[$k])) { $ciudadId = $req[$k]; break; }
        }

        $tipoEquipoId = null;
        foreach (['tipo_equipo_id','tipoEquipoId','tipo_id','tipoId','type_id','typeId'] as $k) {
            if (array_key_exists($k, $req) && $req[$k] !== null && $req[$k] !== '') {
                $tipoEquipoId = $req[$k];
                break;
            }
        }

        $tipoEquipoNombre = null;
        foreach (['tipo_equipo','tipo','type','tipo_nombre','tipoNombre'] as $k) {
            if (array_key_exists($k, $req) && $req[$k] !== null && $req[$k] !== '') {
                $val = $req[$k];
                if (is_array($val)) {
                    $tipoEquipoId = $tipoEquipoId ?? ($val['id'] ?? null);
                    $tipoEquipoNombre = $val['nombre'] ?? $val['name'] ?? null;
                } elseif (is_numeric($val)) {
                    $tipoEquipoId = $tipoEquipoId ?? $val;
                } else {
                    $tipoEquipoNombre = (string) $val;
                }
                break;
            }
        }

        $query = Equipo::with(['tipo_equipo','ubicacion','responsable']);
        if ($ciudadId) {
            $query = $query->whereHas('ubicacion', function ($q) use ($ciudadId) {
                $q->where('ciudad_id', $ciudadId);
            });
        }

        if ($tipoEquipoId) {
            $query = $query->where('tipo_equipo_id', $tipoEquipoId);
        } elseif (!empty($tipoEquipoNombre)) {
            $query = $query->whereHas('tipo_equipo', function ($q) use ($tipoEquipoNombre) {
                $q->where('nombre', 'like', '%' . $tipoEquipoNombre . '%');
            });
        }

        Log::debug('EquipoController.index filtros', [
            'ciudad_id' => $ciudadId,
            'tipo_equipo_id' => $tipoEquipoId,
            'tipo_equipo_nombre' => $tipoEquipoNombre,
        ]);

        return EquipoResource::collection($query->get());
    }

    public function store(Request $request)
    {
        // Normalizar nombres que el frontend podría enviar (camelCase, objetos, o sin _id)
        $input = $request->all();

        // Helper: map possible keys to *_id (supports snake_case, camelCase, objects)
        $mapToId = function (&$arr, $keyBase) {
            $parts = explode('_', $keyBase);
            $camel = $parts[0];
            for ($i = 1; $i < count($parts); $i++) { $camel .= ucfirst($parts[$i]); }

            $candidates = [
                "{$keyBase}_id",
                $keyBase,
                $keyBase . 'Id',
                $camel,
                $camel . 'Id',
                str_replace('_', '', $keyBase),
            ];

            foreach ($candidates as $k) {
                if (array_key_exists($k, $arr) && $arr[$k] !== null && $arr[$k] !== '') {
                    $val = $arr[$k];
                    if (is_array($val) && array_key_exists('id', $val)) {
                        $arr["{$keyBase}_id"] = $val['id'];
                    } else {
                        $arr["{$keyBase}_id"] = $val;
                    }
                    return;
                }
            }
        };

        $mapToId($input, 'ubicacion');
        $mapToId($input, 'tipo_equipo');
        $mapToId($input, 'departamento');
        $mapToId($input, 'responsable');

        if (isset($input['codigoActivo']) && !isset($input['codigo_activo'])) {
            $input['codigo_activo'] = $input['codigoActivo'];
        }

        // Map frontend field names to DB fields
        if (isset($input['numero_serie']) && ! isset($input['serial'])) {
            $input['serial'] = $input['numero_serie'];
        }
        if (isset($input['serieCargador']) && ! isset($input['serie_cargador'])) {
            $input['serie_cargador'] = $input['serieCargador'];
        }
        if (isset($input['anos_garantia']) && ! isset($input['garantia_meses'])) {
            // Frontend sends warranty in years (anos_garantia); store in months
            $years = $input['anos_garantia'];
            $input['garantia_meses'] = is_numeric($years) ? (int) ($years * 12) : $years;
        }

        // If ubicacion_id is missing, prefer department's bodega_ubicacion_id when provided
        if (empty($input['ubicacion_id'])) {
            if (! empty($input['departamento_id'])) {
                $dep = Departamento::find($input['departamento_id']);
                if ($dep && ! empty($dep->bodega_ubicacion_id)) {
                    $input['ubicacion_id'] = $dep->bodega_ubicacion_id;
                }
            }

            // Fallback to DEFAULT_UBICACION_ID or first/create
            if (empty($input['ubicacion_id'])) {
                $defaultId = env('DEFAULT_UBICACION_ID');
                if ($defaultId) {
                    $exists = Ubicacion::find($defaultId);
                    if ($exists) {
                        $input['ubicacion_id'] = $defaultId;
                    }
                }
            }

            if (empty($input['ubicacion_id'])) {
                $first = Ubicacion::first();
                if ($first) {
                    $input['ubicacion_id'] = $first->id;
                } else {
                    $created = Ubicacion::create(['nombre' => 'Bodega IT', 'descripcion' => 'Ubicación por defecto']);
                    $input['ubicacion_id'] = $created->id;
                }
            }
        }

        // Log incoming ubicacion input for diagnosis
        Log::debug('EquipoController.store incoming ubicacion', ['ubicacion_raw' => $input['ubicacion_id'] ?? null, 'request_all' => $input]);

        // If frontend passed a departamento id into ubicacion_id, prefer mapping to departamento's bodega when
        // that departamento exists and is marked as es_bodega. This avoids ambiguity when a Ubicacion with the
        // same numeric id also exists (e.g. departamento id 1 and ubicacion id 1).
        if (! empty($input['ubicacion_id'])) {
            try {
                $maybeDep = Departamento::find($input['ubicacion_id']);
                if ($maybeDep && ($maybeDep->es_bodega ?? false)) {
                    // Prefer departamento mapping even if a Ubicacion with same id exists
                    if (! empty($maybeDep->bodega_ubicacion_id) && Ubicacion::find($maybeDep->bodega_ubicacion_id)) {
                        $input['ubicacion_id'] = $maybeDep->bodega_ubicacion_id;
                    } else {
                        // create an auto bodega ubicacion for this departamento
                        $created = Ubicacion::create([
                            'nombre' => $maybeDep->nombre,
                            'descripcion' => 'Funciona como bodega IT | AUTO_BODEGA_DEPARTAMENTO_'.$maybeDep->id
                        ]);
                        $maybeDep->bodega_ubicacion_id = $created->id;
                        $maybeDep->save();
                        $input['ubicacion_id'] = $created->id;
                    }
                } else {
                    // If not a bodega-department, only map to departamento when no Ubicacion exists for the id
                    if (! Ubicacion::find($input['ubicacion_id'])) {
                        if ($maybeDep) {
                            if (! empty($maybeDep->bodega_ubicacion_id) && Ubicacion::find($maybeDep->bodega_ubicacion_id)) {
                                $input['ubicacion_id'] = $maybeDep->bodega_ubicacion_id;
                            } else {
                                // create an auto bodega ubicacion for this departamento (backwards-compat)
                                $created = Ubicacion::create([
                                    'nombre' => $maybeDep->nombre,
                                    'descripcion' => 'Funciona como bodega IT | AUTO_BODEGA_DEPARTAMENTO_'.$maybeDep->id
                                ]);
                                $maybeDep->bodega_ubicacion_id = $created->id;
                                $maybeDep->save();
                                $input['ubicacion_id'] = $created->id;
                            }
                        }
                    }
                }
            } catch (\Throwable $ex) {
                // ignore and allow validator to handle invalid id
            }
        }

        Log::debug('EquipoController.store resolved ubicacion', ['ubicacion_resolved' => $input['ubicacion_id'] ?? null]);

        $payload = \Illuminate\Support\Arr::only($input, [
            'tipo_equipo_id', 'ubicacion_id', 'responsable_id', 'codigo_activo', 'marca', 'modelo', 'serial', 'serie_cargador',
            'procesador', 'ram', 'disco_capacidad', 'disco_tipo', 'sistema_operativo', 'plan_recambio_id',
            'pi_compra', 'pi_recambio',
            'estado', 'fecha_compra', 'garantia_meses', 'valor_compra', 'observaciones'
        ]);

        // Normalize estado when updating
        if (isset($payload['estado'])) {
            $payload['estado'] = $this->normalizeEstadoValue($payload['estado']);
        }
        // Normalize estado if provided
        if (isset($payload['estado'])) {
            $payload['estado'] = $this->normalizeEstadoValue($payload['estado']);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($payload, [
            'tipo_equipo_id' => 'required|integer|exists:tipo_equipos,id',
            'ubicacion_id' => 'nullable|integer|exists:ubicaciones,id',
            'responsable_id' => 'nullable|integer|exists:users,id',
            'codigo_activo' => 'required|string|unique:equipos,codigo_activo',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'serial' => 'nullable|string',
            'serie_cargador' => 'nullable|string',
            'procesador' => 'nullable|string',
            'ram' => 'nullable|string',
            'disco_capacidad' => 'nullable|string',
            'disco_tipo' => 'nullable|string',
            'sistema_operativo' => 'nullable|string',
            'plan_recambio_id' => 'nullable|integer',
            'pi_compra' => 'nullable|string',
            'pi_recambio' => 'nullable|string',
            'estado' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'garantia_meses' => 'nullable|integer',
            'valor_compra' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        $validator->validate();

        $e = Equipo::create($payload);
        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function show($id)
    {
        return new EquipoResource(Equipo::with(['tipo_equipo','ubicacion','responsable','mantenimientos','historialMovimientos'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $e = Equipo::findOrFail($id);
        $input = $request->all();
        $mapToId = function (&$arr, $keyBase) {
            $candidates = ["{$keyBase}_id", $keyBase, $keyBase . 'Id', ucfirst($keyBase) . '_id'];
            foreach ($candidates as $k) {
                if (array_key_exists($k, $arr) && ! empty($arr[$k])) {
                    $val = $arr[$k];
                    if (is_array($val) && array_key_exists('id', $val)) {
                        $arr["{$keyBase}_id"] = $val['id'];
                    } else {
                        $arr["{$keyBase}_id"] = $val;
                    }
                    return;
                }
            }
        };
        $mapToId($input, 'ubicacion');
        $mapToId($input, 'tipo_equipo');
        $mapToId($input, 'responsable');

         if (isset($input['codigoActivo']) && !isset($input['codigo_activo'])) {
            $input['codigo_activo'] = $input['codigoActivo'];
        }

        // Map frontend field names to DB fields when updating
        if (isset($input['numero_serie']) && ! isset($input['serial'])) {
            $input['serial'] = $input['numero_serie'];
        }
        if (isset($input['anos_garantia']) && ! isset($input['garantia_meses'])) {
            // Frontend sends warranty in years (anos_garantia); store in months
            $years = $input['anos_garantia'];
            $input['garantia_meses'] = is_numeric($years) ? (int) ($years * 12) : $years;
        }

        // --- Normalize/resolve ubicacion when frontend sends a departamento id or nombre ---
        // If the provided ubicacion_id doesn't match an Ubicacion, try to map it from Departamento
        try {
            Log::debug('EquipoController.update incoming ubicacion fields', [
                'raw_ubicacion_id' => $input['ubicacion_id'] ?? null,
                'ubicacion_nombre' => $input['ubicacion_nombre'] ?? null,
                'ubicacion' => $input['ubicacion'] ?? null,
            ]);

            if (! empty($input['ubicacion_id'])) {
                $given = $input['ubicacion_id'];
                // Prefer Departamento mapping when an id exists for both models (frontend often sends departamento id)
                $maybeDep = Departamento::find($given);
                if ($maybeDep) {
                    Log::debug('EquipoController.update treating given id as Departamento', ['dep_id' => $maybeDep->id, 'es_bodega' => $maybeDep->es_bodega ?? null, 'bodega_ubicacion_id' => $maybeDep->bodega_ubicacion_id ?? null]);
                    if (! empty($maybeDep->bodega_ubicacion_id) && \App\Models\Ubicacion::find($maybeDep->bodega_ubicacion_id)) {
                        $input['ubicacion_id'] = $maybeDep->bodega_ubicacion_id;
                        Log::debug('EquipoController.update mapped departamento -> bodega_ubicacion_id', ['mapped_to' => $input['ubicacion_id']]);
                    } else {
                        // create an auto bodega ubicacion for this departamento
                        $created = \App\Models\Ubicacion::create([
                            'nombre' => $maybeDep->nombre,
                            'descripcion' => 'Funciona como bodega IT | AUTO_BODEGA_DEPARTAMENTO_'.$maybeDep->id
                        ]);
                        $maybeDep->bodega_ubicacion_id = $created->id;
                        $maybeDep->save();
                        $input['ubicacion_id'] = $created->id;
                        Log::debug('EquipoController.update created auto Ubicacion for Departamento', ['new_ubicacion_id' => $created->id]);
                    }
                } elseif (\App\Models\Ubicacion::find($given)) {
                    // If it's not a Departamento but exists as an Ubicacion, keep it
                    Log::debug('EquipoController.update given ubicacion id exists as Ubicacion', ['given' => $given]);
                } else {
                    Log::debug('EquipoController.update given id did not match Ubicacion nor Departamento', ['given' => $given]);
                }
            }

            if (empty($input['ubicacion_id']) && ! empty($input['ubicacion_nombre'])) {
                Log::debug('EquipoController.update resolving by ubicacion_nombre', ['ubicacion_nombre' => $input['ubicacion_nombre']]);
                $found = \App\Models\Ubicacion::where('nombre', $input['ubicacion_nombre'])->first();
                if ($found) {
                    $input['ubicacion_id'] = $found->id;
                    Log::debug('EquipoController.update resolved ubicacion_nombre -> existing Ubicacion', ['mapped_to' => $found->id]);
                } else {
                    $created = \App\Models\Ubicacion::create(['nombre' => $input['ubicacion_nombre'], 'descripcion' => 'Creada desde update por frontend']);
                    $input['ubicacion_id'] = $created->id;
                    Log::debug('EquipoController.update created Ubicacion from nombre', ['new_ubicacion_id' => $created->id]);
                }
            }
        } catch (\Throwable $__mapEx) {
            // ignore and let validator catch invalid ids
            \Illuminate\Support\Facades\Log::debug('EquipoController.update ubicacion resolve failed', ['error' => (string)$__mapEx, 'input_ubicacion' => $input['ubicacion_id'] ?? null]);
        }

        $payload = \Illuminate\Support\Arr::only($input, [
            'tipo_equipo_id', 'ubicacion_id', 'responsable_id', 'codigo_activo', 'marca', 'modelo', 'serial', 'serie_cargador',
            'procesador', 'ram', 'disco_capacidad', 'disco_tipo', 'sistema_operativo', 'plan_recambio_id',
            'pi_compra', 'pi_recambio',
            'estado', 'fecha_compra', 'garantia_meses', 'valor_compra', 'observaciones'
        ]);

        $validator = \Illuminate\Support\Facades\Validator::make($payload, [
            'tipo_equipo_id' => 'sometimes|required|integer|exists:tipo_equipos,id',
            'ubicacion_id' => 'sometimes|required|integer|exists:ubicaciones,id',
            'responsable_id' => 'nullable|integer|exists:users,id',
            'codigo_activo' => 'sometimes|required|string|unique:equipos,codigo_activo,'.$e->id,
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'serial' => 'nullable|string',
            'serie_cargador' => 'nullable|string',
            'procesador' => 'nullable|string',
            'ram' => 'nullable|string',
            'disco_capacidad' => 'nullable|string',
            'disco_tipo' => 'nullable|string',
            'sistema_operativo' => 'nullable|string',
            'plan_recambio_id' => 'nullable|integer',
            'pi_compra' => 'nullable|string',
            'pi_recambio' => 'nullable|string',
            'estado' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'garantia_meses' => 'nullable|integer',
            'valor_compra' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        $validator->validate();

        $e->update($payload);
        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function destroy($id)
    {
        Equipo::destroy($id);
        return response()->noContent();
    }

    public function asignar(Request $request, $id)
    {
        $e = Equipo::findOrFail($id);

        // Accept multiple possible keys from frontend for the responsable field
        $candidates = [
            'responsable_id', 'responsable', 'responsableId', 'responsableID',
            'usuario_id', 'usuario', 'usuarioId', 'user_id', 'userId'
        ];

        $responsableId = null;
        foreach ($candidates as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                if (is_array($val) && array_key_exists('id', $val)) {
                    $responsableId = $val['id'];
                } else {
                    $responsableId = $val;
                }
                break;
            }
        }

        // If no responsable provided, return equipo unchanged (keeps previous behavior)
        if ($responsableId === null || $responsableId === '') {
            return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
        }

        // Normalize to integer when possible
        if (is_string($responsableId) && ctype_digit($responsableId)) {
            $responsableId = (int) $responsableId;
        }

        $validator = \Illuminate\Support\Facades\Validator::make(['responsable_id' => $responsableId], ['responsable_id' => 'required|integer|exists:users,id']);
        $validator->validate();

        $e->responsable_id = $responsableId;
        // Mark as assigned (Activo) so frontend stops showing it as disponible
        $e->estado = $this->normalizeEstadoValue('activo');

        // If frontend provided an ubicacion as part of the assignment, allow updating it.
        // Accept shapes: ubicacion_id, ubicacion, ubicacionId, ubicacion_nombre
        $req = $request->all();
        $ubicacionCandidates = ['ubicacion_id','ubicacion','ubicacionId','ubicacionID','ubicacion_nombre','ubicacionNombre'];
        $requestedUbicacion = $this->findIdInPayload($req, $ubicacionCandidates);

        $ubicacionNombre = null;
        if (empty($requestedUbicacion)) {
            if (!empty($req['ubicacion_nombre'])) {
                $ubicacionNombre = $req['ubicacion_nombre'];
            } elseif (!empty($req['ubicacion']) && !is_numeric($req['ubicacion'])) {
                $ubicacionNombre = $req['ubicacion'];
            }
        }

        try {
            if (!empty($requestedUbicacion) && is_numeric($requestedUbicacion) && Ubicacion::find($requestedUbicacion)) {
                $e->ubicacion_id = (int) $requestedUbicacion;
            } elseif (!empty($ubicacionNombre)) {
                $found = Ubicacion::where('nombre', $ubicacionNombre)->first();
                if ($found) {
                    $e->ubicacion_id = $found->id;
                } else {
                    $created = Ubicacion::create(['nombre' => $ubicacionNombre, 'descripcion' => 'Creada desde asignar por frontend']);
                    $e->ubicacion_id = $created->id;
                }
            }
        } catch (\Throwable $ex) {
            // ignore and continue
        }

        $e->save();

        $note = $request->input('observaciones') ?? $request->input('nota') ?? $request->input('detalle') ?? null;
        if (empty($note)) {
            $note = 'Asignado a usuario ID '.$responsableId;
        }

        try {
            // Debug: log payload and note to diagnose missing observation cases
            \Illuminate\Support\Facades\Log::debug('EquipoController.asignar creating historial', ['equipo_id' => $e->id, 'request' => $request->all(), 'nota_used' => $note]);

            $archivoPath = null;
            // Prefer multipart 'archivo'
            if ($request->hasFile('archivo')) {
                try {
                    $file = $request->file('archivo');
                    $archivoPath = $file->store('asignaciones', 'public');
                } catch (\Throwable $ex) {
                    \Illuminate\Support\Facades\Log::error('EquipoController.asignar file save failed (archivo)', ['error' => $ex->getMessage()]);
                }
            }

            // Alternate name from frontend
            if (!$archivoPath && $request->hasFile('evidenceFile')) {
                try {
                    $file = $request->file('evidenceFile');
                    $archivoPath = $file->store('asignaciones', 'public');
                } catch (\Throwable $ex) {
                    \Illuminate\Support\Facades\Log::error('EquipoController.asignar file save failed (evidenceFile)', ['error' => $ex->getMessage()]);
                }
            }

            // Base64 payload
            if (!$archivoPath && ($request->filled('evidenceFileBase64') || $request->filled('evidenceFile'))) {
                $b64 = $request->input('evidenceFileBase64') ?? $request->input('evidenceFile');
                if (is_string($b64) && strpos($b64, 'data:') === 0) {
                    try {
                        [$meta, $data] = explode(',', $b64, 2) + [null, null];
                        if ($data) {
                            $decoded = base64_decode($data);
                            $ext = 'bin';
                            if (preg_match('/data:\/(\w+);/', $meta ?? '', $m)) $ext = $m[1];
                            $filename = 'asignacion_' . time() . '.' . $ext;
                            $path = 'asignaciones/' . $filename;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $decoded);
                            $archivoPath = $path;
                        }
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::error('EquipoController.asignar base64 save failed', ['error' => $ex->getMessage()]);
                    }
                }
            }

            $histData = [
                'equipo_id' => $e->id,
                'from_ubicacion_id' => $e->ubicacion_id,
                'to_ubicacion_id' => $e->ubicacion_id,
                'fecha' => now(),
                'nota' => $note,
                'tipo_accion' => 'ASIGNACION',
                'responsable_id' => $responsableId,
            ];

            if ($archivoPath) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('historial_movimientos', 'archivo')) {
                    $histData['archivo'] = $archivoPath;
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('historial_movimientos', 'archivos')) {
                    $histData['archivos'] = $archivoPath;
                } else {
                    \Illuminate\Support\Facades\Log::warning('EquipoController.asignar archivo saved but no column to persist', ['path' => $archivoPath]);
                }
            }

            $hist = HistorialMovimiento::create($histData);
            \Illuminate\Support\Facades\Log::debug('EquipoController.asignar historial created', ['hist_id' => $hist->id, 'archivo_saved' => $archivoPath ?? null]);
            // Intentar notificar por correo la asignación (no bloquear el flujo)
            try {
                $this->sendAssignNotificationIfEnabled($e, $hist);
            } catch (\Throwable $ex) {
                \Illuminate\Support\Facades\Log::warning('EquipoController.asignar sendAssignNotificationIfEnabled failed', ['error' => (string) $ex]);
            }
        } catch (\Throwable $ex) {
            // don't break assignment if historial logging fails
            \Illuminate\Support\Facades\Log::error('EquipoController.asignar historial create failed', ['error' => (string) $ex]);
        }

        // Return equipo and historial if available to help frontend
        try {
            if (isset($hist) && $hist instanceof \App\Models\HistorialMovimiento) {
                return response()->json([
                    'equipo' => new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable'])),
                    'historial' => new HistorialMovimientoResource($hist)
                ], 200);
            }
        } catch (\Throwable $__ex) {
            // ignore and fallback
        }

        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function recepcionar(Request $request, $id)
    {
        $e = Equipo::findOrFail($id);
        $previousResponsable = $e->responsable_id;
        $oldUbicacion = $e->ubicacion_id;
        $e->estado = $this->normalizeEstadoValue('recepcionado');
        // Clear responsable when recepcionar
        $e->responsable_id = null;

        // Log incoming request payload for diagnosis
        Log::debug('EquipoController.recepcionar incoming', ['equipo_id'=>$id, 'request'=>$request->all(), 'current_ubicacion'=>$e->ubicacion_id]);

        // If request provides an ubicacion, prefer it. Accept many shapes and nested forms.
        $ubicacionCandidates = ['ubicacion_id','ubicacion','ubicacionId','ubicacionID','ubicacionid','to_ubicacion_id','toUbicacionId','to_ubicacion'];
        $requestedUbicacion = $this->findIdInPayload($request->all(), $ubicacionCandidates);

        // Also accept a textual ubicacion name via 'ubicacion_nombre' or 'ubicacionNombre'
        $ubicacionNombre = null;
        if (empty($requestedUbicacion)) {
            if ($request->filled('ubicacion_nombre')) {
                $ubicacionNombre = $request->input('ubicacion_nombre');
            } elseif ($request->filled('ubicacionNombre')) {
                $ubicacionNombre = $request->input('ubicacionNombre');
            }
        }

        if (! empty($requestedUbicacion)) {
            // Normalize numeric strings
            if (is_string($requestedUbicacion) && ctype_digit($requestedUbicacion)) {
                $requestedUbicacion = (int) $requestedUbicacion;
            }
            // Prefer mapping to Departamento if the id corresponds to a department marked as bodega.
            try {
                $maybeDep = Departamento::find($requestedUbicacion);
                if ($maybeDep && ($maybeDep->es_bodega ?? false)) {
                    if (! empty($maybeDep->bodega_ubicacion_id) && Ubicacion::find($maybeDep->bodega_ubicacion_id)) {
                        $e->ubicacion_id = $maybeDep->bodega_ubicacion_id;
                    } else {
                        $created = Ubicacion::create([
                            'nombre' => $maybeDep->nombre,
                            'descripcion' => 'Funciona como bodega IT | AUTO_BODEGA_DEPARTAMENTO_'.$maybeDep->id
                        ]);
                        $maybeDep->bodega_ubicacion_id = $created->id;
                        $maybeDep->save();
                        $e->ubicacion_id = $created->id;
                    }
                } elseif (! empty($requestedUbicacion) && Ubicacion::find($requestedUbicacion)) {
                    // If not a bodega-department, and an Ubicacion with that id exists, use it
                    $e->ubicacion_id = $requestedUbicacion;
                } else {
                    // As a last resort, if it's a department (not marked es_bodega) and no Ubicacion exists, create the auto bodega
                    if ($maybeDep) {
                        if (! empty($maybeDep->bodega_ubicacion_id) && Ubicacion::find($maybeDep->bodega_ubicacion_id)) {
                            $e->ubicacion_id = $maybeDep->bodega_ubicacion_id;
                        } else {
                            $created = Ubicacion::create([
                                'nombre' => $maybeDep->nombre,
                                'descripcion' => 'Funciona como bodega IT | AUTO_BODEGA_DEPARTAMENTO_'.$maybeDep->id
                            ]);
                            $maybeDep->bodega_ubicacion_id = $created->id;
                            $maybeDep->save();
                            $e->ubicacion_id = $created->id;
                        }
                    }
                }
            } catch (\Throwable $ex) {
                // ignore
            }

            // If frontend provided a ubicacion name rather than an id, resolve/create it now
            if (empty($requestedUbicacion) && !empty($ubicacionNombre)) {
                try {
                    $found = Ubicacion::where('nombre', $ubicacionNombre)->first();
                    if ($found) {
                        $e->ubicacion_id = $found->id;
                    } else {
                        $created = Ubicacion::create(['nombre' => $ubicacionNombre, 'descripcion' => 'Creada desde recepcionar por frontend']);
                        $e->ubicacion_id = $created->id;
                    }
                } catch (\Throwable $ex) {
                    // ignore
                }
            }
        } else {
            // Fallback: route to previous responsable's departamento bodega if available
            try {
                if (! empty($previousResponsable)) {
                    $user = User::find($previousResponsable);
                    if ($user && ! empty($user->departamento_id)) {
                        $dep = Departamento::find($user->departamento_id);
                        if ($dep && ! empty($dep->bodega_ubicacion_id)) {
                            $e->ubicacion_id = $dep->bodega_ubicacion_id;
                        }
                    }
                }
            } catch (\Throwable $ex) {
                // Ignore lookup errors and continue with current ubicacion
            }
        }

        $e->save();

        Log::debug('EquipoController.recepcionar result', ['equipo_id'=>$e->id, 'old'=>$oldUbicacion, 'new'=>$e->ubicacion_id]);

        // Debug: report if request included files / content-type to help frontend integration
        try {
            Log::debug('EquipoController.recepcionar incoming request info', [
                'has_archivo' => $request->hasFile('archivo'),
                'has_evidenceFile' => $request->hasFile('evidenceFile'),
                'content_type' => $request->header('Content-Type'),
                'all_keys' => array_keys($request->all())
            ]);

            // Log raw files array size (non-sensitive)
            $files = $request->allFiles();
            Log::debug('EquipoController.recepcionar files count', ['count' => count($files), 'file_keys' => array_keys($files)]);

            // Log movement: prefer storing the observation provided by frontend
            $note = $request->input('observaciones') ?? $request->input('nota') ?? $request->input('detalle') ?? null;
            if (empty($note)) {
                $note = 'Recepcionado. Responsable anterior ID '.($previousResponsable ?? 'N/A');
            }
            $note = $request->input('observaciones') ?? $request->input('nota') ?? $request->input('detalle') ?? null;
            if (empty($note)) {
                $note = 'Recepcionado. Responsable anterior ID '.($previousResponsable ?? 'N/A');
            }
            $archivoPath = null;

            // 1) Multipart field 'archivo' (preferred)
            if ($request->hasFile('archivo')) {
                try {
                    $file = $request->file('archivo');
                    $archivoPath = $file->store('recepciones', 'public');
                } catch (\Throwable $ex) {
                    \Illuminate\Support\Facades\Log::error('EquipoController.recepcionar file save failed (archivo)', ['error' => $ex->getMessage()]);
                }
            }

            // 2) Multipart field 'evidenceFile' (frontend alternate name)
            if (!$archivoPath && $request->hasFile('evidenceFile')) {
                try {
                    $file = $request->file('evidenceFile');
                    $archivoPath = $file->store('recepciones', 'public');
                } catch (\Throwable $ex) {
                    \Illuminate\Support\Facades\Log::error('EquipoController.recepcionar file save failed (evidenceFile)', ['error' => $ex->getMessage()]);
                }
            }

            // 3) Base64 payload (campo 'evidenceFileBase64' o 'evidenceFile' con data: URI)
            if (!$archivoPath && ($request->filled('evidenceFileBase64') || $request->filled('evidenceFile'))) {
                $b64 = $request->input('evidenceFileBase64') ?? $request->input('evidenceFile');
                if (is_string($b64) && strpos($b64, 'data:') === 0) {
                    try {
                        // data:[<mediatype>][;base64],<data>
                        [$meta, $data] = explode(',', $b64, 2) + [null, null];
                        if ($data) {
                            $decoded = base64_decode($data);
                            $ext = 'bin';
                            if (preg_match('/data:\/(\w+);/', $meta ?? '', $m)) $ext = $m[1];
                            $filename = 'recepcion_' . time() . '.' . $ext;
                            $path = 'recepciones/' . $filename;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $decoded);
                            $archivoPath = $path;
                        }
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::error('EquipoController.recepcionar base64 save failed', ['error' => $ex->getMessage()]);
                    }
                }
            }

            $histData = [
                'equipo_id' => $e->id,
                'from_ubicacion_id' => $oldUbicacion,
                'to_ubicacion_id' => $e->ubicacion_id,
                'fecha' => now(),
                'nota' => $note,
                'tipo_accion' => 'RECEPCION',
                'responsable_id' => $previousResponsable,
            ];

            // Persist into either 'archivo' or 'archivos' column depending on schema
            if ($archivoPath) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('historial_movimientos', 'archivo')) {
                    $histData['archivo'] = $archivoPath;
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('historial_movimientos', 'archivos')) {
                    $histData['archivos'] = $archivoPath;
                } else {
                    // Column does not exist; still log where the file is stored
                    \Illuminate\Support\Facades\Log::warning('EquipoController.recepcionar archivo saved but no column to persist', ['path' => $archivoPath]);
                }
            }

            $hist = HistorialMovimiento::create($histData);
            Log::debug('EquipoController.recepcionar historial created', ['hist_id' => $hist->id, 'archivo_saved' => $archivoPath ?? null]);
        } catch (\Throwable $ex) {
            // Don't break the flow if logging fails; keep primary action successful
        }

        // Return equipo and last historial entry (if created) to help frontend confirm upload
        try {
            if (isset($hist) && $hist instanceof \App\Models\HistorialMovimiento) {
                return response()->json([
                    'equipo' => new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable'])),
                    'historial' => new HistorialMovimientoResource($hist)
                ], 200);
            }
        } catch (\Throwable $__ex) {
            // ignore and fallback to equipo only
        }

        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function notifyAsignacion(Request $request, $id)
    {
        try {
            $hist = HistorialMovimiento::with(['equipo','responsable'])->findOrFail($id);
            $equipo = $hist->equipo;

            $settings = EmailSetting::first();
            if (! $settings) {
                return response()->json(['message' => 'Email settings not configured'], 400);
            }
            if (! ($settings->notificar_asignacion ?? false)) {
                return response()->json(['message' => 'Notificaciones de asignación deshabilitadas'], 200);
            }

            $cc = is_array($settings->correos_copia) ? $settings->correos_copia : [];
            $to = null;
            $assignedName = null;
            if (! empty($equipo?->responsable_id)) {
                $user = User::find($equipo->responsable_id);
                $to = $user?->email ?? $user?->correo ?? null;
                $assignedName = $user?->name ?? $user?->nombre ?? null;
            }

            if (empty($to)) {
                if (! empty($cc)) {
                    $to = array_shift($cc);
                } else {
                    return response()->json(['message' => 'No hay destinatario configurado'], 400);
                }
            }

            $codigo = $equipo?->codigo_activo ?? ('Equipo #' . ($equipo?->id ?? $hist->equipo_id));
            $subject = "Asignación de equipo - {$codigo}";

            $lines = [];
            $lines[] = "Se ha asignado el equipo: {$codigo}";
            if (! empty($assignedName)) $lines[] = "Asignado a: {$assignedName}";
            if (! empty($hist->nota)) {
                $lines[] = "";
                $lines[] = "Observaciones:";
                $lines[] = $hist->nota;
            }
            $body = implode("\n", $lines);

            $attachments = [];
            if ($request->hasFile('archivo')) {
                $file = $request->file('archivo');
                $attachments[] = $file->store('asignaciones', 'public');
            } elseif (! empty($hist->archivo)) {
                $attachments[] = $hist->archivo;
            }

            $sent = app(DynamicEmailService::class)->sendRaw($to, $subject, $body, $cc, $attachments);

            return response()->json([
                'ok' => $sent,
                'to' => $to,
                'cc_count' => count($cc),
                'attachments' => $attachments,
            ]);
        } catch (\Throwable $e) {
            Log::error('notifyAsignacion failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['message' => 'Error enviando notificación', 'error' => $e->getMessage()], 500);
        }
    }

    public function darBaja(Request $request, $id)
    {
        $e = Equipo::findOrFail($id);
        $previousResponsable = $e->responsable_id;
        $oldUbicacion = $e->ubicacion_id;
        $e->estado = $this->normalizeEstadoValue('baja');
        $e->save();

        // Log the baja in historial, prefer frontend observation and accept an uploaded file
        try {
            $note = $request->input('observaciones') ?? $request->input('nota') ?? $request->input('detalle') ?? null;
            if (empty($note)) {
                $note = 'Dado de baja. Responsable anterior ID '.($previousResponsable ?? 'N/A');
            }

            $archivoPath = null;

            // Multipart field 'archivo' (preferred)
            if ($request->hasFile('archivo')) {
                try {
                    $file = $request->file('archivo');
                    $archivoPath = $file->store('bajas', 'public');
                } catch (\Throwable $ex) {
                    \Illuminate\Support\Facades\Log::error('EquipoController.darBaja file save failed (archivo)', ['error' => $ex->getMessage()]);
                }
            }

            // Alternate name from frontend
            if (!$archivoPath && $request->hasFile('evidenceFile')) {
                try {
                    $file = $request->file('evidenceFile');
                    $archivoPath = $file->store('bajas', 'public');
                } catch (\Throwable $ex) {
                    \Illuminate\Support\Facades\Log::error('EquipoController.darBaja file save failed (evidenceFile)', ['error' => $ex->getMessage()]);
                }
            }

            // Base64 payload
            if (!$archivoPath && ($request->filled('evidenceFileBase64') || $request->filled('evidenceFile'))) {
                $b64 = $request->input('evidenceFileBase64') ?? $request->input('evidenceFile');
                if (is_string($b64) && strpos($b64, 'data:') === 0) {
                    try {
                        [$meta, $data] = explode(',', $b64, 2) + [null, null];
                        if ($data) {
                            $decoded = base64_decode($data);
                            $ext = 'bin';
                            if (preg_match('/data:\/(\w+);/', $meta ?? '', $m)) $ext = $m[1];
                            $filename = 'baja_' . time() . '.' . $ext;
                            $path = 'bajas/' . $filename;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $decoded);
                            $archivoPath = $path;
                        }
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::error('EquipoController.darBaja base64 save failed', ['error' => $ex->getMessage()]);
                    }
                }
            }

            $histData = [
                'equipo_id' => $e->id,
                'from_ubicacion_id' => $oldUbicacion,
                'to_ubicacion_id' => null,
                'fecha' => now(),
                'nota' => $note,
                'tipo_accion' => 'BAJA',
                'responsable_id' => $previousResponsable,
            ];

            if ($archivoPath) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('historial_movimientos', 'archivo')) {
                    $histData['archivo'] = $archivoPath;
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('historial_movimientos', 'archivos')) {
                    $histData['archivos'] = $archivoPath;
                } else {
                    \Illuminate\Support\Facades\Log::warning('EquipoController.darBaja archivo saved but no column to persist', ['path' => $archivoPath]);
                }
            }

            $hist = HistorialMovimiento::create($histData);
            \Illuminate\Support\Facades\Log::debug('EquipoController.darBaja historial created', ['hist_id' => $hist->id, 'archivo_saved' => $archivoPath ?? null]);
        } catch (\Throwable $ex) {
            // keep primary action successful even if historial logging fails
        }

        // Return equipo and historial if available to help frontend
        try {
            if (isset($hist) && $hist instanceof \App\Models\HistorialMovimiento) {
                return response()->json([
                    'equipo' => new EquipoResource($e),
                    'historial' => new HistorialMovimientoResource($hist)
                ], 200);
            }
        } catch (\Throwable $__ex) {
            // ignore
        }

        return new EquipoResource($e);
    }

    public function marcarParaBaja(Request $request, $id)
    {
        $e = Equipo::findOrFail($id);
        $previousResponsable = $e->responsable_id;
        $oldUbicacion = $e->ubicacion_id;

        // Accept ubicacion info to place the equipo when marking for baja
        $req = $request->all();
        $ubicacionCandidates = ['ubicacion_id','ubicacion','ubicacionId','ubicacion_nombre','ubicacionNombre'];
        $requestedUbicacion = $this->findIdInPayload($req, $ubicacionCandidates);
        $ubicacionNombre = null;
        if (empty($requestedUbicacion)) {
            if (!empty($req['ubicacion_nombre'])) $ubicacionNombre = $req['ubicacion_nombre'];
            elseif (!empty($req['ubicacion']) && !is_numeric($req['ubicacion'])) $ubicacionNombre = $req['ubicacion'];
        }
        try {
            if (!empty($requestedUbicacion) && is_numeric($requestedUbicacion) && Ubicacion::find($requestedUbicacion)) {
                $e->ubicacion_id = (int) $requestedUbicacion;
            } elseif (!empty($ubicacionNombre)) {
                $found = Ubicacion::where('nombre', $ubicacionNombre)->first();
                if ($found) $e->ubicacion_id = $found->id;
                else {
                    $created = Ubicacion::create(['nombre' => $ubicacionNombre, 'descripcion' => 'Creada desde marcarParaBaja por frontend']);
                    $e->ubicacion_id = $created->id;
                }
            }
        } catch (\Throwable $ex) {
            // ignore
        }

        $e->estado = $this->normalizeEstadoValue('para_baja');
        $e->save();

        try {
            $note = $request->input('observaciones') ?? $request->input('nota') ?? $request->input('detalle') ?? 'Marcado para baja';
            HistorialMovimiento::create([
                'equipo_id' => $e->id,
                'from_ubicacion_id' => $oldUbicacion,
                'to_ubicacion_id' => $e->ubicacion_id,
                'fecha' => now(),
                'nota' => $note,
                'tipo_accion' => 'PRE_BAJA',
                'responsable_id' => $previousResponsable,
            ]);
        } catch (\Throwable $ex) {
            // ignore
        }

        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function enviarMantenimiento($id, Request $request)
    {
        $e = Equipo::findOrFail($id);
        // Prevent sending if equipo already appears to be in maintenance (case-insensitive check)
        if (!empty($e->estado) && stripos($e->estado, 'manten') !== false) {
            return response()->json(['message' => 'El equipo ya se encuentra en mantenimiento'], 409);
        }
        // Accept multiple possible keys for description (motivo, problema, fallo)
        $desc = $request->input('descripcion') ?? $request->input('motivo') ?? $request->input('problema') ?? $request->input('fallo') ?? null;
        $fechaInicio = $request->input('fecha_inicio') ?? $request->input('fecha') ?? now();
        $tipo = $request->input('tipo') ?? $request->input('tipo_mantenimiento') ?? null;
        $proveedor = $request->input('proveedor') ?? null;

        $mData = [
            'equipo_id' => $e->id,
            'descripcion' => $desc,
            'fecha_inicio' => $fechaInicio,
            'estado' => 'pendiente',
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('mantenimientos', 'tipo')) {
            $mData['tipo'] = $tipo;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('mantenimientos', 'proveedor')) {
            $mData['proveedor'] = $proveedor;
        }
        $m = Mantenimiento::create($mData);
        // If the request provided a description for the problema/motivo, persist it on the equipo
        if (!empty($desc)) {
            $e->observaciones = $desc;
        }
        $e->estado = $this->normalizeEstadoValue('en_mantenimiento');
        $e->save();
        return new MantenimientoResource($m);
    }

    public function finalizarMantenimiento($id, Request $request)
    {
        // Accept multiple possible keys for maintenance id
        $mId = null;
        foreach (['mantenimiento_id', 'mantenimientoId', 'id'] as $k) {
            if ($request->filled($k)) { $mId = $request->input($k); break; }
        }

        if ($mId) {
            $m = Mantenimiento::find($mId);
            if (! $m) {
                return response()->json(['message' => 'Mantenimiento no encontrado con id proporcionado'], 404);
            }
        } else {
            // Fallback: try to find the latest pending/en_mantenimiento mantenimiento for this equipo
            $m = Mantenimiento::where('equipo_id', $id)
                ->whereIn('estado', ['pendiente', 'en_mantenimiento'])
                ->orderByDesc('fecha_inicio')
                ->first();

            if (! $m) {
                return response()->json(['message' => 'No se encontró mantenimiento pendiente para este equipo.'], 404);
            }
        }

        // Allow updating proveedor and descripcion on finalize
        $m->fecha_fin = now();
        $m->estado = 'finalizado';
        $m->costo = $request->input('costo', $m->costo ?? 0);
        if (\Illuminate\Support\Facades\Schema::hasColumn('mantenimientos', 'proveedor') && $request->filled('proveedor')) {
            $m->proveedor = $request->input('proveedor');
        }
        // Allow updating tipo on finalize if provided
        $tipoFinal = $request->input('tipo') ?? $request->input('tipo_mantenimiento') ?? null;
        if (\Illuminate\Support\Facades\Schema::hasColumn('mantenimientos', 'tipo') && $tipoFinal !== null) {
            $m->tipo = $tipoFinal;
        }
        // Accept various fields for description on finalize
        $finalDesc = $request->input('descripcion') ?? $request->input('detalle') ?? $request->input('descripcion_final') ?? null;
        if ($finalDesc !== null) {
            $m->descripcion = $finalDesc;
        }
        // Handle optional uploaded file for signed maintenance order
        if ($request->hasFile('archivo_orden') || $request->hasFile('archivo')) {
            try {
                $fileKey = $request->hasFile('archivo_orden') ? 'archivo_orden' : 'archivo';
                $file = $request->file($fileKey);
                $path = $file->store('mantenimientos', 'public');
                // store path on model if column exists
                if (\Illuminate\Support\Facades\Schema::hasColumn('mantenimientos', 'archivo_orden')) {
                    $m->archivo_orden = $path;
                }
            } catch (\Throwable $e) {
                // log and continue
                \Illuminate\Support\Facades\Log::error('EquipoController::finalizarMantenimiento file save error: ' . $e->getMessage());
            }
        }
        $m->save();

        // Notificación por correo (si está habilitada)
        try {
            $detalleCorreo = $request->input('descripcion') ?? $request->input('detalle') ?? $request->input('descripcion_final') ?? null;
            $this->sendMaintenanceNotificationIfEnabled($m->equipo, $m, is_string($detalleCorreo) ? $detalleCorreo : null);
        } catch (\Throwable $e) {
            // ignore
        }
        // After saving the mantenimiento, if it references a plan detail, ensure the detalle is marked Realizado.
        try {
            if (!empty($m->plan_detail_id)) {
                $detalleFallback = DetallePlanMantenimiento::find($m->plan_detail_id);
                if ($detalleFallback) {
                    $detalleFallback->estado = 'Realizado';
                    $detalleFallback->fecha_ejecucion = $m->fecha_fin ?? now()->toDateString();
                    if ($request->filled('tecnico')) {
                        $detalleFallback->tecnico_responsable = $request->input('tecnico');
                    }
                    $detalleFallback->save();
                    logger()->info('finalizarMantenimiento: detalle actualizado desde plan_detail_id', ['mantenimiento_id' => $m->id, 'detail_id' => $detalleFallback->id, 'estado' => $detalleFallback->estado]);
                }
            }
        } catch (\Throwable $ex) {
            logger()->warning('finalizarMantenimiento: no se pudo actualizar detalle desde plan_detail_id', ['error' => $ex->getMessage()]);
        }

        // If the request includes a plan detail id, create an EjecucionMantenimiento and update the DetallePlan
        $detailId = null;
        foreach (['detail_id','detalle_id','plan_detail_id','planDetailId'] as $k) {
            if ($request->filled($k)) { $detailId = $request->input($k); break; }
        }

        // If request didn't explicitly include a detail id, but the Mantenimiento was started
        // from a plan and has `plan_detail_id`, prefer that so the plan detalle is updated.
        if (!$detailId && isset($m) && !empty($m->plan_detail_id)) {
            $detailId = $m->plan_detail_id;
            logger()->info('finalizarMantenimiento: usando plan_detail_id desde mantenimiento', ['mantenimiento_id' => $m->id, 'plan_detail_id' => $detailId]);
        }

        if ($detailId) {
            try {
                $fechaExec = $request->input('fecha') ?? $m->fecha_fin ?? now()->toDateString();
                $tecnicoExec = $request->input('tecnico') ?? $request->input('tecnico_responsable') ?? null;
                $observ = $request->input('observaciones') ?? $request->input('descripcion') ?? null;
                $archivoPath = null;
                if ($request->hasFile('archivo')) {
                    $file = $request->file('archivo');
                    $archivoPath = $file->store('mantenimientos', 'public');
                }

                $exec = EjecucionMantenimiento::create([
                    'detail_id' => $detailId,
                    'fecha' => $fechaExec,
                    'tecnico' => $tecnicoExec,
                    'observaciones' => $observ,
                    'archivo' => $archivoPath,
                ]);

                // Mark detalle as Realizado
                $detalle = DetallePlanMantenimiento::find($detailId);
                if ($detalle) {
                    $detalle->estado = 'Realizado';
                    $detalle->fecha_ejecucion = $fechaExec;
                    if ($tecnicoExec) $detalle->tecnico_responsable = $tecnicoExec;
                    $detalle->save();
                }
                logger()->info('Ejecucion creada via finalizarMantenimiento', ['exec_id' => $exec->id, 'detail_id' => $detailId]);
            } catch (\Throwable $ex) {
                logger()->warning('Error creando ejecucion en finalizarMantenimiento', ['error' => $ex->getMessage()]);
            }
        }
        $e = $m->equipo;
        // Actualizar especificaciones técnicas si vienen en el request
        $specs = [
            'serie_cargador',
            'procesador',
            'ram',
            'disco_capacidad',
            'disco_tipo',
            'sistema_operativo',
        ];
        foreach ($specs as $key) {
            if ($request->filled($key)) {
                $e->{$key} = $request->input($key);
            }
        }
        // Normalize nuevo_estado in finalizarMantenimiento flow (consistent states)
        $nuevo = $request->input('nuevo_estado', 'activo');
        $normalized = $this->normalizeEstadoValue($nuevo);

        // If the equipo currently has a responsable, prefer 'Activo' so it remains assigned
        if (! is_null($e->responsable_id)) {
            $e->estado = $this->normalizeEstadoValue('activo');
        } else {
            $e->estado = $normalized;
        }
        $e->save();

        return new MantenimientoResource($m->load('equipo'));
    }

    // Upload an evidence file for an assignment (historial movimiento)
    public function subirArchivoAsignacion(Request $request, $id)
    {
        $hist = HistorialMovimiento::findOrFail($id);

        if (! $request->hasFile('archivo')) {
            return response()->json(['message' => 'Archivo no proporcionado'], 422);
        }

        try {
            $file = $request->file('archivo');
            $path = $file->store('asignaciones', 'public');
            $hist->archivo = $path;
            $hist->save();
        } catch (\Throwable $ex) {
            return response()->json(['message' => 'Error al subir archivo', 'error' => (string) $ex], 500);
        }

        return new HistorialMovimientoResource($hist->load(['responsable','equipo','fromUbicacion','toUbicacion']));
    }
}
