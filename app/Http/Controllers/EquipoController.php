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

    public function asignar($id, Request $request)
    {
        $e = Equipo::findOrFail($id);
        // Allow calls without responsable_id: if absent, return equipo unchanged
        $data = $request->only('responsable_id');
        if (! array_key_exists('responsable_id', $data) || $data['responsable_id'] === null || $data['responsable_id'] === '') {
            return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
        }

        $validator = \Illuminate\Support\Facades\Validator::make($data, ['responsable_id' => 'required|integer|exists:users,id']);
        $validator->validate();

        $e->responsable_id = $data['responsable_id'];
        $e->save();

        $hist = HistorialMovimiento::create([
            'equipo_id' => $e->id,
            'from_ubicacion_id' => $e->ubicacion_id,
            'to_ubicacion_id' => $e->ubicacion_id,
            'fecha' => now(),
            'nota' => 'Asignado a usuario ID '.$data['responsable_id'],
            'responsable_id' => $data['responsable_id'],
        ]);

        return new EquipoResource($e->load(['tipo_equipo','ubicacion','responsable']));
    }

    public function recepcionar($id)
    {
        $e = Equipo::findOrFail($id);
        $e->estado = 'recepcionado';
        $e->save();
        return new EquipoResource($e);
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
        $data = $request->validate(['descripcion' => 'nullable|string','fecha_inicio' => 'nullable|date']);
        $m = Mantenimiento::create([
            'equipo_id' => $e->id,
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'] ?? now(),
            'estado' => 'pendiente',
        ]);
        $e->estado = 'en_mantenimiento';
        $e->save();
        return new MantenimientoResource($m);
    }

    public function finalizarMantenimiento($id, Request $request)
    {
        $m = Mantenimiento::findOrFail($request->input('mantenimiento_id'));
        $m->fecha_fin = now();
        $m->estado = 'finalizado';
        $m->costo = $request->input('costo', 0);
        $m->save();

        $e = $m->equipo;
        $e->estado = 'activo';
        $e->save();

        return new MantenimientoResource($m);
    }
}
