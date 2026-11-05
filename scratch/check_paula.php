<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$emp = Empleado::with('cargo', 'departamento')->where('nombre_completo', 'LIKE', '%PAULA SOLIZ%')->first();
if ($emp) {
    echo "ID: {$emp->id_empleado} | Bio: {$emp->codigo_biometrico} | Nombre: {$emp->nombre_completo}\n";
    echo 'Cargo base: '.($emp->cargo->nombre_cargo ?? 'null')."\n";
    echo 'Depto base: '.($emp->departamento->nombre_departmento ?? 'SIN ÁREA (DISPONIBLE)')."\n";

    echo "--- Asignaciones en roles mensuales ---\n";
    $asigs = AsignacionTurnoDiario::with(['rolMensual.departamento'])->where('id_empleado', $emp->id_empleado)->get();
    foreach ($asigs->groupBy('id_rol_mensual') as $rolId => $items) {
        $rol = RolMensual::with('departamento')->find($rolId);
        echo "Rol {$rolId}: Mes {$rol->mes}/{$rol->anio} - Dept: ".($rol->departamento->nombre_departmento ?? 'null').' - Total turnos: '.count($items).' - Es jefe?: '.($rol->id_jefe == $emp->id_empleado ? 'SÍ' : 'NO')."\n";
    }
} else {
    echo "Empleado no encontrado\n";
}
