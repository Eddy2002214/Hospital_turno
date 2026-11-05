<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$rolMensual = RolMensual::where('mes', 11)->where('anio', 2026)->where('id_departamento', 8)->first();
$deptIdNum = 8;
$empsInThisDept = [35, 680, 1459, 482];

echo "Rol id: {$rolMensual->id_rol_mensual}\n";
echo "Rol jefe: {$rolMensual->id_jefe}\n";
echo 'empsInThisDept: '.implode(',', $empsInThisDept)."\n";

$queryRemoved = AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual);
if (! empty($empsInThisDept)) {
    $queryRemoved->whereNotIn('id_empleado', $empsInThisDept);
}
if ($rolMensual->id_jefe) {
    $queryRemoved->where('id_empleado', '!=', $rolMensual->id_jefe);
}

echo 'SQL: '.$queryRemoved->toSql()."\n";
echo 'Bindings: '.json_encode($queryRemoved->getBindings())."\n";
$matchingToDelete = $queryRemoved->get();
echo 'Count matching to delete: '.$matchingToDelete->count()."\n";
foreach ($matchingToDelete as $m) {
    echo "  - To delete: id_asignacion={$m->id_asignacion}, id_empleado={$m->id_empleado}\n";
}
