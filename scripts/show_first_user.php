<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::first();
if (! $u) {
    echo "NO_USERS\n";
    exit(0);
}

echo json_encode([
    'id' => $u->id,
    'name' => $u->name,
    'username' => $u->username,
    'email' => $u->email,
    'rol' => $u->rol,
    'activo' => (bool) $u->activo,
    'departamento_id' => $u->departamento_id,
    'puesto_id' => $u->puesto_id,
], JSON_PRETTY_PRINT) . PHP_EOL;
