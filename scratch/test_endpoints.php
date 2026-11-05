<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "1. Testing /api/rrhh/status...\n";
$req = Request::create('/api/rrhh/status', 'GET');
$res = $app->handle($req);
echo 'Status code: '.$res->getStatusCode()."\n";
echo 'Body: '.$res->getContent()."\n\n";

echo "2. Testing /api/departamentos/catalogo...\n";
$req = Request::create('/api/departamentos/catalogo', 'GET');
$res = $app->handle($req);
$depts = json_decode($res->getContent(), true);
echo 'Status code: '.$res->getStatusCode().' | Depts count: '.count($depts)."\n";
echo 'Sample: '.implode(', ', array_slice($depts, 0, 5))."...\n\n";

echo "3. Testing /api/login?code=ADM-001...\n";
$req = Request::create('/api/login?code=ADM-001', 'GET');
$res = $app->handle($req);
echo 'Status code: '.$res->getStatusCode()."\n";
echo 'Body: '.$res->getContent()."\n\n";

echo "4. Testing /api/empleados/all?mes=10&anio=2026...\n";
$req = Request::create('/api/empleados/all?mes=10&anio=2026', 'GET');
$res = $app->handle($req);
$all = json_decode($res->getContent(), true);
echo 'Status code: '.$res->getStatusCode().' | Total employees: '.count($all)."\n";
echo 'First employee: '.$all[0]['nombre_completo'].' (Cód: '.$all[0]['codigo_biometrico'].', Cargo: '.($all[0]['cargo']['nombre_cargo'] ?? 'N/A').")\n\n";

echo "5. Testing /api/empleados/unassigned?mes=10&anio=2026...\n";
$req = Request::create('/api/empleados/unassigned?mes=10&anio=2026', 'GET');
$res = $app->handle($req);
$unassigned = json_decode($res->getContent(), true);
echo 'Status code: '.$res->getStatusCode().' | Total unassigned: '.count($unassigned)."\n";
