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
        $p = Puesto::create($request->only('nombre'));
        return new PuestoResource($p);
    }

    public function show($id)
    {
        return new PuestoResource(Puesto::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $p = Puesto::findOrFail($id);
        $p->update($request->only('nombre'));
        return new PuestoResource($p);
    }

    public function destroy($id)
    {
        Puesto::destroy($id);
        return response()->noContent();
    }
}
