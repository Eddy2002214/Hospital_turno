<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$users = User::whereIn('id_empleado', [31, 32])->count();
echo "Usuarios vinculados a Evelio o Nelly: $users\n";

$emps = Empleado::whereNotIn('id_empleado', [31, 32])
    ->where('id_cargo', 2)
    ->get();

echo "Los 3 empleados reales que deben quedar:\n";
foreach ($emps as $e) {
    echo "ID: {$e->id_empleado} | {$e->codigo_biometrico} | {$e->nombre_completo} | Dept ID: {$e->id_departamento}\n";
}
