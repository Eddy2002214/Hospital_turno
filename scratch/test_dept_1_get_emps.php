<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

$req = new Request(['mes' => 2, 'anio' => 2026, 'departamento_id' => 1]);
$emps = $controller->getEmpleados($req);

echo 'Total employees returned for dept 1 (TI): '.count($emps)."\n";
foreach ($emps as $e) {
    echo "ID: {$e['id']}, Name: {$e['name']}, Dept: {$e['dept_name']} (dept_id: {$e['dept_id']})\n";
    $extCount = count(array_filter($e['external_shifts']));
    echo "   external_shifts count: {$extCount}\n";
    if ($extCount > 0) {
        $first = array_values(array_filter($e['external_shifts']))[0];
        echo "   sample external shift: dept={$first['dept']}, code={$first['code']}\n";
    }
}
