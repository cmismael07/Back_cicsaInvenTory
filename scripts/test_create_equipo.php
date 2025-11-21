<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\EquipoController;

$controller = new EquipoController();

$tests = [
    // camelCase keys
    [
        'tipoEquipoId' => 1,
        'ubicacionId' => 1,
        'codigo_activo' => 'EQ-TEST-' . time() . '-1',
        'marca' => 'TestCo',
        'modelo' => 'T1',
    ],
    // snake_case keys
    [
        'tipo_equipo_id' => 1,
        'ubicacion_id' => 1,
        'codigo_activo' => 'EQ-TEST-' . time() . '-2',
        'marca' => 'TestCo',
        'modelo' => 'T2',
    ],
    // nested object
    [
        'tipo_equipo' => ['id' => 1],
        'ubicacion' => ['id' => 1],
        'codigo_activo' => 'EQ-TEST-' . time() . '-3',
        'marca' => 'TestCo',
        'modelo' => 'T3',
    ],
    // no ubicacion provided (should use default)
    [
        'tipoEquipoId' => 1,
        'codigo_activo' => 'EQ-TEST-' . time() . '-4',
        'marca' => 'TestCo',
        'modelo' => 'T4',
    ],
];

foreach ($tests as $i => $payload) {
    $req = Request::create('/api/equipos', 'POST', $payload);
    try {
        $res = $controller->store($req);
        echo "Test $i: OK -> ";
        if (is_object($res) && method_exists($res, 'resolve')) {
            echo json_encode($res->resolve(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        } elseif (is_object($res) && method_exists($res, 'getContent')) {
            echo $res->getContent() . PHP_EOL;
        } else {
            echo var_export($res, true) . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "Test $i: ERROR -> " . $e->getMessage() . PHP_EOL;
    }
}
