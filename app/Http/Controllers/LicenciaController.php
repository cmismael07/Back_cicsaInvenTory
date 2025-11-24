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
        return LicenciaResource::collection(Licencia::with('tipo_licencia','asignaciones')->get());
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'tipo_licencia_id' => 'required|integer|exists:tipo_licencias,id',
            'clave' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
        ]);
        $l = Licencia::create($payload);
        return new LicenciaResource($l);
    }

    public function show($id)
    {
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

    public function addStock(Request $request)
    {
        $request->validate(['licencia_id' => 'required|integer','amount' => 'required|integer']);
        $l = Licencia::findOrFail($request->licencia_id);
        $l->stock = max(0, $l->stock + intval($request->amount));
        $l->save();
        return new LicenciaResource($l->load('asignaciones'));
    }

   public function asignar(Request $request, $id)
    {
        $payload = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $licencia = Licencia::with('asignaciones')->findOrFail($id);
        $user = User::findOrFail($payload['user_id']);
        
        // Usamos el atributo calculado 'disponible'
        if ($licencia->disponible <= 0) {
            return response()->json(['message' => 'No hay stock disponible para esta licencia.'], 422);
        }

        // Verificar que el usuario no tenga ya esta licencia
        if ($licencia->asignaciones()->where('user_id', $user->id)->exists()) {
             return response()->json(['message' => 'El usuario ya tiene asignada esta licencia.'], 422);
        }

        // Asignar usando la tabla intermedia
        $licencia->asignaciones()->attach($user->id);

        return new LicenciaResource($licencia->load('asignaciones'));
    }

    public function liberar(Request $request, $id)
    {
        $payload = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $licencia = Licencia::findOrFail($id);
        
        // Liberar de la tabla intermedia
        $licencia->asignaciones()->detach($payload['user_id']);
        
        return new LicenciaResource($licencia->load('asignaciones'));
    }
}
