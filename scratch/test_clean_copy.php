<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = app(RoleController::class);

$request = Request::create('/api/empleados/copy-month', 'POST', [
    'mes' => 3,
    'anio' => 2026,
    'department_id' => 'todos',
]);

$response = $controller->copyMonthAssignments($request);
echo 'Response: '.$response->getContent()."\n";

echo "--- VERIFICACIÓN DE MARZO 2026 TRAS LA COPIA LIMPIA ---\n";
$roles = RolMensual::with('departamento', 'jefe')->where('mes', 3)->where('anio', 2026)->get();
foreach ($roles as $r) {
    echo "Rol ID: {$r->id_rol_mensual} | Dept: ".($r->departamento->nombre_departmento ?? 'null').' | Jefe: '.($r->jefe->nombre_completo ?? 'null')."\n";
    $asigs = AsignacionTurnoDiario::with(['empleado', 'turno'])
        ->where('id_rol_mensual', $r->id_rol_mensual)
        ->get()
        ->groupBy('id_empleado');

    foreach ($asigs as $empId => $empAsigs) {
        $empName = $empAsigs->first()->empleado->nombre_completo ?? "ID {$empId}";
        $isJefe = ($r->id_jefe == $empId) ? ' [JEFE]' : '';
        $sampleTurnos = $empAsigs->take(5)->pluck('turno.codigo_turno')->implode(' ');
        echo "  - {$empName}{$isJefe} (Total días: ".count($empAsigs)."): Primeros turnos: [{$sampleTurnos}]\n";
    }
}
