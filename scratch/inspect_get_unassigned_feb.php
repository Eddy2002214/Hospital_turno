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
$staff = $controller->getUnassigned($req);

echo 'Total employees in getUnassigned for Feb 2026: '.count($staff)."\n";
foreach ($staff as $e) {
    echo "ID: {$e['id']}, Name: {$e['name']}\n";
    echo '  base_dept_id: '.($e['base_dept_id'] ?? 'null')." ({$e['base_dept_name']})\n";
    echo '  assigned_dept_ids: '.json_encode($e['assigned_dept_ids'])."\n";
    echo '  assigned_dept_names: '.json_encode($e['assigned_dept_names'])."\n";
    echo "  dept: {$e['dept']} (dept_id: ".($e['dept_id'] ?? 'null').")\n\n";
}
