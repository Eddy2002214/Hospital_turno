<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$serviceId = '8';
$people = [
    ['id' => 35, 'dept_id' => 8, 'ff' => 'TGN', 'is_jefe' => true, 'shifts' => array_fill(0, 30, 'L')],
    ['id' => 680, 'dept_id' => 8, 'ff' => 'GOB', 'is_jefe' => false, 'shifts' => array_fill(0, 30, 'M')],
    ['id' => 1459, 'dept_id' => 8, 'ff' => 'IDH/HIPC', 'is_jefe' => false, 'shifts' => array_fill(0, 30, 'M')],
    ['id' => 482, 'dept_id' => 8, 'ff' => 'RP', 'is_jefe' => false, 'shifts' => array_fill(0, 30, 'M*')],
];

// Let's see what $rolMensuales has:
$rolMensuales = [];
foreach ($people as $person) {
    if ($serviceId && $serviceId !== 'todos') {
        $deptId = (int) $serviceId;
    } else {
        $deptId = (int) ($person['dept_id'] ?? 8);
    }
    $deptKey = $deptId ?? 'null';
    if (! isset($rolMensuales[$deptKey])) {
        $rolMensual = RolMensual::where('mes', 11)->where('anio', 2026)->where('id_departamento', $deptId)->first();
        $rolMensuales[$deptKey] = $rolMensual;
    }
}

echo 'rolMensuales keys: '.json_encode(array_keys($rolMensuales))."\n";
foreach ($rolMensuales as $deptKey => $rolMensual) {
    echo 'Processing deptKey: type='.gettype($deptKey).', val='.var_export($deptKey, true)."\n";
    $deptIdNum = (int) $deptKey;
    echo "deptIdNum: $deptIdNum\n";

    // Simulate inserts
    $inserts = [];
    foreach ($people as $p) {
        $inserts[] = ['dept_id' => 8, 'id_empleado' => $p['id']];
    }

    $empsInThisDept = collect($inserts)->where('dept_id', $deptIdNum)->pluck('id_empleado')->unique()->toArray();
    echo 'empsInThisDept: '.json_encode($empsInThisDept)."\n";

    $queryRemoved = AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual);
    if (! empty($empsInThisDept)) {
        $queryRemoved->whereNotIn('id_empleado', $empsInThisDept);
    }
    if ($rolMensual->id_jefe) {
        $queryRemoved->where('id_empleado', '!=', $rolMensual->id_jefe);
    }

    echo 'queryRemoved count: '.$queryRemoved->count()."\n";
    $del = $queryRemoved->delete();
    echo "queryRemoved deleted: $del\n";
}
