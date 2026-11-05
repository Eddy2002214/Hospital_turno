<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

foreach ([29, 38, 39] as $rid) {
    $r = RolMensual::with('departamento', 'jefe')->find($rid);
    if (! $r) {
        continue;
    }
    echo "=== ROL {$rid} (Mes {$r->mes}/{$r->anio} - {$r->departamento->nombre_departmento}) ===\n";
    $emps = AsignacionTurnoDiario::with('empleado')->where('id_rol_mensual', $rid)->get()->groupBy('id_empleado');
    foreach ($emps as $eId => $items) {
        $eName = $items->first()->empleado->nombre_completo ?? "ID {$eId}";
        $bio = $items->first()->empleado->codigo_biometrico ?? '';
        echo "  - Emp {$eId} ({$bio} - {$eName}): ".count($items)." turnos\n";
    }
}
