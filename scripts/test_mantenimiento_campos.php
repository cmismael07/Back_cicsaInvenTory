<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Http\Request;
use App\Models\Equipo;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$kernel->handle($request);

$equipoId = 4;
$controller = new App\Http\Controllers\EquipoController();

// Create mantenimiento with 'motivo' and 'proveedor' and 'tipo'
$reqCreate = Request::create('/equipos/'.$equipoId.'/mantenimiento', 'POST', ['motivo' => 'Pantalla no enciende', 'proveedor' => 'TechRepair', 'tipo_mantenimiento' => 'Correctivo']);
$respCreate = $controller->enviarMantenimiento($equipoId, $reqCreate);
echo "Created maintenance:\n"; print_r($respCreate->toResponse($reqCreate)->getData(true));

// Finalize and include descripcion_final and proveedor
$reqFinalize = Request::create('/equipos/'.$equipoId.'/finalizar-mantenimiento', 'POST', ['costo' => 200, 'descripcion_final' => 'Reemplazo de placa', 'proveedor' => 'TechRepair']);
$respFinalize = $controller->finalizarMantenimiento($equipoId, $reqFinalize);

echo "Finalized maintenance:\n"; print_r($respFinalize->toResponse($reqFinalize)->getData(true));
