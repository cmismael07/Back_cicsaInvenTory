<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function index()
    {
        return UserResource::collection(User::with(['departamento', 'puesto'])->paginate(15));
    }

    public function store(Request $request)
    {
        // Aceptamos claves provenientes del frontend (`correo`, `nombre_usuario`, `nombres`/`apellidos`)
        $data = $request->only(['name','nombres','apellidos','nombre_usuario','username','email','correo','password','departamento_id','puesto_id','rol','activo']);

        // Normalizar email
        $email = $data['email'] ?? $data['correo'] ?? null;

        // Normalizar username
        $username = $data['username'] ?? $data['nombre_usuario'] ?? null;

        // Normalizar nombre completo
        $name = $data['name'] ?? null;
        if (empty($name) && (!empty($data['nombres']) || !empty($data['apellidos']))) {
            $name = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidos'] ?? ''));
        }
        if (empty($name) && !empty($username)) {
            $name = $username; // fallback
        }
        if (empty($name) && !empty($email)) {
            $name = strstr($email, '@', true) ?: $email;
        }

        $payload = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $data['password'] ?? null,
            'departamento_id' => $data['departamento_id'] ?? null,
            'puesto_id' => $data['puesto_id'] ?? null,
            'rol' => $data['rol'] ?? null,
            'activo' => array_key_exists('activo', $data) ? (bool) $data['activo'] : true,
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($payload, [
            'name' => 'required|string',
            'username' => 'nullable|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'puesto_id' => 'nullable|integer|exists:puestos,id',
            'rol' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $validator->validate();

        $payload['password'] = Hash::make($payload['password']);

        $u = User::create($payload);
        return new UserResource($u);
    }

    public function show($id)
    {
        return new UserResource(User::with(['departamento','puesto'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $u = User::findOrFail($id);
        $data = $request->only(['name','nombres','apellidos','nombre_usuario','username','email','correo','password','departamento_id','puesto_id','rol','activo']);

        $email = $data['email'] ?? $data['correo'] ?? $u->email;
        $username = $data['username'] ?? $data['nombre_usuario'] ?? $u->username;

        $name = $data['name'] ?? $u->name;
        if ((!empty($data['nombres']) || !empty($data['apellidos']))) {
            $name = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidos'] ?? ''));
        }

        $payload = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'departamento_id' => $data['departamento_id'] ?? $u->departamento_id,
            'puesto_id' => $data['puesto_id'] ?? $u->puesto_id,
            'rol' => $data['rol'] ?? $u->rol,
            'activo' => array_key_exists('activo', $data) ? (bool) $data['activo'] : $u->activo,
        ];

        $rules = [
            'name' => 'sometimes|required|string',
            'username' => "nullable|string|unique:users,username,{$id}",
            'email' => "sometimes|required|email|unique:users,email,{$id}",
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'puesto_id' => 'nullable|integer|exists:puestos,id',
            'rol' => 'nullable|string',
            'activo' => 'boolean',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($payload, $rules);
        $validator->validate();

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $u->update($payload);
        return new UserResource($u);
    }

    public function destroy($id)
    {
        User::destroy($id);
        return response()->noContent();
    }
}
