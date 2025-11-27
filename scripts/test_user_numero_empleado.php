<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Str;

// Try find a user else create one
$u = User::first();
if (! $u) {
    $u = User::create([
        'name' => 'Test User',
        'username' => 'testuser'.Str::random(4),
        'email' => 'testuser'.Str::random(6).'@example.test',
        'password' => 'secret123',
    ]);
}

echo "Before: " . var_export($u->numero_empleado, true) . "\n";

$u->numero_empleado = 'EMP-'.rand(1000,9999);
$u->save();

$u2 = User::find($u->id);
echo "After: " . var_export($u2->numero_empleado, true) . "\n";

return 0;
