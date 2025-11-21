<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Resources\UserResource;

$users = User::with(['departamento','puesto'])->take(5)->get();
$arr = UserResource::collection($users)->resolve();
echo json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
