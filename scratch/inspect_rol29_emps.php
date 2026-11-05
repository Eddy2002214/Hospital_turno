<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$rol = RolMensual::where('mes', 11)->where('anio', 2026)->where('id_departamento', 8)->first();
echo "Rol 11/2026 Dept 8: id_rol_mensual = {$rol->id_rol_mensual}, id_jefe = {$rol->id_jefe}\n";

$asigs = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)->get();
echo "Total asignaciones en rol {$rol->id_rol_mensual}: ".$asigs->count()."\n";
$byEmp = $asigs->groupBy('id_empleado');
foreach ($byEmp as $eId => $list) {
    echo "  - Emp $eId: ".$list->count()." registros\n";
}
