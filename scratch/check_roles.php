<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

foreach ([15, 16, 17, 18, 33] as $id) {
    $rol = RolMensual::with('departamento', 'jefe')->find($id);
    if (! $rol) {
        echo "Rol $id: not found\n";

        continue;
    }
    $emps = AsignacionTurnoDiario::where('id_rol_mensual', $id)->select('id_empleado')->distinct()->pluck('id_empleado')->toArray();
    echo "Rol {$id} ({$rol->departamento->nombre_departmento}): Jefe={$rol->jefe->nombre_completo}, employees: ".implode(',', $emps)."\n";
}
