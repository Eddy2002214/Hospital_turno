<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

$asigs = AsignacionTurnoDiario::with('rolMensual')->where('id_empleado', 1372)->get();
echo 'Maria Cristina (1372) assignments count: '.$asigs->count()."\n";
foreach ($asigs as $a) {
    $rol = $a->rolMensual;
    echo "id_asig: {$a->id_asignacion}, rol_id: {$a->id_rol_mensual}, mes: {$rol->mes}, anio: {$rol->anio}, dept: {$rol->id_departamento}, fecha: {$a->fecha}, turno: {$a->id_turno}\n";
}
