<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Departamento;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use App\Http\Controllers\DepartamentoController;

// Create a departamento via controller (so the automatic Ubicacion logic runs)
$controller = new DepartamentoController();
$req = Request::create('/api/departamentos', 'POST', ['nombre' => 'Dept Prueba Bodega', 'es_bodega' => true]);
$res = $controller->store($req);
$d = Departamento::latest('id')->first();
echo "Creado departamento id={$d->id} es_bodega=" . var_export($d->es_bodega, true) . "\n";

// Look for ubicacion with marker
$marker = "AUTO_BODEGA_DEPARTAMENTO_{$d->id}";
$u = Ubicacion::where('descripcion', 'like', "%{$marker}%")->first();
if ($u) {
    echo "Ubicacion creada: id={$u->id} nombre={$u->nombre}\n";
} else {
    echo "No se creó Ubicacion\n";
}

$req2 = Request::create('/api/departamentos/'.$d->id, 'PUT', ['nombre' => $d->nombre, 'es_bodega' => false]);
$res2 = $controller->update($req2, $d->id);
echo "Actualizado departamento id={$d->id} es_bodega=" . var_export(Departamento::find($d->id)->es_bodega, true) . "\n";
$u2 = Ubicacion::where('descripcion', 'like', "%{$marker}%")->first();
if ($u2) {
    echo "Ubicacion NO eliminada (id={$u2->id})\n";
} else {
    echo "Ubicacion eliminada correctamente\n";
}

return 0;
