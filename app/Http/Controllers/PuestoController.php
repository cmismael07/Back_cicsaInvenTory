<?php

namespace App\Http\Controllers;

use App\Models\Puesto;
use Illuminate\Http\Request;
use App\Http\Resources\PuestoResource;

class PuestoController extends Controller
{
    public function index()
    {
        return PuestoResource::collection(Puesto::all());
    }

    public function store(Request $request)
    {
        $input = $request->all();
        // Normalize when frontend sends nested object or raw string
        if (array_key_exists('nombre', $input)) {
            if (is_array($input['nombre']) && isset($input['nombre']['nombre'])) {
                $input['nombre'] = $input['nombre']['nombre'];
            } elseif (is_array($input['nombre']) && isset($input['nombre']['name'])) {
                $input['nombre'] = $input['nombre']['name'];
            } elseif (is_object($input['nombre'])) {
                $obj = (array) $input['nombre'];
                if (isset($obj['nombre'])) $input['nombre'] = $obj['nombre'];
                elseif (isset($obj['name'])) $input['nombre'] = $obj['name'];
            }
        }

        $nombre = $input['nombre'] ?? $request->input('nombre');
        $payload = ['nombre' => is_string($nombre) ? trim($nombre) : $nombre];
        $request->merge($payload);
        $data = $request->validate([
            'nombre' => 'required|string',
        ]);

        $p = Puesto::create($data);
        return new PuestoResource($p);
    }

    public function show($id)
    {
        return new PuestoResource(Puesto::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $p = Puesto::findOrFail($id);
        $input = $request->all();
        if (array_key_exists('nombre', $input)) {
            if (is_array($input['nombre']) && isset($input['nombre']['nombre'])) {
                $input['nombre'] = $input['nombre']['nombre'];
            } elseif (is_array($input['nombre']) && isset($input['nombre']['name'])) {
                $input['nombre'] = $input['nombre']['name'];
            } elseif (is_object($input['nombre'])) {
                $obj = (array) $input['nombre'];
                if (isset($obj['nombre'])) $input['nombre'] = $obj['nombre'];
                elseif (isset($obj['name'])) $input['nombre'] = $obj['name'];
            }
        }

        $nombre = $input['nombre'] ?? $request->input('nombre');
        $payload = ['nombre' => is_string($nombre) ? trim($nombre) : $nombre];
        $request->merge($payload);
        $data = $request->validate([
            'nombre' => 'required|string',
        ]);

        $p->update($data);
        return new PuestoResource($p);
    }

    public function destroy($id)
    {
        Puesto::destroy($id);
        return response()->noContent();
    }
}
