<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

echo "CONTEO DE EMPLEADOS POR DEPARTAMENTO:\n";
foreach (DepartamentoServicio::withCount('empleados')->get() as $d) {
    if ($d->empleados_count > 0) {
        echo "ID: {$d->id_departamento} | Nombre: {$d->nombre_departmento} => {$d->empleados_count} empleados\n";
    }
}

$sinDept = Empleado::whereNull('id_departamento')->count();
echo "Sin departamento: $sinDept empleados\n";
