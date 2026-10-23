<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\DepartamentoServicio;
use Illuminate\Contracts\Console\Kernel;

$seedIds = [1, 2, 3, 4, 5];
$deleted = DepartamentoServicio::whereIn('id_departamento', $seedIds)->delete();
echo "Se eliminaron $deleted departamentos obsoletos de prueba (IDs 1-5).\n";

echo "\nDepartamentos activos ahora:\n";
foreach (DepartamentoServicio::withCount('empleados')->orderBy('nombre_departmento')->get() as $d) {
    echo "ID: {$d->id_departamento} | {$d->nombre_departmento} ({$d->empleados_count} empleados)\n";
}
