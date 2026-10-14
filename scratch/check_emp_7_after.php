<?php

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$asigs = AsignacionTurnoDiario::where('id_empleado', 7)
    ->whereBetween('fecha', ['2026-02-01', '2026-02-28'])
    ->get();
echo 'Assignments for Emp 7 in Feb 2026: '.$asigs->count()."\n";
foreach ($asigs->groupBy('id_rol_mensual') as $rolId => $items) {
    $rol = RolMensual::find($rolId);
    echo "  Rol ID {$rolId} (Dept {$rol->id_departamento}): {$items->count()} assignments, shift: ".$items->first()->id_turno."\n";
}
