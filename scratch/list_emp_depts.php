<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

foreach (Empleado::all() as $e) {
    echo "ID: {$e->id_empleado} | Bio: {$e->codigo_biometrico} | Nombre: {$e->nombre_completo} | Dept ID: {$e->id_departamento}\n";
}
