<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use Illuminate\Http\Request;
use App\Http\Resources\TipoEquipoResource;

class TipoEquipoController extends Controller
{
    public function index()
    {
        return TipoEquipoResource::collection(TipoEquipo::all());
    }

    public function store(Request $request)
    {
        $payload = $request->only(['nombre', 'descripcion', 'frecuencia_anual']);
        // default to 1 if not provided
        if (! isset($payload['frecuencia_anual']) || $payload['frecuencia_anual'] === null) {
            $payload['frecuencia_anual'] = 1;
        }
        $payload['considerar_recambio'] = $request->boolean('considerar_recambio', true);
        $t = TipoEquipo::create($payload);
        return new TipoEquipoResource($t);
    }

    public function show($id)
    {
        return new TipoEquipoResource(TipoEquipo::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $t = TipoEquipo::findOrFail($id);
        $payload = $request->only(['nombre', 'descripcion', 'frecuencia_anual']);
        if ($request->has('considerar_recambio')) {
            $payload['considerar_recambio'] = $request->boolean('considerar_recambio');
        }
        $t->update($payload);
        return new TipoEquipoResource($t);
    }

    public function destroy($id)
    {
        TipoEquipo::destroy($id);
        return response()->noContent();
    }
}
