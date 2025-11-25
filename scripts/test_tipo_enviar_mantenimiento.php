<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Http\Request;
use App\Models\Mantenimiento;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$kernel->handle($request);

$equipoId = 5;
$controller = new App\Http\Controllers\EquipoController();

$reqSend = Request::create('/equipos/'.$equipoId.'/mantenimiento', 'POST', ['motivo' => 'Prueba tipo', 'tipo' => 'Preventivo', 'proveedor' => 'ProveedorX']);
$respSend = $controller->enviarMantenimiento($equipoId, $reqSend);
echo "Response from enviarMantenimiento:\n"; print_r($respSend->toResponse($reqSend)->getData(true));

$m = Mantenimiento::where('equipo_id', $equipoId)->orderByDesc('id')->first();
if ($m) {
    echo "DB Row: id={$m->id} tipo={$m->tipo} proveedor={$m->proveedor} descripcion={$m->descripcion}\n";
} else {
    echo "No maintenance found for equipo {$equipoId}\n";
}
