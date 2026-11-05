<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$rol = RolMensual::with('departamento', 'jefe')->find(29);
echo "Rol 29: Mes {$rol->mes}/{$rol->anio} | Dept: ".($rol->departamento->nombre_departmento ?? 'null').' | Jefe: '.($rol->jefe->nombre_completo ?? 'null')."\n";

$asigs = AsignacionTurnoDiario::with(['empleado', 'turno'])
    ->where('id_rol_mensual', 29)
    ->get()
    ->groupBy('id_empleado');

foreach ($asigs as $empId => $items) {
    $empName = $items->first()->empleado->nombre_completo ?? "ID {$empId}";
    $bio = $items->first()->empleado->codigo_biometrico ?? '';
    $codes = $items->pluck('turno.codigo_turno')->unique()->implode(', ');
    $dates = $items->pluck('fecha')->sort()->values();
    $minDate = $dates->first();
    $maxDate = $dates->last();
    echo "- Emp {$empId} ({$bio} - {$empName}): Total registros = ".count($items)." | Turnos = [{$codes}] | Rango fechas = {$minDate} a {$maxDate}\n";
}
