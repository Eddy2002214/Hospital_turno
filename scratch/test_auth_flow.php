<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckRole;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "--- 1. Probar Login y Sesión ---\n";
$authCtrl = app(AuthController::class);
$loginReq = Request::create('/api/login', 'GET', ['code' => 'admin']);
$res = $authCtrl->login($loginReq);
echo 'Login status: '.$res->getStatusCode().' | Auth::check(): '.(Auth::check() ? 'TRUE' : 'FALSE')."\n";
if (Auth::check()) {
    echo 'Usuario autenticado: '.Auth::user()->nombre_usuario.' | Rol: '.Auth::user()->rol."\n";
}

echo "--- 2. Probar Middleware CheckRole:Administrador con sesión iniciada ---\n";
$middleware = new CheckRole;
$reportReq = Request::create('/reporte/imprimir', 'GET');
$passed = false;
$next = function ($request) use (&$passed) {
    $passed = true;

    return response('OK');
};

$midRes = $middleware->handle($reportReq, $next, 'Administrador');
echo 'Middleware pasó?: '.($passed ? 'SÍ (200 OK)' : 'NO (Error '.$midRes->getStatusCode().')')."\n";

echo "--- 3. Probar Middleware sin sesión ---\n";
Auth::logout();
$midResNoAuth = $middleware->handle($reportReq, $next, 'Administrador');
echo 'Redirección sin sesión hacia: '.$midResNoAuth->headers->get('Location').' (Código: '.$midResNoAuth->getStatusCode().")\n";
