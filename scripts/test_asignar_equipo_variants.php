<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\EquipoController;
use App\Models\Equipo;
use App\Models\User;

try {
    $ctrl = new EquipoController();

    $equipo = Equipo::first();
    if (! $equipo) { echo "No equipos in DB\n"; exit(0); }
    $users = User::take(2)->get();
    if ($users->count() < 1) { echo "No users in DB\n"; exit(0); }

    $user = $users->first();
    echo "Equipo id={$equipo->id}, current responsable_id={$equipo->responsable_id}\n";

    $variants = [
        ['responsable_id' => $user->id],
        ['responsable' => $user->id],
        ['responsableId' => $user->id],
        ['usuario_id' => $user->id],
        ['user_id' => $user->id],
        ['responsable' => ['id' => $user->id]],
    ];

    foreach ($variants as $i => $payload) {
        echo "\n-- Test variant {$i} payload=" . json_encode($payload) . "\n";
        $req = Request::create('/api/equipos/'.$equipo->id.'/asignar', 'POST', $payload);
        $resp = $ctrl->asignar($req, $equipo->id);
        if (is_object($resp) && method_exists($resp, 'toResponse')) {
            $http = $resp->toResponse($req);
            $body = $http->getData(true);
            print_r($body);
        } else {
            var_dump($resp);
        }

        $equipo = Equipo::find($equipo->id);
        echo "After assign responsable_id={$equipo->responsable_id}\n";
    }

} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "Done\n";
