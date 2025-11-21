<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Http\Request;
/**
 * Ruta named `login` para evitar excepciones cuando middleware intenta redirigir
 * desde peticiones no autenticadas (útil para pruebas desde navegador).
 */
Route::get('/login', function (Request $request) {
    if ($request->wantsJson()) {
        return response()->json(['message' => 'Unauthenticated. Use API login.'], 401);
    }
    // Para accesos desde navegador, devolver una página simple
    return response('<h1>Login</h1><p>Por favor inicie sesión vía la API.</p>', 401);
})->name('login');

// Nota: la ruta de desarrollo para emitir tokens se ha removido.
// Usa comandos artisan o gestion segura para crear tokens en entornos locales.

// Nota: No cargamos manualmente `routes/api.php` aquí porque
// Laravel ya lo registra automáticamente con el middleware `api`.
// Mantener una sola carga evita que las rutas `api` queden bajo
// el middleware `web` y provoquen errores CSRF (HTTP 419).
