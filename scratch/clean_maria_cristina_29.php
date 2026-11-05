<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

$deleted = AsignacionTurnoDiario::where('id_rol_mensual', 29)->where('id_empleado', 1372)->delete();
echo "Deleted Maria Cristina from rol 29: $deleted rows\n";
