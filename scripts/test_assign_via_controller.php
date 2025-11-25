<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\LicenciaController;
use App\Models\Licencia;
use App\Models\User;

try {
    echo "Buscando una licencia disponible (user_id NULL)...\n";
    $lic = Licencia::whereNull('user_id')->first();
    if (! $lic) {
        echo "No hay licencias disponibles para probar.\n";
        exit(0);
    }
    echo "Licencia encontrada id={$lic->id}, tipo_id={$lic->tipo_licencia_id}\n";

    // Use an existing user (admin@example.com)
    $user = User::where('email', 'admin@example.com')->first();
    if (! $user) {
        $user = User::first();
    }
    echo "Usando usuario id={$user->id}, email={$user->email}\n";

    $ctrl = new LicenciaController();

    // Simulate frontend sending 'usuario_id'
    $req = Request::create('/api/licencias/'.$lic->id.'/asignar', 'POST', ['usuario_id' => $user->id]);
    $resp = $ctrl->asignar($req, $lic->id);
    echo "Respuesta asignar (toResponse):\n";
    if (is_object($resp) && method_exists($resp, 'toResponse')) {
        $http = $resp->toResponse($req);
        $body = $http->getData(true);
        print_r($body);
    } else {
        var_dump($resp);
    }

    // Verify in DB
    $lic2 = Licencia::find($lic->id);
    echo "Licencia user_id ahora: " . ($lic2->user_id ?? 'NULL') . "\n";

    // Now try liberar with usuario_id
    $req2 = Request::create('/api/licencias/'.$lic->id.'/liberar', 'POST', ['usuario_id' => $user->id]);
    $resp2 = $ctrl->liberar($req2, $lic->id);
    echo "Respuesta liberar:\n";
    if (is_object($resp2) && method_exists($resp2, 'toResponse')) {
        $http2 = $resp2->toResponse($req2);
        $body2 = $http2->getData(true);
        print_r($body2);
    } else {
        var_dump($resp2);
    }

    $lic3 = Licencia::find($lic->id);
    echo "Licencia user_id despues liberar: " . ($lic3->user_id ?? 'NULL') . "\n";

} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Test done\n";
