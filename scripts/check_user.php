<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Comprobar si existe el usuario admin@example.com
$user = \App\Models\User::where('email', 'admin@example.com')->first();
if ($user) {
    echo "FOUND\n";
    echo json_encode($user->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "NOT_FOUND\n";
}
