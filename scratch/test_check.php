<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use App\Models\Turno;
use App\Services\RrhhService;
use Illuminate\Contracts\Console\Kernel;

echo "1. Checking connection to SQL Server control_turnos_hospital...\n";
$dbName = DB::connection()->getDatabaseName();
echo "Connected DB: $dbName\n";

echo "2. Ensuring employee 3997 (Evelio Ancieta Vasquez)...\n";
$emp = app(RrhhService::class)->ensureEmployeeInLocal(3997, 1);
if ($emp) {
    echo "Employee ID (PK): {$emp->id_empleado}, Item ID: {$emp->item_id}, Name: {$emp->nombre_completo}\n";
} else {
    echo "Employee not found!\n";
}

echo "3. Testing assignment to Dept 1 for Oct 2026...\n";
$rol = RolMensual::firstOrCreate(['mes' => 10, 'anio' => 2026, 'id_departamento' => 1]);
echo "Rol ID: {$rol->id_rol_mensual}\n";

$diasEnMes = cal_days_in_month(CAL_GREGORIAN, 10, 2026);
$turnoLibre = Turno::where('codigo_turno', 'L')->first();
$idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

$yaExiste = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
    ->where('id_empleado', $emp->id_empleado)
    ->exists();

if (! $yaExiste) {
    $inserts = [];
    for ($d = 1; $d <= $diasEnMes; $d++) {
        $fecha = sprintf('%04d-%02d-%02d', 2026, 10, $d);
        $inserts[] = [
            'id_rol_mensual' => $rol->id_rol_mensual,
            'id_empleado' => $emp->id_empleado,
            'id_fuente' => null,
            'fecha' => $fecha,
            'id_turno' => $idTurnoLibre,
        ];
    }
    AsignacionTurnoDiario::insert($inserts);
    echo "Successfully inserted $diasEnMes days of assignments for Evelio into SQL Server!\n";
} else {
    echo "Assignments already exist in SQL Server for Evelio!\n";
}

$count = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
    ->where('id_empleado', $emp->id_empleado)
    ->count();
echo "Total verified assignments in SQL Server for this employee: $count\n";
