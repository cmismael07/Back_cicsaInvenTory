<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

$req = Request::create('/', 'POST', [
    'email' => 'admin@example.com',
    'password' => 'password',
]);

$c = new AuthController();
$res = $c->login($req);
echo $res->getContent() . PHP_EOL;
