<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Cargo;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

echo "--- CARGOS EN SQL SERVER ---\n";
$cargos = Cargo::all();
foreach ($cargos as $c) {
    $count = Empleado::where('id_cargo', $c->id_cargo)->count();
    echo "ID: {$c->id_cargo} | Cargo: {$c->nombre_cargo} | Empleados con este cargo: $count\n";
}
