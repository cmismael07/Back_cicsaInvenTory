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
        $t = TipoEquipo::create($request->only(['nombre','descripcion']));
        return new TipoEquipoResource($t);
    }

    public function show($id)
    {
        return new TipoEquipoResource(TipoEquipo::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $t = TipoEquipo::findOrFail($id);
        $t->update($request->only(['nombre','descripcion']));
        return new TipoEquipoResource($t);
    }

    public function destroy($id)
    {
        TipoEquipo::destroy($id);
        return response()->noContent();
    }
}
