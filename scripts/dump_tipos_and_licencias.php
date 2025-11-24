<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\TipoLicenciaController;
use App\Http\Controllers\LicenciaController;

try {
    echo "Calling TipoLicenciaController@index...\n";
    $ctrlTipos = new TipoLicenciaController();
    $req = Request::create('/api/tipos-licencia', 'GET');
    $resp = $ctrlTipos->index();
    $http = $resp->toResponse($req);
    $dataTipos = $http->getData(true);
    print_r($dataTipos);

    echo "\nCalling LicenciaController@index...\n";
    $ctrlLic = new LicenciaController();
    $req2 = Request::create('/api/licencias', 'GET');
    $resp2 = $ctrlLic->index();
    $http2 = $resp2->toResponse($req2);
    $dataLic = $http2->getData(true);
    print_r($dataLic);

} catch (Throwable $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
