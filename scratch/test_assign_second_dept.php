<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Contracts\Console\Kernel;

// Test the scenario:
// Emp 7 (Usuario Enfermería 1) has base id_departamento = 3 (Emergencias)
// In Feb 2026, Emp 7 is in Rol 25 (Emergencias).
// Now suppose Jefe of TI (Dept 1) assigns Emp 7 to TI (Dept 1) for Feb 2026.

$rol25 = RolMensual::firstOrCreate(['mes' => 2, 'anio' => 2026, 'id_departamento' => 3]);
$rol23 = RolMensual::firstOrCreate(['mes' => 2, 'anio' => 2026, 'id_departamento' => 1]);
$turnoM = Turno::where('codigo_turno', 'M')->first();
$turnoL = Turno::where('codigo_turno', 'L')->first();

// Ensure Emp 7 in Rol 25 has M
AsignacionTurnoDiario::where('id_rol_mensual', $rol25->id_rol_mensual)->where('id_empleado', 7)->delete();
for ($d = 1; $d <= 28; $d++) {
    AsignacionTurnoDiario::create([
        'id_rol_mensual' => $rol25->id_rol_mensual,
        'id_empleado' => 7,
        'fecha' => sprintf('2026-02-%02d', $d),
        'id_turno' => $turnoM->id_turno,
    ]);
}

// Assign Emp 7 to TI in Feb 2026 with L
AsignacionTurnoDiario::where('id_rol_mensual', $rol23->id_rol_mensual)->where('id_empleado', 7)->delete();
for ($d = 1; $d <= 28; $d++) {
    AsignacionTurnoDiario::create([
        'id_rol_mensual' => $rol23->id_rol_mensual,
        'id_empleado' => 7,
        'fecha' => sprintf('2026-02-%02d', $d),
        'id_turno' => $turnoL->id_turno,
    ]);
}

echo "Emp 7 assignments in Feb 2026:\n";
$asigs = AsignacionTurnoDiario::where('id_empleado', 7)->whereBetween('fecha', ['2026-02-01', '2026-02-28'])->get();
foreach ($asigs->groupBy('id_rol_mensual') as $rId => $group) {
    $r = RolMensual::find($rId);
    echo "  Rol ID $rId (Dept {$r->id_departamento}): {$group->count()} records, shift sample: ".$group->first()->id_turno."\n";
}
