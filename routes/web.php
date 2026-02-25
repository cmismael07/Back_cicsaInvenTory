<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->map(function ($route) {
            $uri = $route->uri();
            if (!Str::startsWith($uri, 'api/')) {
                return null;
            }

            $methods = collect($route->methods())
                ->reject(fn ($m) => $m === 'HEAD')
                ->values()
                ->all();

            $middleware = $route->gatherMiddleware();
            $authRequired = collect($middleware)->contains(function ($mw) {
                return str_contains((string) $mw, 'auth:sanctum');
            });

            return [
                'uri' => '/' . $uri,
                'methods' => $methods,
                'auth_required' => $authRequired,
                'name' => $route->getName(),
            ];
        })
        ->filter()
        ->sortBy(['auth_required', 'uri'])
        ->values();

    return view('api-index', [
        'baseUrl' => url('/api'),
        'publicRoutes' => $routes->where('auth_required', false)->values(),
        'protectedRoutes' => $routes->where('auth_required', true)->values(),
    ]);
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
