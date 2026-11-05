<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "--- GET EMPLEADOS SIN DEPARTAMENTO_ID ---\n";
$req = Request::create('/api/empleados?mes=10&anio=2026', 'GET');
$controller = app(RoleController::class);
$response = $controller->getEmpleados($req);

echo 'Total personas: '.count($response)."\n";
foreach ($response as $p) {
    echo "ID: {$p['id']} | Name: {$p['name']} | Dept: {$p['dept_name']} (Dept ID: {$p['dept_id']}) | Is Jefe: ".($p['is_jefe'] ? 'YES' : 'NO')."\n";
}
