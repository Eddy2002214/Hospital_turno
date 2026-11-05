<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = app(RoleController::class);

$request = Request::create('/api/empleados', 'GET', [
    'mes' => 3,
    'anio' => 2026,
]);

$response = $controller->getEmpleados($request);
$data = is_array($response) ? $response : (method_exists($response, 'toArray') ? $response->toArray() : json_decode(json_encode($response), true));

echo 'Total personas devueltas en Marzo 2026: '.count($data)."\n";
foreach ($data as $idx => $p) {
    echo ($idx + 1).". {$p['name']} (".($p['dept_name'] ?? 'null').') - Es Jefe?: '.($p['is_jefe'] ? 'SÍ' : 'NO').' - Turnos asignados: '.count($p['shifts'])."\n";
}
