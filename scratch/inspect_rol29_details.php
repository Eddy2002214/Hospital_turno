<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

$records = AsignacionTurnoDiario::with(['empleado', 'rolMensual'])->where('id_rol_mensual', 29)->orderBy('id_asignacion')->get();
echo 'Total asignaciones en Rol 29: '.count($records)."\n";
$byEmp = $records->groupBy('id_empleado');
foreach ($byEmp as $eId => $items) {
    $eName = $items->first()->empleado->nombre_completo ?? "ID {$eId}";
    $minId = $items->min('id_asignacion');
    $maxId = $items->max('id_asignacion');
    echo "Emp {$eId} ({$eName}): ".count($items)." asignaciones (IDs {$minId} a {$maxId})\n";
}
