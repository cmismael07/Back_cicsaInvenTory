<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('config:clear');
echo "✔ config:clear ejecutado<br>";

$kernel->call('cache:clear');
echo "✔ cache:clear ejecutado<br>";

$kernel->call('optimize:clear');
echo "✔ optimize:clear ejecutado<br>";

echo "<br><strong>✔ Caché limpiada correctamente</strong>";