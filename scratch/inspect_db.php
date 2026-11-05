<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Services\RrhhService;
use Illuminate\Contracts\Console\Kernel;

echo "--- DEPARTAMENTOS EN SQL SERVER ---\n";
$depts = DepartamentoServicio::all();
foreach ($depts as $d) {
    echo "ID: {$d->id_departamento} | {$d->nombre_departmento}\n";
}
echo 'Total Depts: '.$depts->count()."\n\n";

echo "--- EMPLEADOS EN SQL SERVER (control_turnos_hospital) ---\n";
$emps = Empleado::with(['cargo', 'departamento'])->get();
foreach ($emps as $e) {
    echo "ID: {$e->id_empleado} | Item: {$e->item_id} | Bio: {$e->codigo_biometrico} | {$e->nombre_completo} | Cargo: ".($e->cargo?->nombre_cargo).' | Dept: '.($e->departamento?->nombre_departmento ?? 'Sin Dept')."\n";
}
echo 'Total Empleados: '.$emps->count()."\n\n";

echo "--- BUSCANDO EVELIO ANCIETA EN BDRRHH ---\n";
$allRrhh = app(RrhhService::class)->getPersonalFromSqlServer(false);
$found = [];
foreach ($allRrhh as $r) {
    if (stripos($r['nombre_completo'], 'ANCIETA') !== false) {
        $found[] = $r;
    }
}
print_r($found);
