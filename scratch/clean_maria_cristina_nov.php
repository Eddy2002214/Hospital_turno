<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

$deleted = AsignacionTurnoDiario::where('id_rol_mensual', 29)->where('id_empleado', 1372)->delete();
echo "Eliminadas {$deleted} asignaciones de Maria Cristina (Emp 1372) del Rol 29 (Finanzas - Nov 2026).\n";

$remaining = AsignacionTurnoDiario::with('empleado')->where('id_rol_mensual', 29)->get()->groupBy('id_empleado');
echo "Personal que queda en Noviembre 2026 (Finanzas):\n";
foreach ($remaining as $eId => $items) {
    $eName = $items->first()->empleado->nombre_completo ?? "ID {$eId}";
    echo "  - Emp {$eId}: {$eName} (".count($items)." turnos)\n";
}
