<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

// Scenario A: called with departamento_id = 1
$reqA = new Request(['mes' => 2, 'anio' => 2026, 'departamento_id' => 1]);
$allA = $controller->getAllEmpleados($reqA);
$emp7A = collect($allA)->firstWhere('id_empleado', 7);
$ext7A = count(array_filter($emp7A['external_shifts'] ?? []));
echo "Scenario A (departamento_id = 1): Emp 7 external_shifts count = {$ext7A}\n";

// Scenario B: called without departamento_id (e.g. Admin or not passed)
$reqB = new Request(['mes' => 2, 'anio' => 2026]);
$allB = $controller->getAllEmpleados($reqB);
$emp7B = collect($allB)->firstWhere('id_empleado', 7);
$ext7B = count(array_filter($emp7B['external_shifts'] ?? []));
echo "Scenario B (departamento_id null): Emp 7 external_shifts count = {$ext7B}\n";

// Scenario C: called with departamento_id = 'todos'
$reqC = new Request(['mes' => 2, 'anio' => 2026, 'departamento_id' => 'todos']);
$allC = $controller->getAllEmpleados($reqC);
$emp7C = collect($allC)->firstWhere('id_empleado', 7);
$ext7C = count(array_filter($emp7C['external_shifts'] ?? []));
echo "Scenario C (departamento_id = 'todos'): Emp 7 external_shifts count = {$ext7C}\n";
