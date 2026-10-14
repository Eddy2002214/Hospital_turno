<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Contracts\Console\Kernel;

$deptos = DepartamentoServicio::all();
$turnos = Turno::all()->keyBy('codigo_turno');
$shiftTypes = ['M', 'M*', 'T', 'N', 'L'];

AsignacionTurnoDiario::query()->delete();
RolMensual::query()->delete();

foreach ($deptos as $depto) {
    $rol = RolMensual::firstOrCreate(['mes' => 10, 'anio' => 2026, 'id_departamento' => $depto->id_departamento]);
    $empleados = Empleado::where('id_departamento', $depto->id_departamento)->get();

    $inserts = [];
    foreach ($empleados as $emp) {
        for ($dia = 1; $dia <= 31; $dia++) {
            $shiftCode = $shiftTypes[array_rand($shiftTypes)];
            if (isset($turnos[$shiftCode])) {
                $inserts[] = [
                    'id_rol_mensual' => $rol->id_rol_mensual,
                    'id_empleado' => $emp->id_empleado,
                    'fecha' => sprintf('2026-10-%02d', $dia),
                    'id_turno' => $turnos[$shiftCode]->id_turno,
                ];
            }
        }
    }
    AsignacionTurnoDiario::insert($inserts);
}

echo "Turnos guardados exitosamente en la base de datos!\n";
