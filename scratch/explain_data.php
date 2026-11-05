<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

echo "--- DATOS EXACTOS EN dbo.Empleados ---\n";
$emps = Empleado::with(['cargo', 'departamento'])
    ->whereIn('id_departamento', [8, 9, 10])
    ->orderBy('id_departamento')
    ->orderBy('id_cargo')
    ->get();

foreach ($emps as $e) {
    echo "id_empleado: {$e->id_empleado} | nombre: {$e->nombre_completo} | id_departamento: {$e->id_departamento} ({$e->departamento?->nombre_departmento}) | id_cargo: {$e->id_cargo} ({$e->cargo?->nombre_cargo})\n";
}
