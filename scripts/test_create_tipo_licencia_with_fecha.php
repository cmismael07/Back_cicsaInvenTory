<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TipoLicencia;
use App\Models\Licencia;

try {
    echo "Test: crear tipo con stock=3 y fecha_vencimiento especificada\n";

    $tipo = TipoLicencia::create([
        'nombre' => 'API-FECHA-'.uniqid(),
        'proveedor' => 'script-test',
        'descripcion' => 'prueba fecha vencimiento',
        'stock' => 3,
        'fecha_vencimiento' => date('Y-m-d', strtotime('+1 year')),
    ]);

    // Simular la lógica que hace el controller (crear licencias iniciales)
    $licenciasParaCrear = [];
    for ($i = 0; $i < $tipo->stock; $i++) {
        $licenciasParaCrear[] = [
            'tipo_licencia_id' => $tipo->id,
            'clave' => 'LIC-' . strtoupper(bin2hex(random_bytes(5))) . '-' . uniqid(),
            'fecha_vencimiento' => $tipo->fecha_vencimiento,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    \App\Models\Licencia::insert($licenciasParaCrear);

    $count = Licencia::where('tipo_licencia_id', $tipo->id)->count();
    echo "Licencias creadas: {$count}\n";

    $rows = Licencia::where('tipo_licencia_id', $tipo->id)->get()->toArray();
    print_r($rows);

} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Done\n";
