<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
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

        $d->update($payload);
        return new DepartamentoResource($d);
    }

    public function destroy($id)
    {
        Departamento::destroy($id);
        return response()->noContent();
    }
}
