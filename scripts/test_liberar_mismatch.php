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
    echo "Test: asignar licencia a usuario A y pedir liberar con usuario B (sin body)\n";

    // Find a free license and assign to user A
    $lic = Licencia::whereNull('user_id')->first();
    if (! $lic) { echo "No free license found.\n"; exit(0); }

    $users = User::all();
    if ($users->count() < 2) { echo "Need at least 2 users to test mismatch.\n"; exit(0); }

    $userA = $users->get(0);
    $userB = $users->get(1);

    echo "Assigning license id={$lic->id} to userA id={$userA->id}\n";
    $lic->user_id = $userA->id;
    $lic->save();

    $ctrl = new LicenciaController();

    // Simulate request from userB without body
    $req = Request::create('/api/licencias/'.$lic->id.'/liberar', 'POST');
    $req->setUserResolver(function() use ($userB) { return $userB; });

    $resp = $ctrl->liberar($req, $lic->id);
    echo "Response:\n";
    if (is_object($resp) && method_exists($resp, 'toResponse')) {
        $http = $resp->toResponse($req);
        $body = $http->getData(true);
        print_r($body);
        echo "Status: " . $http->getStatusCode() . "\n";
    } else {
        var_dump($resp);
    }

    // cleanup: free license
    $lic->user_id = null;
    $lic->save();

} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Done\n";
