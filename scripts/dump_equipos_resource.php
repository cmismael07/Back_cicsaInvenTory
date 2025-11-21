<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Equipo;
use App\Http\Resources\EquipoResource;

$items = Equipo::with(['tipo_equipo','ubicacion','responsable'])->take(5)->get();
echo json_encode(EquipoResource::collection($items)->resolve(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
