<?php



use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;



require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';



$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);



$kernel->call('db:wipe --force');



echo 'Base de datos borrada correctamente';