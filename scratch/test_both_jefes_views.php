<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

// 1. Jefe of Emergencias queries Feb 2026
$req3 = new Request(['mes' => 2, 'anio' => 2026, 'departamento_id' => 3]);
$staff3 = $controller->getEmpleados($req3);
$emp7In3 = collect($staff3)->firstWhere('id', 7);
echo "Jefe of Emergencias view of Emp 7:\n";
echo '  Present: '.($emp7In3 ? 'YES' : 'NO')."\n";
if ($emp7In3) {
    echo "  Dept Name: {$emp7In3['dept_name']}, shift[0]: {$emp7In3['shifts'][0]}\n";
    $extCount = count(array_filter($emp7In3['external_shifts']));
    echo "  external_shifts count: {$extCount}\n";
}

// 2. Jefe of TI queries Feb 2026
$req1 = new Request(['mes' => 2, 'anio' => 2026, 'departamento_id' => 1]);
$staff1 = $controller->getEmpleados($req1);
$emp7In1 = collect($staff1)->firstWhere('id', 7);
echo "\nJefe of TI view of Emp 7:\n";
echo '  Present: '.($emp7In1 ? 'YES' : 'NO')."\n";
if ($emp7In1) {
    echo "  Dept Name: {$emp7In1['dept_name']}, shift[0]: {$emp7In1['shifts'][0]}\n";
    $extCount = count(array_filter($emp7In1['external_shifts']));
    echo "  external_shifts count: {$extCount}\n";
    $sample = array_values(array_filter($emp7In1['external_shifts']))[0] ?? null;
    if ($sample) {
        echo "  sample ext: dept={$sample['dept']}, code={$sample['code']}\n";
    }
}
