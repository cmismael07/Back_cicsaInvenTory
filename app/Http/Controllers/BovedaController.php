<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Models\BovedaEntrada;

class BovedaController extends Controller
{
    public function index()
    {
        $rows = BovedaEntrada::orderByDesc('updated_at')->get();
        $payload = $rows->map(function ($r) {
            $password = $r->password_hash;
            try {
                $password = $password ? Crypt::decryptString($password) : $password;
            } catch (\Throwable $e) {
                // fallback: return stored string as-is
            }
            return [
                'id' => $r->id,
                'servicio' => $r->servicio,
                'usuario' => $r->usuario,
                'password_hash' => $password,
                'url' => $r->url,
                'categoria' => $r->categoria,
                'notas' => $r->notas,
                'fecha_actualizacion' => optional($r->updated_at)->toDateString(),
            ];
        });

        return response()->json($payload->values());
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'servicio' => 'required|string',
            'usuario' => 'required|string',
            'password_hash' => 'required|string',
            'url' => 'nullable|string',
            'categoria' => 'required|string',
            'notas' => 'nullable|string',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        $data = $v->validated();
        $data['password_hash'] = Crypt::encryptString($data['password_hash']);

        $row = BovedaEntrada::create($data);

        return response()->json([
            'id' => $row->id,
            'servicio' => $row->servicio,
            'usuario' => $row->usuario,
            'password_hash' => $request->input('password_hash'),
            'url' => $row->url,
            'categoria' => $row->categoria,
            'notas' => $row->notas,
            'fecha_actualizacion' => optional($row->updated_at)->toDateString(),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $row = BovedaEntrada::findOrFail($id);
        $v = Validator::make($request->all(), [
            'servicio' => 'sometimes|required|string',
            'usuario' => 'sometimes|required|string',
            'password_hash' => 'sometimes|required|string',
            'url' => 'nullable|string',
            'categoria' => 'sometimes|required|string',
            'notas' => 'nullable|string',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        $data = $v->validated();
        if (array_key_exists('password_hash', $data)) {
            $data['password_hash'] = Crypt::encryptString($data['password_hash']);
        }

        $row->update($data);

        $password = null;
        try {
            $password = $row->password_hash ? Crypt::decryptString($row->password_hash) : null;
        } catch (\Throwable $e) {
            $password = $row->password_hash;
        }

        return response()->json([
            'id' => $row->id,
            'servicio' => $row->servicio,
            'usuario' => $row->usuario,
            'password_hash' => $password,
            'url' => $row->url,
            'categoria' => $row->categoria,
            'notas' => $row->notas,
            'fecha_actualizacion' => optional($row->updated_at)->toDateString(),
        ], 200);
    }

    public function destroy($id)
    {
        BovedaEntrada::where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
