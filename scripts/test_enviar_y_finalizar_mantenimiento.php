<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Http\Request;
use App\Models\Equipo;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$kernel->handle($request);

$equipoId = 3; // pick an equipo that exists and may be set to maintenance
$controller = new App\Http\Controllers\EquipoController();

// Send to maintenance (create mantenimiento)
$reqSend = Request::create('/equipos/'.$equipoId.'/mantenimiento', 'POST', ['descripcion' => 'Prueba enviar a mantenimiento']);
$respSend = $controller->enviarMantenimiento($equipoId, $reqSend);
echo "Created mantenimiento:\n"; print_r($respSend->toResponse($reqSend)->getData(true));

// Now finalize WITHOUT passing mantenimiento_id
$reqFinal = Request::create('/equipos/'.$equipoId.'/finalizar-mantenimiento', 'POST', ['costo' => 150, 'nuevo_estado' => 'DISPONIBLE']);
$respFinal = $controller->finalizarMantenimiento($equipoId, $reqFinal);
echo "Finalized mantenimiento:\n"; print_r($respFinal->toResponse($reqFinal)->getData(true));

