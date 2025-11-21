<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\EquipoController;

$controller = new EquipoController();

// Ensure there's at least one equipo
$e = \App\Models\Equipo::first();
if (! $e) {
    echo "NO_EQUIPO\n";
    exit(0);
}

$req = Request::create('/api/equipos/'.$e->id.'/asignar', 'POST', []);
try {
    $res = $controller->asignar($e->id, $req);
    if (is_object($res) && method_exists($res, 'resolve')) {
        echo json_encode($res->resolve(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    } else {
        echo var_export($res, true) . PHP_EOL;
    }
} catch (Exception $ex) {
    echo "ERROR: " . $ex->getMessage() . PHP_EOL;
}
