<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the framework
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Departamento;

// Try to find by nombre 'Sistemas' or first
$d = Departamento::where('nombre', 'Sistemas')->first();
if (! $d) {
    $d = Departamento::first();
    if (! $d) {
        echo "No hay departamentos en la BD. Crea uno primero.\n";
        exit(1);
    }
}

echo "Antes: id={$d->id} nombre={$d->nombre} es_bodega=" . var_export($d->es_bodega, true) . "\n";

$d->es_bodega = true;
$d->save();

$d2 = Departamento::find($d->id);

echo "Después: id={$d2->id} nombre={$d2->nombre} es_bodega=" . var_export($d2->es_bodega, true) . "\n";

return 0;
