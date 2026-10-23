<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$unusedIds = [1, 2, 3, 4, 5];
foreach ($unusedIds as $id) {
    $empCount = DB::table('empleados')->where('id_departamento', $id)->count();
    $userCount = DB::table('usuarios')->where('id_empleado', $id)->count();
    $dept = DB::table('departamentos_servicios')->where('id_departamento', $id)->first();
    echo "ID: $id | ".($dept ? $dept->nombre_departmento : 'No existe')." -> Empleados: $empCount\n";
}
