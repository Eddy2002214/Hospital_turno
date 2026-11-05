<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$rol = RolMensual::where('mes', 11)->where('anio', 2026)->where('id_departamento', 8)->first();
$asigs = AsignacionTurnoDiario::with('empleado')->where('id_rol_mensual', $rol->id_rol_mensual)->get()->groupBy('id_empleado');
echo "Currently in Rol 29:\n";
foreach ($asigs as $eId => $items) {
    echo "  - Emp $eId: ".($items->first()->empleado->nombre_completo ?? 'Unknown')."\n";
}
