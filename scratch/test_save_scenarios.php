<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

// Check what happens if we simulate saving TI with Usuario Enfermería 1 having shift M
$controller = new RoleController;

// Suppose we have people from TI:
$people = [
    [
        'id' => 2, // Jefe de Sistemas
        'name' => 'Jefe de Sistemas',
        'dept_id' => 1,
        'dept_name' => 'Tecnología de la Información',
        'shifts' => array_fill(0, 28, 'M'),
    ],
    [
        'id' => 7, // Usuario Enfermería 1
        'name' => 'Usuario Enfermería 1',
        'dept_id' => 1,
        'dept_name' => 'Tecnología de la Información',
        'shifts' => array_fill(0, 28, 'M'),
    ],
];

// Scenario 1: saving with serviceId = '1'
$req1 = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => '1',
    'people' => $people,
]);

$res1 = $controller->saveRole($req1);
echo "Scenario 1 (serviceId = '1') status: ".$res1->getStatusCode()."\n";
echo 'Content: '.$res1->getContent()."\n\n";

// Scenario 2: saving with serviceId = 'todos', and person 7 having dept_id = 1
$req2 = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => 'todos',
    'people' => $people,
]);

$res2 = $controller->saveRole($req2);
echo "Scenario 2 (serviceId = 'todos', dept_id = 1) status: ".$res2->getStatusCode()."\n";
echo 'Content: '.$res2->getContent()."\n\n";

// Scenario 3: saving with serviceId = null or 'todos', but person 7 has dept_id = 3 (Emergencias)!
$peopleWithOriginalDept = [
    [
        'id' => 2,
        'name' => 'Jefe de Sistemas',
        'dept_id' => 1,
        'dept_name' => 'Tecnología de la Información',
        'shifts' => array_fill(0, 28, 'M'),
    ],
    [
        'id' => 7,
        'name' => 'Usuario Enfermería 1',
        'dept_id' => 3, // <-- NOT UPDATED TO TI!
        'dept_name' => 'Emergencias',
        'shifts' => array_fill(0, 28, 'M'),
    ],
];

$req3 = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => 'todos',
    'people' => $peopleWithOriginalDept,
]);

$res3 = $controller->saveRole($req3);
echo "Scenario 3 (serviceId = 'todos', person dept_id = 3) status: ".$res3->getStatusCode()."\n";
echo 'Content: '.$res3->getContent()."\n\n";
