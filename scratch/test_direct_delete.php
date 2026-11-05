<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$rolMensual = RolMensual::where('mes', 11)->where('anio', 2026)->where('id_departamento', 8)->first();
$empsInThisDept = [35, 680, 1459, 482];

$queryRemoved = AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual);
if (! empty($empsInThisDept)) {
    $queryRemoved->whereNotIn('id_empleado', $empsInThisDept);
}
if ($rolMensual->id_jefe) {
    $queryRemoved->where('id_empleado', '!=', $rolMensual->id_jefe);
}

$deletedCount = $queryRemoved->delete();
echo 'Deleted count: '.$deletedCount."\n";
