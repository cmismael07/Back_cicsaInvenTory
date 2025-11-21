<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use Illuminate\Http\Request;
use App\Http\Resources\LicenciaResource;

class LicenciaController extends Controller
{
    public function index()
    {
        return LicenciaResource::collection(Licencia::with('tipo_licencia','user')->get());
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'tipo_licencia_id' => 'required|integer|exists:tipo_licencias,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'clave' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'stock' => 'nullable|integer',
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
            'user_id' => 'nullable|integer|exists:users,id',
            'clave' => 'nullable|string',
            'fecha_compra' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'stock' => 'nullable|integer',
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
        return new LicenciaResource($l);
    }

    public function asignar($id)
    {
        $l = Licencia::findOrFail($id);
        $payload = request()->validate(['user_id' => 'required|integer']);
        if ($l->stock <= 0) {
            return response()->json(['message' => 'No hay stock disponible'], 422);
        }
        $l->user_id = $payload['user_id'];
        $l->stock = max(0, $l->stock - 1);
        $l->save();
        return new LicenciaResource($l);
    }

    public function liberar($id)
    {
        $l = Licencia::findOrFail($id);
        $payload = request()->validate(['user_id' => 'nullable|integer']);
        $l->user_id = null;
        $l->stock = $l->stock + 1;
        $l->save();
        return new LicenciaResource($l);
    }
}
