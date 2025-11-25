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
        // Accept both `user_id` (english) and `usuario_id` (spanish) from frontend
        $userId = $request->input('user_id') ?? $request->input('usuario_id');

        if (empty($userId) || !is_numeric($userId)) {
            return response()->json(['message' => 'The user id field is required.'], 422);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 422);
        }

        $licencia = Licencia::findOrFail($id);

        if ($licencia->user_id) {
            return response()->json(['message' => 'Esta licencia ya está asignada.'], 422);
        }

        $licencia->user_id = $user->id;
        $licencia->save();

        return new LicenciaResource($licencia->load('user'));
    }

    public function liberar(Request $request, $id)
    {
        // Accept both `user_id` and `usuario_id`, or fallback to authenticated user
        $userId = $request->input('user_id') ?? $request->input('usuario_id') ?? ($request->user()?->id ?? null);

        if (empty($userId) || !is_numeric($userId)) {
            return response()->json(['message' => 'The user id field is required.'], 422);
        }

        // Find the license first, avoid throwing ModelNotFoundException to return friendly messages
        $licencia = Licencia::find($id);
        if (! $licencia) {
            return response()->json(['message' => 'Licencia no encontrada.'], 404);
        }

        if (is_null($licencia->user_id)) {
            return response()->json(['message' => 'La licencia ya está libre.'], 422);
        }

        if ((int)$licencia->user_id !== (int)$userId) {
            // If authenticated user is admin allow override, otherwise return clear error
            $authUser = $request->user();
            if ($authUser && isset($authUser->rol) && $authUser->rol === 'Administrador') {
                // allow admin to release any license
            } else {
                return response()->json(['message' => 'La licencia no está asignada a ese usuario.'], 403);
            }
        }

        $licencia->user_id = null;
        $licencia->save();

        return new LicenciaResource($licencia);
    }
}