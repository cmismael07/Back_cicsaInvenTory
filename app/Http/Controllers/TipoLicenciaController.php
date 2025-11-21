<?php

namespace App\Http\Controllers;

use App\Models\TipoLicencia;
use Illuminate\Http\Request;
use App\Http\Resources\TipoLicenciaResource;

class TipoLicenciaController extends Controller
{
    public function index()
    {
        return TipoLicenciaResource::collection(TipoLicencia::all());
    }

    public function store(Request $request)
    {
        $t = TipoLicencia::create($request->only(['nombre', 'proveedor', 'descripcion', 'version']));
        return new TipoLicenciaResource($t);
    }

    public function show($id)
    {
        return new TipoLicenciaResource(TipoLicencia::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $t = TipoLicencia::findOrFail($id);
        $t->update($request->only(['nombre', 'proveedor', 'descripcion', 'version']));
        return new TipoLicenciaResource($t);
    }

    public function destroy($id)
    {
        TipoLicencia::destroy($id);
        return response()->noContent();
    }
}
