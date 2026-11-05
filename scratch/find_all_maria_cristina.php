<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$allAsigs = AsignacionTurnoDiario::with('rolMensual.departamento')
    ->where('id_empleado', 1372)
    ->get()
    ->groupBy('id_rol_mensual');

echo "Roles donde aparece Maria Cristina (Emp 1372):\n";
foreach ($allAsigs as $rolId => $items) {
    $rol = RolMensual::with('departamento')->find($rolId);
    if ($rol) {
        echo "Rol {$rolId}: Mes {$rol->mes}/{$rol->anio} - Dept: ".($rol->departamento->nombre_departmento ?? 'null').' - Total asignaciones: '.count($items)."\n";
    } else {
        echo "Rol {$rolId}: no existe - Total asigs: ".count($items)."\n";
    }
}
