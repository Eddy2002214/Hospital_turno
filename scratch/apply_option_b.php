<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Cargo;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

echo "--- APLICANDO OPCION B EN SQL SERVER ---\n";

// 1. Obtener o verificar cargo 2 'Operador / Especialista'
$cargoOperador = Cargo::where('id_cargo', 2)->first()
    ?? Cargo::where('nombre_cargo', 'Operador / Especialista')->first();

echo "Cargo Operador ID: {$cargoOperador->id_cargo} - {$cargoOperador->nombre_cargo}\n";

// 2. Mover todos los empleados con id_cargo != 1, 2, 3, 4 hacia cargo 2
$moved = Empleado::whereNotIn('id_cargo', [1, 2, 3, 4])
    ->update(['id_cargo' => $cargoOperador->id_cargo]);
echo "Empleados actualizados a 'Operador / Especialista': $moved\n";

// 3. Eliminar cualquier cargo que no sea los 4 base
$deletedCargos = Cargo::whereNotIn('id_cargo', [1, 2, 3, 4])->delete();
echo "Cargos adicionales eliminados: $deletedCargos\n";

// 4. Mostrar cómo quedó la tabla Cargos
echo "\n--- ESTADO FINAL DE LA TABLA Cargos ---\n";
$cargos = Cargo::all();
foreach ($cargos as $c) {
    $count = Empleado::where('id_cargo', $c->id_cargo)->count();
    echo "id_cargo: {$c->id_cargo} | {$c->nombre_cargo} | Empleados: $count\n";
}
