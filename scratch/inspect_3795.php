<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

$emps = Empleado::with('departamento')->whereIn('codigo_biometrico', ['3793', '3794', '3795', '4029'])->get();
foreach ($emps as $e) {
    echo "Bio: {$e->codigo_biometrico} | Nombre: {$e->nombre_completo} | Depto: ".($e->departamento->nombre_departmento ?? 'null')." (ID: {$e->id_departamento})\n";
}
