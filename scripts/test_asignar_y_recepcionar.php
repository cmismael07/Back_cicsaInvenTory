<?php
// Simple script: asigna equipo 2 a usuario 3 y luego lo recepciona, mostrando antes/despues
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;
use App\Models\Equipo;
use App\Models\User;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot framework
$request = Request::capture();
$kernel->handle($request);

$equipo = Equipo::find(2);
echo "Before: equipo id={$equipo->id}, responsable_id={$equipo->responsable_id}, estado={$equipo->estado}\n";

// Simulate controller calls
$controller = new App\Http\Controllers\EquipoController();
// Assign
$req = Request::create('/equipos/2/asignar', 'POST', ['usuario_id' => 3]);
$response = $controller->asignar($req, 2);
echo "After assign: \n"; print_r($response->toResponse($req)->getData(true));

// Recepcionar
$req2 = Request::create('/equipos/2/recepcionar', 'POST');
$response2 = $controller->recepcionar(2);
echo "After recepcionar: \n"; print_r($response2->toResponse($req2)->getData(true));


