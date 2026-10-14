<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

$req = new Request(['mes' => 2, 'anio' => 2026]);
$emps = $controller->getEmpleados($req);

echo 'Total employees returned when departamento_id is null / not passed: '.count($emps)."\n";
foreach ($emps as $e) {
    echo "ID: {$e['id']}, Name: {$e['name']}, Dept: {$e['dept_name']} (dept_id: {$e['dept_id']}), shifts[0]: {$e['shifts'][0]}\n";
    $extCount = count(array_filter($e['external_shifts']));
    echo "   external_shifts count: {$extCount}\n";
}
