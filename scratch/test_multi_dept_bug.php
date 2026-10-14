<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

// Ensure Rol 25 (Emergencias) has Emp 7 with M shifts
$rol25 = RolMensual::firstOrCreate(['mes' => 2, 'anio' => 2026, 'id_departamento' => 3]);
$turnoM = Turno::where('codigo_turno', 'M')->first();

AsignacionTurnoDiario::where('id_rol_mensual', $rol25->id_rol_mensual)->where('id_empleado', 7)->delete();
for ($d = 1; $d <= 28; $d++) {
    AsignacionTurnoDiario::create([
        'id_rol_mensual' => $rol25->id_rol_mensual,
        'id_empleado' => 7,
        'fecha' => sprintf('2026-02-%02d', $d),
        'id_turno' => $turnoM->id_turno,
    ]);
}

echo "Pre-condition: Emp 7 has 28 M shifts in Rol 25 (Emergencias).\n";

// Now, Admin saves with serviceId = 'todos'.
// People includes:
// 1. Jefe de Emergencias (dept_id: 3) -> causes Rol 25 to be in $rolMensuales
// 2. Jefe de Sistemas (dept_id: 1) -> causes Rol 23 to be in $rolMensuales
// 3. Emp 7 in TI (dept_id: 1) with shift M!
$people = [
    [
        'id' => 11, // Jefe de Emergencias
        'name' => 'Jefe de Emergencias',
        'dept_id' => 3,
        'dept_name' => 'Emergencias',
        'shifts' => array_fill(0, 28, 'M*'),
    ],
    [
        'id' => 2, // Jefe de Sistemas
        'name' => 'Jefe de Sistemas',
        'dept_id' => 1,
        'dept_name' => 'Tecnología de la Información',
        'shifts' => array_fill(0, 28, 'M'),
    ],
    [
        'id' => 7, // Emp 7 (Usuario Enfermería 1) in TI!
        'name' => 'Usuario Enfermería 1',
        'dept_id' => 1,
        'dept_name' => 'Tecnología de la Información',
        'shifts' => array_fill(0, 28, 'M'),
    ],
];

$controller = new RoleController;
$req = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => 'todos',
    'people' => $people,
]);

$res = $controller->saveRole($req);
echo 'Status: '.$res->getStatusCode()."\n";
echo 'Response: '.$res->getContent()."\n";
