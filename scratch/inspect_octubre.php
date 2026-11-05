<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

echo "--- OCTUBRE 2026 (mes=10) ---\n";
$octRoles = RolMensual::with('departamento')->where('mes', 10)->where('anio', 2026)->get();
foreach ($octRoles as $r) {
    echo "Rol {$r->id_rol_mensual} (Dept {$r->id_departamento}: ".($r->departamento->nombre_departmento ?? 'null')."):\n";
    $emps = AsignacionTurnoDiario::with('empleado')->where('id_rol_mensual', $r->id_rol_mensual)->select('id_empleado')->distinct()->get();
    foreach ($emps as $e) {
        echo "  - Emp {$e->id_empleado} (".($e->empleado->nombre_completo ?? 'null').")\n";
    }
}
