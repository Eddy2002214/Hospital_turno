<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$emp = Empleado::with('cargo', 'departamento')->where('codigo_biometrico', '3795')->first();
if ($emp) {
    echo "ID: {$emp->id_empleado} | Bio: {$emp->codigo_biometrico} | Nombre: {$emp->nombre_completo}\n";
    echo 'Cargo base: '.($emp->cargo->nombre_cargo ?? 'null')."\n";
    echo 'Depto base ID: '.($emp->id_departamento ?? 'null').' - '.($emp->departamento->nombre_departmento ?? 'SIN AREA')."\n";

    echo "--- Asignaciones en Noviembre 2026 (mes=11, anio=2026) ---\n";
    $novRoles = RolMensual::where('mes', 11)->where('anio', 2026)->get();
    foreach ($novRoles as $nr) {
        $asigs = AsignacionTurnoDiario::where('id_rol_mensual', $nr->id_rol_mensual)->where('id_empleado', $emp->id_empleado)->get();
        echo "  Rol {$nr->id_rol_mensual} (Dept {$nr->id_departamento}): tiene ".count($asigs)." turnos\n";
    }
} else {
    echo "No se encontro 3795\n";
}
