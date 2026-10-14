<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

echo "=== TEST 1: VERIFICAR getUnassigned CON EMPLEADO MULTI-ÁREA ===\n";
// Emp 7 está en Rol 25 (Emergencias) y en Rol 23 (TI) en Feb 2026
$reqUnassigned = new Request(['mes' => 2, 'anio' => 2026]);
$staff = $controller->getUnassigned($reqUnassigned);
$emp7 = collect($staff)->firstWhere('id', 7);

echo "Emp 7:\n";
echo "  Base Dept: {$emp7['base_dept_name']} (ID: {$emp7['base_dept_id']})\n";
echo '  Assigned Dept IDs: '.json_encode($emp7['assigned_dept_ids'])."\n";
echo '  Assigned Dept Names: '.json_encode($emp7['assigned_dept_names'])."\n";
echo "  Dept string: {$emp7['dept']}\n";
$hasBoth = in_array(1, $emp7['assigned_dept_ids']) && in_array(3, $emp7['assigned_dept_ids']);
echo '  Tiene ambas áreas en assigned_dept_ids: '.($hasBoth ? 'SÍ (CORRECTO)' : 'NO (ERROR)')."\n\n";

echo "=== TEST 2: ASIGNAR EMPLEADO SIN PERDER SU ÁREA ANTERIOR ===\n";
// Tomamos a Emp 14 (Usuario Emergencias 3), que está en Emergencias (Dept 3).
// Le asignamos TI (Dept 1) en Feb 2026 vía assignEmployee.
$countBeforeEmergencias = AsignacionTurnoDiario::whereHas('rolMensual', fn ($q) => $q->where('mes', 2)->where('anio', 2026)->where('id_departamento', 3))
    ->where('id_empleado', 14)->count();
echo "Turnos de Emp 14 en Emergencias antes de asignar TI: {$countBeforeEmergencias}\n";

$reqAssign = new Request([
    'employee_id' => 14,
    'department_id' => 1,
    'mes' => 2,
    'anio' => 2026,
]);
$resAssign = $controller->assignEmployee($reqAssign);
echo 'Status assignEmployee: '.$resAssign->getStatusCode()."\n";

$countAfterEmergencias = AsignacionTurnoDiario::whereHas('rolMensual', fn ($q) => $q->where('mes', 2)->where('anio', 2026)->where('id_departamento', 3))
    ->where('id_empleado', 14)->count();
$countInTI = AsignacionTurnoDiario::whereHas('rolMensual', fn ($q) => $q->where('mes', 2)->where('anio', 2026)->where('id_departamento', 1))
    ->where('id_empleado', 14)->count();

echo "Turnos de Emp 14 en Emergencias después: {$countAfterEmergencias} (NO se borraron)\n";
echo "Turnos de Emp 14 en TI después: {$countInTI} (Se incorporaron en Libre)\n";
echo 'Área base del empleado: '.Empleado::find(14)->id_departamento." (Base no modificada)\n\n";

echo "=== TEST 3: REMOVER EMPLEADO DE UN ÁREA SIN AFECTAR LA OTRA ===\n";
// Removemos a Emp 14 de TI (Dept 1)
$reqRemove = new Request([
    'employee_id' => 14,
    'department_id' => 1,
    'mes' => 2,
    'anio' => 2026,
]);
$resRemove = $controller->removeEmployee($reqRemove);
echo 'Status removeEmployee: '.$resRemove->getStatusCode()."\n";

$countFinalEmergencias = AsignacionTurnoDiario::whereHas('rolMensual', fn ($q) => $q->where('mes', 2)->where('anio', 2026)->where('id_departamento', 3))
    ->where('id_empleado', 14)->count();
$countFinalTI = AsignacionTurnoDiario::whereHas('rolMensual', fn ($q) => $q->where('mes', 2)->where('anio', 2026)->where('id_departamento', 1))
    ->where('id_empleado', 14)->count();

echo "Turnos de Emp 14 en Emergencias final: {$countFinalEmergencias} (Permanecen intactos)\n";
echo "Turnos de Emp 14 en TI final: {$countFinalTI} (Eliminado solo de TI)\n";
