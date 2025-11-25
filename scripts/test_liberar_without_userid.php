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
    echo "Test: liberar licencia SIN enviar user id (usar usuario autenticado)\n";

    $lic = Licencia::whereNull('user_id')->first();
    if (! $lic) {
        echo "No hay licencias disponibles para asignar/liberar.\n";
        exit(0);
    }

    // Use a user and assign license to them first
    $user = User::where('email', 'admin@example.com')->first() ?: User::first();
    echo "Usando usuario id={$user->id}\n";

    $lic->user_id = $user->id;
    $lic->save();
    echo "Asignada temporalmente licencia id={$lic->id} a usuario {$user->id}\n";

    $ctrl = new LicenciaController();

    // Create request without body, but set user resolver so $request->user() returns our $user
    $req = Request::create('/api/licencias/'.$lic->id.'/liberar', 'POST');
    $req->setUserResolver(function() use ($user) { return $user; });

    $resp = $ctrl->liberar($req, $lic->id);
    echo "Respuesta liberar (toResponse):\n";
    if (is_object($resp) && method_exists($resp, 'toResponse')) {
        $http = $resp->toResponse($req);
        $body = $http->getData(true);
        print_r($body);
    } else {
        var_dump($resp);
    }

    $lic2 = Licencia::find($lic->id);
    echo "Licencia user_id despues liberar: " . ($lic2->user_id ?? 'NULL') . "\n";

} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Done\n";
