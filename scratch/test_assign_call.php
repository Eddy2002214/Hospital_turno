<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "--- PROBANDO ASIGNACION DE EVELIO (5554) AL DEPT 8 EN SQL SERVER ---\n";

$req = Request::create('/api/empleados/assign', 'POST', [
    'employee_id' => 5554,
    'department_id' => 8,
    'mes' => 10,
    'anio' => 2026,
]);

$controller = app(RoleController::class);
$response = $controller->assignEmployee($req);

echo 'Status code: '.$response->getStatusCode()."\n";
echo 'Response content: '.$response->getContent()."\n";

$rol = RolMensual::where('mes', 10)->where('anio', 2026)->where('id_departamento', 8)->first();
echo 'Rol ID creado/obtenido: '.($rol ? $rol->id_rol_mensual : 'Ninguno')."\n";

if ($rol) {
    $count = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)->count();
    echo "Total asignaciones en SQL Server para este Rol: $count\n";
}
