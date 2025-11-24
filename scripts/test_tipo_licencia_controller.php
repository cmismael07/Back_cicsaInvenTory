<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\TipoLicenciaController;
use App\Models\TipoLicencia;
use App\Models\Licencia;

try {
    echo "Test: llamar al controlador TipoLicenciaController::store con payload stock=4\n";

    $payload = [
        'nombre' => 'CTRL-TEST-'.uniqid(),
        'version' => '2.0',
        'stock' => 4,
        'proveedor' => 'ctrl-test',
        'descripcion' => 'Creado por test via controller'
    ];
    // add fecha_vencimiento to simulate frontend sending it on creation
    $payload['fecha_vencimiento'] = date('Y-m-d', strtotime('+1 year'));

    $req = Request::create('/api/tipos-licencia', 'POST', $payload);

    $ctrl = new TipoLicenciaController();
    $resp = $ctrl->store($req);

    echo "Respuesta store:\n";
    // The controller returns a JsonResource. Convert it to HTTP response then to array.
    if (is_object($resp) && method_exists($resp, 'toResponse')) {
        $httpResp = $resp->toResponse($req);
        $body = $httpResp->getData(true);
        print_r($body);
        $tipoId = $body['data']['id'] ?? null;
    } else {
        var_dump($resp);
        $tipoId = null;
    }
    if ($tipoId) {
        $count = Licencia::where('tipo_licencia_id', $tipoId)->count();
        echo "Licencias creadas para tipo {$tipoId}: {$count}\n";
    } else {
        echo "No se obtuvo id del tipo en la respuesta.\n";
    }

    echo "Ahora probar addStock (cantidad=2)\n";
    $addPayload = ['cantidad' => 2, 'fecha_vencimiento' => date('Y-m-d', strtotime('+1 year'))];
    $req2 = Request::create('/api/tipos-licencia/' . $tipoId . '/add-stock', 'POST', $addPayload);
    $resp2 = $ctrl->addStock($req2, $tipoId);

    echo "Respuesta addStock:\n";
    if (is_object($resp2) && method_exists($resp2, 'getData')) {
        print_r($resp2->getData(true));
    } else {
        var_dump($resp2);
    }

    $count2 = Licencia::where('tipo_licencia_id', $tipoId)->count();
    echo "Licencias totales tras addStock para tipo {$tipoId}: {$count2}\n";

} catch (Throwable $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Test finished.\n";
