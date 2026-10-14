<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

// What if serviceId sent was null or 'todos' or '1'?
// What if person 7 had dept_id: null, or dept_id: 3, or dept_id: 1?

foreach ([
    ['label' => 'serviceId=1, p7.dept_id=1', 'serviceId' => '1', 'p7_dept' => 1],
    ['label' => 'serviceId=1, p7.dept_id=3', 'serviceId' => '1', 'p7_dept' => 3],
    ['label' => 'serviceId=1, p7.dept_id=null', 'serviceId' => '1', 'p7_dept' => null],
    ['label' => 'serviceId=todos, p7.dept_id=1', 'serviceId' => 'todos', 'p7_dept' => 1],
    ['label' => 'serviceId=todos, p7.dept_id=3', 'serviceId' => 'todos', 'p7_dept' => 3],
    ['label' => 'serviceId=null, p7.dept_id=1', 'serviceId' => null, 'p7_dept' => 1],
    ['label' => 'serviceId=null, p7.dept_id=3', 'serviceId' => null, 'p7_dept' => 3],
] as $test) {
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
            'dept_id' => $test['p7_dept'],
            'dept_name' => $test['p7_dept'] === 3 ? 'Emergencias' : 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
    ];

    $req = new Request([
        'year' => 2026,
        'month' => 'Febrero',
        'serviceId' => $test['serviceId'],
        'people' => $people,
    ]);

    $res = $controller->saveRole($req);
    echo "{$test['label']}: Status {$res->getStatusCode()}\n";
    if ($res->getStatusCode() !== 200) {
        $body = json_decode($res->getContent(), true);
        echo '   Error: '.($body['error'] ?? 'Unknown error')."\n";
    }
}
