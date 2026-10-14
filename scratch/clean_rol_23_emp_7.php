<?php

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

AsignacionTurnoDiario::where('id_rol_mensual', 23)->where('id_empleado', 7)->delete();
echo "Emp 7 cleaned from Rol 23 (TI). Emp 7 remains in Rol 25 (Emergencias).\n";
