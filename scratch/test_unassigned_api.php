<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$request = Request::create('/api/empleados/unassigned', 'GET', ['mes' => 10, 'anio' => 2026]);
$controller = new RoleController;
$res = $controller->getUnassigned($request);

echo 'Total devueltos: '.count($res)."\n";
foreach ($res as $r) {
    if (stripos($r['name'], 'LEIDY') !== false || stripos($r['dept'], 'LAVAN') !== false || stripos($r['base_dept_name'], 'LAVAN') !== false || stripos($r['name'], 'BETANCUR') !== false) {
        echo "✔ Funcionario: {$r['name']} ({$r['bio']})\n";
        echo "  - dept: {$r['dept']}\n";
        echo "  - dept_id: {$r['dept_id']}\n";
        echo "  - base_dept_name: {$r['base_dept_name']}\n";
        echo "  - base_dept_id: {$r['base_dept_id']}\n";
        echo '  - assigned_dept_ids: '.json_encode($r['assigned_dept_ids'])."\n";
        echo '  - assigned_dept_names: '.json_encode($r['assigned_dept_names'])."\n";
    }
}
