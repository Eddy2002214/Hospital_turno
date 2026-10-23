<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "=== USUARIOS ===\n";
$users = DB::table('usuarios')->get();
foreach ($users as $u) {
    echo "ID: {$u->id_usuario} | User: {$u->nombre_usuario} | Rol: {$u->rol} | Dept ID: {$u->id_departamento} | Emp ID: {$u->id_empleado}\n";
}

echo "\n=== EMPLEADOS ===\n";
$emps = DB::table('empleados')
    ->leftJoin('cargos', 'empleados.id_cargo', '=', 'cargos.id_cargo')
    ->leftJoin('departamentos_servicios', 'empleados.id_departamento', '=', 'departamentos_servicios.id_departamento')
    ->select('empleados.*', 'cargos.nombre_cargo', 'departamentos_servicios.nombre_departmento')
    ->get();
foreach ($emps as $e) {
    echo "ID: {$e->id_empleado} | Bio: {$e->codigo_biometrico} | Nom: {$e->nombre_completo} | Cargo: {$e->nombre_cargo} | Dept: {$e->nombre_departmento} (ID: {$e->id_departamento})\n";
}
