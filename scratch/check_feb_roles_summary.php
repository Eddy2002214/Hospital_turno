<?php

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$roles = RolMensual::with('departamento')->where('anio', 2026)->where('mes', 2)->get();
echo "Roles in Feb 2026:\n";
foreach ($roles as $r) {
    $deptName = $r->departamento ? $r->departamento->nombre_departmento : 'None';
    $emps = AsignacionTurnoDiario::where('id_rol_mensual', $r->id_rol_mensual)->pluck('id_empleado')->unique();
    echo "  Rol {$r->id_rol_mensual} - {$deptName} (Dept {$r->id_departamento}): {$emps->count()} emps: [".$emps->implode(',')."]\n";
}
