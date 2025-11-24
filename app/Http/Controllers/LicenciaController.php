<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\LicenciaResource;

class LicenciaController extends Controller
{
    public function index()
    {
        // Eager load using relationship names defined in the model
        return LicenciaResource::collection(Licencia::with('tipo_licencia','user')->get());
    }

    // ... store, show, update, destroy se mantienen para gestión individual si es necesario ...
    public function store(Request $request)
    {
        $payload = $request->validate([
            'tipo_licencia_id' => 'required|integer|exists:tipos_licencia,id',
            'clave' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
        ]);
        $l = Licencia::create($payload);
        return new LicenciaResource($l);
    }
    
    public function show($id)
    {
        // Corregido a las relaciones definidas en el modelo
        return new LicenciaResource(Licencia::with('tipo_licencia','user')->findOrFail($id));
    }
    
    public function update(Request $request, $id)
    {
        $l = Licencia::findOrFail($id);
        $payload = $request->validate([
            'tipo_licencia_id' => 'sometimes|required|integer|exists:tipo_licencias,id',
            'clave' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
        ]);
        $l->update($payload);
        return new LicenciaResource($l);
    }
    
    public function destroy($id)
    {
        Licencia::destroy($id);
        return response()->noContent();
    }
    
    // *** MÉTODO addStock ELIMINADO ***

    public function asignar(Request $request, $id)
    {
        $payload = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $licencia = Licencia::findOrFail($id);
        
        if ($licencia->user_id) {
            return response()->json(['message' => 'Esta licencia ya está asignada.'], 422);
        }

        $licencia->user_id = $payload['user_id'];
        $licencia->save();

        return new LicenciaResource($licencia->load('user'));
    }

    public function liberar(Request $request, $id)
    {
        $payload = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $licencia = Licencia::where('id', $id)->where('user_id', $payload['user_id'])->firstOrFail();
        
        $licencia->user_id = null;
        $licencia->save();
        
        return new LicenciaResource($licencia);
    }
}