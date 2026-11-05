<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

foreach (AsignacionTurnoDiario::where('id_rol_mensual', 29)->select('id_empleado')->distinct()->get() as $a) {
    echo $a->id_empleado.' - '.Empleado::find($a->id_empleado)->nombre_completo.PHP_EOL;
}
