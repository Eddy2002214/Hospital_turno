<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$ctrl = app(RoleController::class);
$req = Request::create('/api/empleados', 'GET', [
    'mes' => 11,
    'anio' => 2026,
    'departamento_id' => 8,
]);
$res = $ctrl->getEmpleados($req);
$data = is_array($res) ? $res : (method_exists($res, 'toArray') ? $res->toArray() : json_decode(json_encode($res), true));

echo 'Total personas en getEmpleados (Nov 2026, Dept 8): '.count($data)."\n";
foreach ($data as $p) {
    echo "ID: {$p['id']} | Bio: {$p['bio']} | Nombre: {$p['name']} | Dept: ".($p['dept_name'] ?? 'null').' (ID: '.($p['dept_id'] ?? 'null').') | Shifts: '.implode('', array_slice($p['shifts'], 0, 5))."...\n";
}
