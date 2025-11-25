<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Http\Request;
use App\Models\Equipo;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$kernel->handle($request);

$equipoId = 6; // choose an equipo that exists
$userId = 3;   // choose a user that exists
$controller = new App\Http\Controllers\EquipoController();

// Assign equipo to user
$reqAssign = Request::create('/equipos/'.$equipoId.'/asignar', 'POST', ['usuario_id' => $userId]);
$respAssign = $controller->asignar($reqAssign, $equipoId);
echo "After assign:\n"; print_r($respAssign->toResponse($reqAssign)->getData(true));

// Send to maintenance with a motivo
$reqSend = Request::create('/equipos/'.$equipoId.'/mantenimiento', 'POST', ['descripcion' => 'Prueba fallo asignado']);
$respSend = $controller->enviarMantenimiento($equipoId, $reqSend);
echo "After enviarMantenimiento:\n"; print_r($respSend->toResponse($reqSend)->getData(true));

// Finalize without specifying nuevo_estado
$reqFinal = Request::create('/equipos/'.$equipoId.'/finalizar-mantenimiento', 'POST', ['costo' => 50]);
$respFinal = $controller->finalizarMantenimiento($equipoId, $reqFinal);
echo "After finalizarMantenimiento:\n"; print_r($respFinal->toResponse($reqFinal)->getData(true));

