<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Contracts\Console\Kernel;

// 1. Reset state: Put Emp 7 in Rol 25 (Emergencias) with 28 M shifts
$rol25 = RolMensual::firstOrCreate(['mes' => 2, 'anio' => 2026, 'id_departamento' => 3]);
$turnoM = Turno::where('codigo_turno', 'M')->first();

AsignacionTurnoDiario::where('id_rol_mensual', $rol25->id_rol_mensual)->where('id_empleado', 7)->delete();
for ($d = 1; $d <= 28; $d++) {
    AsignacionTurnoDiario::create([
        'id_rol_mensual' => $rol25->id_rol_mensual,
        'id_empleado' => 7,
        'fecha' => sprintf('2026-02-%02d', $d),
        'id_turno' => $turnoM->id_turno,
    ]);
}
echo "Setup: Emp 7 has 28 M shifts in Rol 25 (Emergencias)\n";

// Function simulating our new save logic
function testSave($serviceId, $people)
{
    $mes = 2;
    $anio = 2026;
    $empleadosDb = Empleado::with(['cargo', 'departamento'])->whereIn('id_empleado', collect($people)->pluck('id'))->get()->keyBy('id_empleado');
    $turnos = Turno::all()->keyBy('codigo_turno');
    $deptsDb = DepartamentoServicio::all()->keyBy('id_departamento');

    $rolMensuales = [];
    $inserts = [];

    foreach ($people as $person) {
        $empDb = $empleadosDb[$person['id']] ?? null;
        if (! $empDb) {
            continue;
        }

        if ($serviceId && $serviceId !== 'todos') {
            $deptId = (int) $serviceId;
        } else {
            $deptId = isset($person['dept_id']) && $person['dept_id'] !== null
                ? (int) $person['dept_id']
                : (int) $empDb->id_departamento;
        }
        $deptKey = $deptId ?? 'null';

        if (! isset($rolMensuales[$deptKey])) {
            $rolMensuales[$deptKey] = RolMensual::firstOrCreate([
                'mes' => $mes,
                'anio' => $anio,
                'id_departamento' => $deptId,
            ]);
        }
        $rolMensual = $rolMensuales[$deptKey];
        $deptName = $person['dept_name'] ?? ($deptsDb[$deptId]->nombre_departmento ?? 'Área Asignada');

        foreach ($person['shifts'] as $index => $shiftCode) {
            $dia = $index + 1;
            if (! checkdate($mes, $dia, $anio)) {
                continue;
            }
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            $turno = $turnos[$shiftCode] ?? null;
            if ($turno) {
                $inserts[] = [
                    'id_rol_mensual' => $rolMensual->id_rol_mensual,
                    'id_empleado' => $person['id'],
                    'id_fuente' => null,
                    'fecha' => $fecha,
                    'id_turno' => $turno->id_turno,
                    'shift_code' => $shiftCode,
                    'dept_id' => $deptId,
                    'dept_name' => $deptName,
                ];
            }
        }
    }

    $shiftsOverlap = function (string $codeA, string $codeB): bool {
        if ($codeA === 'L' || $codeB === 'L') {
            return false;
        }
        if ($codeA === $codeB) {
            return true;
        }
        if ($codeA === 'M*' && in_array($codeB, ['M', 'T'])) {
            return true;
        }
        if ($codeB === 'M*' && in_array($codeA, ['M', 'T'])) {
            return true;
        }

        return false;
    };

    // 1. Internal Validation
    $activeByEmpDate = [];
    foreach ($inserts as $ins) {
        if ($ins['shift_code'] === 'L') {
            continue;
        }
        $key = $ins['id_empleado'].'_'.$ins['fecha'];
        if (! isset($activeByEmpDate[$key])) {
            $activeByEmpDate[$key] = [];
        }
        foreach ($activeByEmpDate[$key] as $prev) {
            if ($prev['dept_id'] !== $ins['dept_id'] && $shiftsOverlap($ins['shift_code'], $prev['shift_code'])) {
                $empName = $empleadosDb[$ins['id_empleado']]->nombre_completo ?? "ID {$ins['id_empleado']}";
                $dia = (int) date('d', strtotime($ins['fecha']));

                return [
                    'status' => 422,
                    'error' => "Conflicto interno: {$empName} no puede tener turnos en el mismo horario el día {$dia} entre \"{$prev['dept_name']}\" ({$prev['shift_code']}) y \"{$ins['dept_name']}\" ({$ins['shift_code']}).",
                ];
            }
        }
        $activeByEmpDate[$key][] = $ins;
    }

    // 2. External Validation
    $empleadosIds = collect($people)->pluck('id')->toArray();
    $startDate = sprintf('%04d-%02d-01', $anio, $mes);
    $endDate = date('Y-m-t', strtotime($startDate));

    $existingAssignments = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno', 'empleado'])
        ->whereIn('id_empleado', $empleadosIds)
        ->whereBetween('fecha', [$startDate, $endDate])
        ->whereHas('turno', fn ($qt) => $qt->where('codigo_turno', '!=', 'L'))
        ->get()
        ->groupBy(fn ($item) => $item->id_empleado.'_'.$item->fecha);

    foreach ($inserts as $ins) {
        if ($ins['shift_code'] === 'L') {
            continue;
        }
        $key = $ins['id_empleado'].'_'.$ins['fecha'];
        if (isset($existingAssignments[$key])) {
            foreach ($existingAssignments[$key] as $conflict) {
                $conflictDeptId = $conflict->rolMensual ? (int) $conflict->rolMensual->id_departamento : null;
                if ($conflictDeptId !== null && $conflictDeptId !== (int) $ins['dept_id']) {
                    $extCode = $conflict->turno ? $conflict->turno->codigo_turno : 'L';
                    if ($shiftsOverlap($ins['shift_code'], $extCode)) {
                        $empName = $conflict->empleado ? $conflict->empleado->nombre_completo : "ID {$ins['id_empleado']}";
                        $deptName = $conflict->rolMensual?->departamento?->nombre_departmento ?? 'otra área';
                        $dia = (int) date('d', strtotime($ins['fecha']));

                        return [
                            'status' => 422,
                            'error' => "Conflicto externo: {$empName} ya tiene programado el turno {$extCode} en \"{$deptName}\" el día {$dia}.",
                        ];
                    }
                }
            }
        }
    }

    return ['status' => 200, 'msg' => 'OK'];
}

// Test A: Admin submits with serviceId = 'todos', including Jefe Emergencias, Jefe TI, and Emp 7 in TI with M
$testA = testSave('todos', [
    ['id' => 11, 'dept_id' => 3, 'dept_name' => 'Emergencias', 'shifts' => array_fill(0, 28, 'M*')],
    ['id' => 2, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'M')],
    ['id' => 7, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'M')],
]);
echo "Test A (Admin multi-dept with Emp 7 in TI): Status {$testA['status']}\n";
echo '   Message: '.($testA['error'] ?? $testA['msg'])."\n\n";

// Test B: Jefe of TI submits TI role with Emp 7 in TI with M
$testB = testSave('1', [
    ['id' => 2, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'M')],
    ['id' => 7, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'M')],
]);
echo "Test B (Jefe of TI with Emp 7 in TI): Status {$testB['status']}\n";
echo '   Message: '.($testB['error'] ?? $testB['msg'])."\n\n";

// Test C: Admin submits Emp 7 with Libre in TI on day 1..28 (NO overlap)
$testC = testSave('1', [
    ['id' => 2, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'M')],
    ['id' => 7, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'L')],
]);
echo "Test C (Emp 7 with Libre in TI): Status {$testC['status']}\n";
echo '   Message: '.($testC['error'] ?? $testC['msg'])."\n\n";

// Test D: Admin submits payload containing internal overlap (Emp 7 in TI with M and in Emergencias with M)
$testD = testSave('todos', [
    ['id' => 7, 'dept_id' => 3, 'dept_name' => 'Emergencias', 'shifts' => array_fill(0, 28, 'M')],
    ['id' => 7, 'dept_id' => 1, 'dept_name' => 'Tecnología de la Información', 'shifts' => array_fill(0, 28, 'M')],
]);
echo "Test D (Internal conflict in payload): Status {$testD['status']}\n";
echo '   Message: '.($testD['error'] ?? $testD['msg'])."\n\n";
