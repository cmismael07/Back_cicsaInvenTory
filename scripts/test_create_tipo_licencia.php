<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TipoLicencia;
use App\Models\Licencia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Simple test: create a tipo with stock and insert licencias
try {
    echo "Starting test: create TipoLicencia with stock=5\n";

    DB::beginTransaction();

    $tipo = TipoLicencia::create([
        'nombre' => 'TEST-TIPO-'.uniqid(),
        'version' => '1.0',
        'stock' => 5,
        'proveedor' => 'script-test',
        'descripcion' => 'created by test script'
    ]);

    $licenciasParaCrear = [];
    for ($i = 0; $i < $tipo->stock; $i++) {
        $licenciasParaCrear[] = [
            'tipo_licencia_id' => $tipo->id,
            'clave' => 'LIC-' . strtoupper(Str::random(10)) . '-' . uniqid(),
            'fecha_vencimiento' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    echo "Inserting " . count($licenciasParaCrear) . " licencias...\n";
    Licencia::insert($licenciasParaCrear);

    DB::commit();

    $total = Licencia::where('tipo_licencia_id', $tipo->id)->count();
    echo "Total licencias for tipo {$tipo->id}: {$total}\n";

} catch (Throwable $e) {
    DB::rollBack();
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Test finished.\n";
