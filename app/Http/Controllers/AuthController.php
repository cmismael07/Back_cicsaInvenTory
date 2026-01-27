<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Accept either `email` or `correo` from frontend
        $data = $request->only(['email','correo','password']);
        $request->validate([
            'password' => 'required',
        ]);

        $email = $data['email'] ?? $data['correo'] ?? null;
        if (! $email) {
            return response()->json(['message' => 'email or correo is required'], 422);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // If using Sanctum, return a personal access token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request)
    {
        // Support multiple frontend payloads: either
        // { current_password, new_password, new_password_confirmation }
        // or { old, new, confirm }
        $user = $request->user();

        $current = $request->input('current_password') ?? $request->input('old');
        $new = $request->input('new_password') ?? $request->input('new');
        $confirm = $request->input('new_password_confirmation') ?? $request->input('confirm');

        if (! $current || ! $new || ! $confirm) {
            return response()->json(['message' => 'Se requieren campos current_password/old y new/confirm'], 422);
        }

        if (strlen($new) < 6) {
            return response()->json(['message' => 'La nueva contraseña debe tener al menos 6 caracteres'], 422);
        }

        if ($new !== $confirm) {
            return response()->json(['message' => 'La confirmación de la nueva contraseña no coincide'], 422);
        }

        if (! Hash::check($current, $user->password)) {
            return response()->json(['message' => 'Contraseña actual incorrecta'], 422);
        }

        $user->password = Hash::make($new);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada']);
    }
}
