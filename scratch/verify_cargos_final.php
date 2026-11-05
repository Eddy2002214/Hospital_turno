<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Cargo;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

echo "--- LISTA FINAL DE CARGOS ---\n";
$cargos = Cargo::all();
foreach ($cargos as $c) {
    echo "id_cargo: {$c->id_cargo} | {$c->nombre_cargo}\n";
}

echo "\n--- EMPLEADOS Y SUS CARGOS ---\n";
$emps = Empleado::with(['cargo', 'departamento'])->get();
foreach ($emps as $e) {
    echo "Emp: {$e->nombre_completo} | Cargo ID: {$e->id_cargo} ({$e->cargo?->nombre_cargo}) | Especialidad: {$e->profesion_especialidad}\n";
}
