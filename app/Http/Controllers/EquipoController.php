<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\HistorialMovimiento;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use App\Http\Resources\EquipoResource;
use App\Http\Resources\MantenimientoResource;
use App\Http\Resources\HistorialMovimientoResource;

class EquipoController extends Controller
{
    public function index()
    {
        return EquipoResource::collection(Equipo::with(['tipo_equipo','ubicacion','responsable'])->get());
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
        $mapToId($input, 'responsable');

        if (isset($input['codigoActivo']) && !isset($input['codigo_activo'])) {
            $input['codigo_activo'] = $input['codigoActivo'];
        }

        // Map frontend field names to DB fields
        if (isset($input['numero_serie']) && ! isset($input['serial'])) {
            $input['serial'] = $input['numero_serie'];
        }
        if (isset($input['anos_garantia']) && ! isset($input['garantia_meses'])) {
            // Frontend sends warranty in years (anos_garantia); store in months
            $years = $input['anos_garantia'];
            $input['garantia_meses'] = is_numeric($years) ? (int) ($years * 12) : $years;
        }

        // If ubicacion_id is missing, try env DEFAULT_UBICACION_ID, otherwise use first Ubicacion or create a default one
        if (empty($input['ubicacion_id'])) {
            $defaultId = env('DEFAULT_UBICACION_ID');
            if ($defaultId) {
                $exists = Ubicacion::find($defaultId);
                if ($exists) {
                    $input['ubicacion_id'] = $defaultId;
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

        $payload = \Illuminate\Support\Arr::only($input, [
            'tipo_equipo_id', 'ubicacion_id', 'responsable_id', 'codigo_activo', 'marca', 'modelo', 'serial', 'estado', 'fecha_compra', 'garantia_meses', 'valor_compra', 'observaciones'
        ]);

        $validator = \Illuminate\Support\Facades\Validator::make($payload, [
            'tipo_equipo_id' => 'required|integer|exists:tipo_equipos,id',
            'ubicacion_id' => 'nullable|integer|exists:ubicaciones,id',
            'responsable_id' => 'nullable|integer|exists:users,id',
            'codigo_activo' => 'required|string|unique:equipos,codigo_activo',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'serial' => 'nullable|string',
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

        $payload = \Illuminate\Support\Arr::only($input, [
            'tipo_equipo_id', 'ubicacion_id', 'responsable_id', 'codigo_activo', 'marca', 'modelo', 'serial', 'estado', 'fecha_compra', 'garantia_meses', 'valor_compra', 'observaciones'
        ]);

        $validator = \Illuminate\Support\Facades\Validator::make($payload, [
            'tipo_equipo_id' => 'sometimes|required|integer|exists:tipo_equipos,id',
            'ubicacion_id' => 'sometimes|required|integer|exists:ubicaciones,id',
            'responsable_id' => 'nullable|integer|exists:users,id',
            'codigo_activo' => 'sometimes|required|string|unique:equipos,codigo_activo,'.$e->id,
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'serial' => 'nullable|string',
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
        // Mark as assigned (activo) so frontend stops showing it as disponible
        $e->estado = 'activo';
        $e->save();

        $hist = HistorialMovimiento::create([
            'equipo_id' => $e->id,
            'from_ubicacion_id' => $e->ubicacion_id,
            'to_ubicacion_id' => $e->ubicacion_id,
            'fecha' => now(),
            'nota' => 'Asignado a usuario ID '.$responsableId,
            'responsable_id' => $responsableId,
        ]);

        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function recepcionar($id)
    {
        $e = Equipo::findOrFail($id);
        $previousResponsable = $e->responsable_id;
        $e->estado = 'recepcionado';
        // Clear responsable when recepcionar
        $e->responsable_id = null;
        $e->save();

        // Log movement: record that equipo fue recepcionado y responsable removido
        try {
            HistorialMovimiento::create([
                'equipo_id' => $e->id,
                'from_ubicacion_id' => $e->ubicacion_id,
                'to_ubicacion_id' => $e->ubicacion_id,
                'fecha' => now(),
                'nota' => 'Recepcionado. Responsable anterior ID '.($previousResponsable ?? 'N/A'),
                'responsable_id' => $previousResponsable,
            ]);
        } catch (\Throwable $ex) {
            // Don't break the flow if logging fails; keep primary action successful
        }

        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function darBaja($id)
    {
        $e = Equipo::findOrFail($id);
        $e->estado = 'baja';
        $e->save();
        return new EquipoResource($e);
    }

    public function enviarMantenimiento($id, Request $request)
    {
        $e = Equipo::findOrFail($id);
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
        $e->estado = 'en_mantenimiento';
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
        $m->save();

        $e = $m->equipo;
        // Use requested nuevo_estado if provided, otherwise default to 'activo'
        $nuevo = $request->input('nuevo_estado', 'activo');
        $normalized = $nuevo === 'DISPONIBLE' || strtolower($nuevo) === 'disponible' ? 'disponible' : strtolower($nuevo);

        // If the equipo currently has a responsable, prefer 'activo' so it remains assigned
        if (! is_null($e->responsable_id)) {
            $e->estado = 'activo';
        } else {
            $e->estado = $normalized;
        }
        $e->save();

        return new MantenimientoResource($m->load('equipo'));
    }
}
