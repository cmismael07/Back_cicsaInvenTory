<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use App\Http\Resources\DepartamentoResource;

class DepartamentoController extends Controller
{
    public function index()
    {
        return DepartamentoResource::collection(Departamento::all());
    }

    public function store(Request $request)
    {
        // Handle case where frontend posts nombre as an object: { nombre: { nombre: 'X', es_bodega: true } }
        $rawNombre = $request->input('nombre');
        $payload = ['nombre' => null];

        if (is_array($rawNombre) || is_object($rawNombre)) {
            $rn = (array) $rawNombre;
            $payload['nombre'] = $rn['nombre'] ?? $rn['nombre'] ?? null;
            if (array_key_exists('es_bodega', $rn)) {
                $payload['es_bodega'] = filter_var($rn['es_bodega'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            }
        } else {
            $payload['nombre'] = $rawNombre;
        }

        // Also accept boolean variants at top-level
        if ($request->filled('es_bodega') || $request->has('es_bodega')) {
            $payload['es_bodega'] = $request->boolean('es_bodega');
        } elseif ($request->filled('esBodega') || $request->has('esBodega')) {
            $payload['es_bodega'] = $request->boolean('esBodega');
        } elseif ($request->filled('is_bodega') || $request->has('is_bodega')) {
            $payload['es_bodega'] = $request->boolean('is_bodega');
        }

        $d = Departamento::create($payload);

        // If this departamento is flagged as bodega, ensure an Ubicacion exists and store its id
        if (! empty($payload['es_bodega'])) {
            $marker = "AUTO_BODEGA_DEPARTAMENTO_{$d->id}";
            $exists = Ubicacion::where('descripcion', 'like', "%{$marker}%")->first();
            if (! $exists) {
                $u = Ubicacion::create([
                    'nombre' => $d->nombre,
                    'descripcion' => "Funciona como bodega IT | {$marker}",
                ]);
                $d->bodega_ubicacion_id = $u->id;
                $d->save();
            } else {
                // ensure reference set
                if (empty($d->bodega_ubicacion_id)) {
                    $d->bodega_ubicacion_id = $exists->id;
                    $d->save();
                }
            }
        }
        return new DepartamentoResource($d);
    }

    public function show($id)
    {
        return new DepartamentoResource(Departamento::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $d = Departamento::findOrFail($id);
        // Support nested nombre object from frontend
        $rawNombre = $request->input('nombre');
        if (is_array($rawNombre) || is_object($rawNombre)) {
            $rn = (array) $rawNombre;
            $payload = ['nombre' => $rn['nombre'] ?? $d->nombre];
            if (array_key_exists('es_bodega', $rn)) {
                $payload['es_bodega'] = filter_var($rn['es_bodega'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            }
        } else {
            $payload = $request->only('nombre');
        }

        // Normalize boolean variants at top-level as well
        if ($request->filled('es_bodega') || $request->has('es_bodega')) {
            $payload['es_bodega'] = $request->boolean('es_bodega');
        } elseif ($request->filled('esBodega') || $request->has('esBodega')) {
            $payload['es_bodega'] = $request->boolean('esBodega');
        } elseif ($request->filled('is_bodega') || $request->has('is_bodega')) {
            $payload['es_bodega'] = $request->boolean('is_bodega');
        }

        $wasBodega = (bool) $d->es_bodega;
        $d->update($payload);
        $isBodegaNow = (bool) $d->es_bodega;

        // Marker for auto-created ubicaciones for this departamento
        $marker = "AUTO_BODEGA_DEPARTAMENTO_{$d->id}";

        // If it transitioned to bodega, create Ubicacion if missing and store reference
        if (! $wasBodega && $isBodegaNow) {
            $exists = Ubicacion::where('descripcion', 'like', "%{$marker}%")->first();
            if (! $exists) {
                $u = Ubicacion::create([
                    'nombre' => $d->nombre,
                    'descripcion' => "Funciona como bodega IT | {$marker}",
                ]);
                $d->bodega_ubicacion_id = $u->id;
                $d->save();
            } else {
                if (empty($d->bodega_ubicacion_id)) {
                    $d->bodega_ubicacion_id = $exists->id;
                    $d->save();
                }
            }
        }

        // If it remained a bodega and the nombre changed, update the ubicacion name(s)
        if ($wasBodega && $isBodegaNow) {
            Ubicacion::where('descripcion', 'like', "%{$marker}%")->get()->each(function ($u) use ($d) {
                try { $u->nombre = $d->nombre; $u->save(); } catch (\Throwable $e) { /* ignore */ }
            });
            // also update stored reference name if present
            if (! empty($d->bodega_ubicacion_id)) {
                try {
                    $uRef = Ubicacion::find($d->bodega_ubicacion_id);
                    if ($uRef) { $uRef->nombre = $d->nombre; $uRef->save(); }
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // If it was a bodega and now is not, remove the auto-created Ubicacion(s)
        if ($wasBodega && ! $isBodegaNow) {
            Ubicacion::where('descripcion', 'like', "%{$marker}%")->get()->each(function ($u) use ($d) {
                try { $u->delete(); } catch (\Throwable $e) { /* ignore */ }
            });
            // clear the reference
            $d->bodega_ubicacion_id = null;
            $d->save();
        }
        return new DepartamentoResource($d);
    }

    public function destroy($id)
    {
        Departamento::destroy($id);
        return response()->noContent();
    }
}
