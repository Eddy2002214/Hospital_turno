<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

$sample = AsignacionTurnoDiario::with(['empleado', 'rolMensual.departamento'])->whereBetween('id_asignacion', [3560, 3740])->get();
$byEmp = $sample->groupBy('id_empleado');
foreach ($byEmp as $eId => $items) {
    $empName = $items->first()->empleado->nombre_completo ?? "ID {$eId}";
    $rol = $items->first()->rolMensual;
    $dept = $rol->departamento->nombre_departmento ?? 'null';
    $minId = $items->min('id_asignacion');
    $maxId = $items->max('id_asignacion');
    echo "Emp {$eId} ({$empName}): Rol {$rol->id_rol_mensual} (Dept: {$dept}) - IDs: {$minId} a {$maxId} - Count: ".count($items)."\n";
}
