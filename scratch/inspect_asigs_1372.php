<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

$asigs = AsignacionTurnoDiario::where('id_rol_mensual', 29)->where('id_empleado', 1372)->get();
echo 'Total asigs para 1372 en Rol 29: '.count($asigs)."\n";
foreach ($asigs->take(5) as $a) {
    echo "ID: {$a->id_asignacion} | Fecha: {$a->fecha} | Turno ID: {$a->id_turno} | Created: {$a->created_at} | Updated: {$a->updated_at}\n";
}
foreach ($asigs->skip(30)->take(5) as $a) {
    echo "ID: {$a->id_asignacion} | Fecha: {$a->fecha} | Turno ID: {$a->id_turno} | Created: {$a->created_at} | Updated: {$a->updated_at}\n";
}
