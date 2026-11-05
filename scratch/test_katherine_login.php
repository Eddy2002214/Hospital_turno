<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\AuthController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "--- LOGIN CON BIOMETRICO DE KATHERINE ---\n";
$req = Request::create('/api/login?code='.urlencode('3997 CEK'), 'GET');
$controller = app(AuthController::class);
$res = $controller->login($req);
echo 'Status: '.$res->getStatusCode()."\n";
echo 'Response: '.$res->getContent()."\n";
