<?php

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$roles = RolMensual::with('departamento')->where('anio', 2026)->where('mes', 2)->get();
echo 'Roles in Feb 2026: '.count($roles)."\n";
foreach ($roles as $r) {
    echo "Rol ID: {$r->id_rol_mensual}, Dept: ".($r->departamento ? $r->departamento->nombre_departmento : 'NULL')." (ID: {$r->id_departamento})\n";
    $asigs = AsignacionTurnoDiario::with(['empleado', 'turno'])
        ->where('id_rol_mensual', $r->id_rol_mensual)
        ->get();
    $byEmp = $asigs->groupBy('id_empleado');
    foreach ($byEmp as $empId => $empAsigs) {
        $empName = $empAsigs->first()->empleado ? $empAsigs->first()->empleado->nombre_completo : "ID $empId";
        $nonL = $empAsigs->filter(fn ($a) => $a->turno && $a->turno->codigo_turno !== 'L')->count();
        $sample = $empAsigs->take(5)->map(fn ($a) => ($a->turno ? $a->turno->codigo_turno : '?'))->implode(',');
        echo "   Emp {$empId} ({$empName}): {$empAsigs->count()} records, non-L: {$nonL}, first 5: [{$sample}]\n";
    }
}
