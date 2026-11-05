<?php

namespace App\Http\Controllers;

use App\Models\AsignacionTurnoDiario;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function printReport(Request $request)
    {
        $mes = $request->input('mes', date('n'));
        $anio = $request->input('anio', date('Y'));
        $departamentoId = $request->input('departamento_id');

        $departamento = null;
        $rolesMensuales = collect();
        if ($departamentoId && $departamentoId !== 'todos') {
            $r = RolMensual::with('departamento')
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->where('id_departamento', $departamentoId)
                ->first();
            if ($r) {
                $rolesMensuales->push($r);
            }
            $departamento = DepartamentoServicio::find($departamentoId);
        } else {
            $rolesMensuales = RolMensual::with('departamento')
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->get();
        }

        $rolesIds = $rolesMensuales->pluck('id_rol_mensual')->toArray();
        $todasAsignaciones = [];
        $empleadosAsignadosPorDept = [];
        $empToRoleDeptMap = [];
        $todosEmpIdsAsignados = [];

        if (count($rolesIds) > 0) {
            $asignacionesCollection = AsignacionTurnoDiario::with('fuente')->whereIn('id_rol_mensual', $rolesIds)->get();
            $todasAsignaciones = $asignacionesCollection->groupBy('id_empleado');

            foreach ($rolesMensuales as $rol) {
                $empIds = $asignacionesCollection
                    ->where('id_rol_mensual', $rol->id_rol_mensual)
                    ->pluck('id_empleado')
                    ->unique()
                    ->toArray();

                if (count($empIds) > 0) {
                    $empleadosAsignadosPorDept[$rol->id_departamento] = $empIds;
                    $todosEmpIdsAsignados = array_merge($todosEmpIdsAsignados, $empIds);

                    foreach ($empIds as $eId) {
                        $empToRoleDeptMap[$eId] = [
                            'id' => $rol->id_departamento,
                            'name' => $rol->departamento ? $rol->departamento->nombre_departmento : 'Área Asignada',
                        ];
                    }
                }
            }
            $todosEmpIdsAsignados = array_unique($todosEmpIdsAsignados);
        }

        $query = Empleado::with(['cargo', 'departamento']);
        $query->whereHas('cargo', function ($q) {
            $q->where('nombre_cargo', '!=', 'Administrador de Sistema');
        });

        if ($departamentoId && $departamentoId !== 'todos') {
            if (isset($empleadosAsignadosPorDept[$departamentoId])) {
                $query->whereIn('id_empleado', $empleadosAsignadosPorDept[$departamentoId]);
            } else {
                $query->where('id_departamento', $departamentoId);
            }
        } else {
            $deptsConRol = array_keys($empleadosAsignadosPorDept);
            if (count($deptsConRol) > 0) {
                $query->where(function ($q) use ($deptsConRol, $todosEmpIdsAsignados) {
                    $q->whereIn('id_empleado', $todosEmpIdsAsignados)
                        ->orWhere(function ($q2) use ($deptsConRol) {
                            $q2->whereNotNull('id_departamento')
                                ->whereNotIn('id_departamento', $deptsConRol);
                        });
                });
            } else {
                $query->whereNotNull('id_departamento');
            }
        }

        $rolesByDept = $rolesMensuales->keyBy(fn ($r) => (int) $r->id_departamento);

        $empleados = $query->get()->sort(function ($a, $b) use ($empToRoleDeptMap, $rolesByDept) {
            $deptA = (int) ($empToRoleDeptMap[$a->id_empleado]['id'] ?? ($a->id_departamento ?? 999999));
            $deptB = (int) ($empToRoleDeptMap[$b->id_empleado]['id'] ?? ($b->id_departamento ?? 999999));
            if ($deptA !== $deptB) {
                return $deptA <=> $deptB;
            }

            // Jerarquía de cargo: El Jefe del área para este mes va primero
            $rolA = $rolesByDept[$deptA] ?? null;
            $isJefeA = false;
            if ($rolA && $rolA->id_jefe) {
                $isJefeA = ((int) $rolA->id_jefe === (int) $a->id_empleado);
            } else {
                $isJefeA = $a->cargo && in_array($a->cargo->nombre_cargo, ['Jefe de Servicio', 'Jefe de Area']);
            }

            $rolB = $rolesByDept[$deptB] ?? null;
            $isJefeB = false;
            if ($rolB && $rolB->id_jefe) {
                $isJefeB = ((int) $rolB->id_jefe === (int) $b->id_empleado);
            } else {
                $isJefeB = $b->cargo && in_array($b->cargo->nombre_cargo, ['Jefe de Servicio', 'Jefe de Area']);
            }

            if ($isJefeA !== $isJefeB) {
                return $isJefeA ? -1 : 1;
            }

            return strcmp($a->nombre_completo, $b->nombre_completo);
        })->values();

        $turnosById = Turno::all()->keyBy('id_turno');
        $turnos = Turno::all();

        $matrix = [];
        foreach ($empleados as $emp) {
            $shifts = array_fill(1, 31, 'L');

            if (isset($todasAsignaciones[$emp->id_empleado])) {
                foreach ($todasAsignaciones[$emp->id_empleado] as $asig) {
                    $dia = (int) date('d', strtotime($asig->fecha));
                    if (isset($turnosById[$asig->id_turno])) {
                        $shifts[$dia] = $turnosById[$asig->id_turno]->codigo_turno;
                    }
                }
            }

            $deptId = (int) ($empToRoleDeptMap[$emp->id_empleado]['id'] ?? $emp->id_departamento);
            $deptName = $empToRoleDeptMap[$emp->id_empleado]['name'] ?? ($emp->departamento ? $emp->departamento->nombre_departmento : 'Administración / Sin Área');

            $rolDept = $rolesByDept[$deptId] ?? null;
            $isJefe = false;
            if ($rolDept && $rolDept->id_jefe) {
                $isJefe = ((int) $rolDept->id_jefe === (int) $emp->id_empleado);
            } else {
                $isJefe = $emp->cargo && in_array($emp->cargo->nombre_cargo, ['Jefe de Servicio', 'Jefe de Area']);
            }

            $empAsig = isset($todasAsignaciones[$emp->id_empleado])
                ? $todasAsignaciones[$emp->id_empleado]->first(fn ($a) => $a->id_fuente !== null)
                : null;
            $ff = $empAsig && $empAsig->fuente ? $empAsig->fuente->codigo_fuente : 'TGN';

            $matrix[] = [
                'item' => $emp->item_id,
                'codigo_biometrico' => $emp->codigo_biometrico,
                'ff' => $ff,
                'nombre' => $emp->nombre_completo,
                'cargo' => $isJefe ? 'Jefe de Area' : ($emp->cargo ? $emp->cargo->nombre_cargo : ''),
                'departamento' => $deptName,
                'is_jefe' => $isJefe,
                'shifts' => $shifts,
            ];
        }

        $mesesStr = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];

        return view('reporte', [
            'matrix' => $matrix,
            'mes' => $mesesStr[(int) $mes] ?? $mes,
            'anio' => $anio,
            'departamento' => $departamento ? ($departamento->nombre_departmento ?? $departamento->nombre_departamento) : 'TODOS LOS SERVICIOS',
            'turnos' => $turnos,
        ]);
    }
}
