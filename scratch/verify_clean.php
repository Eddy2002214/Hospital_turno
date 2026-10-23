<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

echo "USUARIOS:\n";
foreach (User::all() as $u) {
    echo "ID: {$u->id_usuario} | User: {$u->nombre_usuario} | Rol: {$u->rol} | id_empleado: {$u->id_empleado}\n";
}

echo "\nEMPLEADOS (".Empleado::count()."):\n";
foreach (Empleado::with('cargo', 'departamento')->get() as $e) {
    $c = $e->cargo ? $e->cargo->nombre_cargo : 'Sin cargo';
    $d = $e->departamento ? $e->departamento->nombre_departmento : 'Sin departamento';
    echo sprintf("#%02d | [%-6s] %-35s | %-20s | %s\n", $e->id_empleado, $e->codigo_biometrico, $e->nombre_completo, $c, $d);
}
