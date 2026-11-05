<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

echo "--- ROLES MENSUALES OCTUBRE 2026 ---\n";
$roles = RolMensual::with('departamento')->where('mes', 10)->where('anio', 2026)->get();
foreach ($roles as $r) {
    echo "Rol ID: {$r->id_rol_mensual} | Dept ID: {$r->id_departamento} | Dept: ".($r->departamento?->nombre_departmento)."\n";
    $asigs = AsignacionTurnoDiario::where('id_rol_mensual', $r->id_rol_mensual)->get();
    $empIds = $asigs->pluck('id_empleado')->unique();
    echo '  Empleados asignados en este Rol ('.$empIds->count().' empleados, '.$asigs->count()." turnos):\n";
    foreach ($empIds as $eId) {
        $emp = Empleado::with(['cargo', 'departamento'])->find($eId);
        echo "    Emp ID: $eId | {$emp?->nombre_completo} | Cargo: {$emp?->cargo?->nombre_cargo} | Dept Base: {$emp?->departamento?->nombre_departmento}\n";
    }
}
