<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Http\Request;
use App\Models\Mantenimiento;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$kernel->handle($request);

$equipoId = 2;
$controller = new App\Http\Controllers\EquipoController();

// Create mantenimiento WITHOUT tipo
$reqCreate = Request::create('/equipos/'.$equipoId.'/mantenimiento', 'POST', ['motivo' => 'Sin tipo inicialmente']);
$respCreate = $controller->enviarMantenimiento($equipoId, $reqCreate);
echo "Created maintenance (no tipo):\n"; print_r($respCreate->toResponse($reqCreate)->getData(true));

// Finalize passing tipo only
$reqFinalize = Request::create('/equipos/'.$equipoId.'/finalizar-mantenimiento', 'POST', ['costo' => 75, 'tipo' => 'Preventivo']);
$respFinalize = $controller->finalizarMantenimiento($equipoId, $reqFinalize);
echo "Finalized maintenance with tipo:\n"; print_r($respFinalize->toResponse($reqFinalize)->getData(true));

$m = Mantenimiento::where('equipo_id', $equipoId)->orderByDesc('id')->first();
if ($m) {
    echo "DB Row after finalize: id={$m->id} tipo={$m->tipo} proveedor={$m->proveedor} descripcion={$m->descripcion}\n";
} else {
    echo "No maintenance found for equipo {$equipoId}\n";
}
