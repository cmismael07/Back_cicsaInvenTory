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
        $d = Departamento::create($request->only('nombre'));
        return new DepartamentoResource($d);
    }

    public function show($id)
    {
        return new DepartamentoResource(Departamento::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $d = Departamento::findOrFail($id);
        $d->update($request->only('nombre'));
        return new DepartamentoResource($d);
    }

    public function destroy($id)
    {
        Departamento::destroy($id);
        return response()->noContent();
    }
}
