<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('migrate:fresh --force');

DB::table('users')->insertOrIgnore([
    'name'       => 'Admin',
    'username'   => 'admin',
    'email'      => 'admin@example.com',
    'password'   => Hash::make('password'), // password = "password"
    'rol'        => 'Administrador',
    'activo'     => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo 'Migraciones ejecutadas y usuario admin creado';