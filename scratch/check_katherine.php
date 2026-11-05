<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

$katherine = Empleado::with(['cargo', 'departamento'])->where('item_id', '5547')->first();
echo "Katherine ID: {$katherine->id_empleado} | Cargo: ".($katherine->cargo?->nombre_cargo).' | Dept: '.($katherine->departamento?->nombre_departmento)." (ID: {$katherine->id_departamento})\n";
