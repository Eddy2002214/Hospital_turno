<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

$emp7 = Empleado::find(7);
echo "Emp 7 base id_departamento: {$emp7->id_departamento}\n";

$startDate = '2026-02-01';
$endDate = '2026-02-28';

$asigs = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno'])
    ->whereBetween('fecha', [$startDate, $endDate])
    ->where('id_empleado', 7)
    ->get();

echo 'Total assignments in Feb 2026 for Emp 7: '.$asigs->count()."\n";
foreach ($asigs as $a) {
    $rol = $a->rolMensual;
    $deptName = $rol && $rol->departamento ? $rol->departamento->nombre_departmento : 'None';
    $code = $a->turno ? $a->turno->codigo_turno : 'None';
    echo "  Asig ID: {$a->id_asignacion}, Rol ID: {$a->id_rol_mensual}, Dept: {$deptName} (ID: {$rol->id_departamento}), Date: {$a->fecha}, Turno: {$code}\n";
    if ($a->id_asignacion > 10) {
        echo "  ... (truncated)\n";
        break;
    }
}
