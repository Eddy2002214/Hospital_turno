<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$c = new RoleController;
$req = new Request(['mes' => 10, 'anio' => 2026, 'departamento_id' => 'todos']);
$res = $c->getEmpleados($req);

echo 'Total empleados devueltos para Octubre 2026 (todos): '.count($res)."\n";
foreach ($res as $e) {
    if ($e['is_jefe']) {
        echo "JEFE: {$e['name']} | Cargo: {$e['role']} | Dept Name: {$e['dept_name']}\n";
    }
}
