<?php

namespace App\Http\Controllers;

use App\Models\AsignacionTurnoDiario;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function getEmpleados(Request $request)
    {
        $mes = (int) $request->input('mes', 10);
        $anio = (int) $request->input('anio', 2026);
        $departamentoId = $request->input('departamento_id');

        // 1. Buscar roles mensuales existentes para este período
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
            $asignacionesCollection = AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)->get();
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

        // 2. Armar query de empleados respetando el historial del rol guardado
        $query = Empleado::with(['cargo', 'departamento']);

        // Excluir al Administrador de Sistema (el Super Admin es administrador del sistema, no tiene turnos hospitalarios ni empleados a cargo)
        $query->whereHas('cargo', function ($q) {
            $q->where('nombre_cargo', '!=', 'Administrador de Sistema');
        });

        if ($departamentoId && $departamentoId !== 'todos') {
            // Jefes de este departamento
            $jefesDeptIds = Empleado::where('id_departamento', $departamentoId)
                ->whereHas('cargo', function ($q) {
                    $q->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                })
                ->pluck('id_empleado')
                ->toArray();

            if (empty($jefesDeptIds)) {
                $primerEmpId = Empleado::where('id_departamento', $departamentoId)->orderBy('id_empleado')->value('id_empleado');
                if ($primerEmpId) {
                    $jefesDeptIds = [$primerEmpId];
                }
            }

            // Si este departamento YA TIENE un rol guardado con personal para este mes/año:
            if (isset($empleadosAsignadosPorDept[$departamentoId])) {
                // EL JEFE SIEMPRE DEBE ESTAR PRESENTE SIN IMPORTAR, SEGUIDO POR LOS EMPLEADOS
                $empIdsFinales = array_unique(array_merge($jefesDeptIds, $empleadosAsignadosPorDept[$departamentoId]));
                $query->whereIn('id_empleado', $empIdsFinales);
            } else {
                // Si el rol aún no existe para este período: comenzar desde cero con solo el Jefe
                if (! empty($jefesDeptIds)) {
                    $query->whereIn('id_empleado', $jefesDeptIds);
                } else {
                    $query->where('id_departamento', $departamentoId);
                }
            }
        } else {
            // Caso "todos los departamentos":
            // Obtener TODOS los jefes de todos los departamentos
            $todosJefesIds = Empleado::whereNotNull('id_departamento')
                ->whereHas('cargo', function ($qc) {
                    $qc->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                })
                ->pluck('id_empleado')
                ->toArray();

            $deptsConRol = array_keys($empleadosAsignadosPorDept);
            if (count($deptsConRol) > 0) {
                // El personal asignado + siempre todos los jefes de área sin importar
                $empIdsConJefes = array_unique(array_merge($todosEmpIdsAsignados, $todosJefesIds));
                $query->where(function ($q) use ($deptsConRol, $empIdsConJefes) {
                    $q->whereIn('id_empleado', $empIdsConJefes)
                        ->orWhere(function ($q2) use ($deptsConRol) {
                            $q2->whereNotNull('id_departamento')
                                ->whereNotIn('id_departamento', $deptsConRol)
                                ->whereHas('cargo', function ($qc) {
                                    $qc->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                                });
                        });
                });
            } else {
                // Si ningún departamento tiene rol registrado: solo los Jefes de cada departamento
                $query->whereNotNull('id_departamento')
                    ->whereHas('cargo', function ($qc) {
                        $qc->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                    });
            }
        }

        // Ordenar por Departamento/Área, y dentro de cada Área el Jefe primero y abajo sus empleados
        $empleados = $query->get()->sort(function ($a, $b) use ($empToRoleDeptMap) {
            $deptA = $empToRoleDeptMap[$a->id_empleado]['id'] ?? ($a->id_departamento ?? 999999);
            $deptB = $empToRoleDeptMap[$b->id_empleado]['id'] ?? ($b->id_departamento ?? 999999);
            if ($deptA !== $deptB) {
                return $deptA <=> $deptB;
            }

            // Jerarquía de cargo: Jefes de Servicio y Jefes de Área arriba
            $rankA = 0;
            if ($a->cargo) {
                if ($a->cargo->nombre_cargo === 'Jefe de Servicio') {
                    $rankA = 2;
                } elseif ($a->cargo->nombre_cargo === 'Jefe de Area') {
                    $rankA = 1;
                }
            }

            $rankB = 0;
            if ($b->cargo) {
                if ($b->cargo->nombre_cargo === 'Jefe de Servicio') {
                    $rankB = 2;
                } elseif ($b->cargo->nombre_cargo === 'Jefe de Area') {
                    $rankB = 1;
                }
            }

            if ($rankA !== $rankB) {
                return $rankB <=> $rankA; // Mayor rango (Jefe) primero
            }

            return strcmp($a->nombre_completo, $b->nombre_completo);
        })->values();

        $turnosById = Turno::all()->keyBy('id_turno');

        $startDate = sprintf('%04d-%02d-01', $anio, $mes);
        $endDate = date('Y-m-t', strtotime($startDate));

        $allActiveAssignments = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno'])
            ->whereBetween('fecha', [$startDate, $endDate])
            ->whereIn('id_empleado', $empleados->pluck('id_empleado'))
            ->whereHas('turno', function ($qt) {
                $qt->where('codigo_turno', '!=', 'L');
            })
            ->get()
            ->groupBy('id_empleado');

        return $empleados->map(function ($emp) use ($todasAsignaciones, $turnosById, $empToRoleDeptMap, $allActiveAssignments, $departamentoId) {
            $shifts = array_fill(0, 31, 'L');

            if (isset($todasAsignaciones[$emp->id_empleado])) {
                foreach ($todasAsignaciones[$emp->id_empleado] as $asig) {
                    $dia = (int) date('d', strtotime($asig->fecha)) - 1;
                    if ($dia >= 0 && $dia < 31 && isset($turnosById[$asig->id_turno])) {
                        $code = $turnosById[$asig->id_turno]->codigo_turno;
                        if ($shifts[$dia] === 'L' || $code !== 'L') {
                            $shifts[$dia] = $code;
                        }
                    }
                }
            }

            $deptId = $empToRoleDeptMap[$emp->id_empleado]['id'] ?? $emp->id_departamento;
            $deptName = $empToRoleDeptMap[$emp->id_empleado]['name'] ?? ($emp->departamento ? $emp->departamento->nombre_departmento : 'Sin Área Asignada');

            // El departamento de referencia de este empleado en la vista actual
            $currentDeptId = ($departamentoId && $departamentoId !== 'todos') ? (int) $departamentoId : (int) $deptId;

            $externalShifts = array_fill(0, 31, null);
            if (isset($allActiveAssignments[$emp->id_empleado])) {
                foreach ($allActiveAssignments[$emp->id_empleado] as $extAsig) {
                    $asigDeptId = $extAsig->rolMensual ? (int) $extAsig->rolMensual->id_departamento : null;
                    // Cualquier turno activo en OTRO departamento es externo
                    if ($asigDeptId !== null && $asigDeptId !== $currentDeptId) {
                        $dia = (int) date('d', strtotime($extAsig->fecha)) - 1;
                        if ($dia >= 0 && $dia < 31 && $extAsig->turno && $extAsig->turno->codigo_turno !== 'L') {
                            $extDeptName = $extAsig->rolMensual && $extAsig->rolMensual->departamento
                                ? $extAsig->rolMensual->departamento->nombre_departmento
                                : 'Otra área';
                            $externalShifts[$dia] = [
                                'dept' => $extDeptName,
                                'code' => $extAsig->turno->codigo_turno,
                                'name' => $extAsig->turno->nombre_turno,
                            ];
                        }
                    }
                }
            }

            $isJefe = $emp->cargo && in_array($emp->cargo->nombre_cargo, ['Jefe de Servicio', 'Jefe de Area']);

            return [
                'id' => $emp->id_empleado,
                'no' => $emp->id_empleado,
                'ff' => 'TGN',
                'item' => $emp->item_id,
                'bio' => $emp->codigo_biometrico,
                'phone' => $emp->telefono,
                'name' => $emp->nombre_completo,
                'role' => $emp->cargo ? $emp->cargo->nombre_cargo : 'Sin cargo',
                'dept_id' => $deptId,
                'dept_name' => $deptName,
                'is_jefe' => $isJefe,
                'shifts' => $shifts,
                'external_shifts' => $externalShifts,
            ];
        });
    }

    private function shiftsOverlap(string $codeA, string $codeB): bool
    {
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
    }

    public function getAllEmpleados(Request $request)
    {
        $mes = $request->filled('mes') ? (int) $request->input('mes') : null;
        $anio = $request->filled('anio') ? (int) $request->input('anio') : null;
        $deptId = $request->input('departamento_id');

        $empleados = Empleado::with(['cargo', 'departamento'])
            ->whereHas('cargo', function ($q) {
                $q->where('nombre_cargo', '!=', 'Administrador de Sistema');
            })
            ->get();

        $externalByEmp = [];
        if ($mes && $anio) {
            $startDate = sprintf('%04d-%02d-01', $anio, $mes);
            $endDate = date('Y-m-t', strtotime($startDate));

            $allActive = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno'])
                ->whereBetween('fecha', [$startDate, $endDate])
                ->whereHas('turno', function ($qt) {
                    $qt->where('codigo_turno', '!=', 'L');
                })
                ->get()
                ->groupBy('id_empleado');

            foreach ($empleados as $emp) {
                $targetDept = ($deptId && $deptId !== 'todos') ? (int) $deptId : null;
                $ext = array_fill(0, 31, null);

                if (isset($allActive[$emp->id_empleado])) {
                    foreach ($allActive[$emp->id_empleado] as $a) {
                        $aDeptId = $a->rolMensual ? (int) $a->rolMensual->id_departamento : null;
                        $isExternal = false;
                        if ($targetDept !== null) {
                            $isExternal = ($aDeptId !== null && $aDeptId !== $targetDept);
                        } else {
                            $isExternal = ($aDeptId !== null);
                        }

                        if ($isExternal) {
                            $dia = (int) date('d', strtotime($a->fecha)) - 1;
                            if ($dia >= 0 && $dia < 31 && $a->turno && $a->turno->codigo_turno !== 'L') {
                                $ext[$dia] = [
                                    'dept' => $a->rolMensual?->departamento?->nombre_departmento ?? 'Otra área',
                                    'code' => $a->turno->codigo_turno,
                                    'name' => $a->turno->nombre_turno,
                                ];
                            }
                        }
                    }
                }
                $externalByEmp[$emp->id_empleado] = $ext;
            }
        }

        return $empleados->map(function ($emp) use ($externalByEmp) {
            $data = $emp->toArray();
            $data['external_shifts'] = $externalByEmp[$emp->id_empleado] ?? array_fill(0, 31, null);

            return $data;
        });
    }

    public function saveRole(Request $request)
    {
        $mesStr = $request->input('month');
        $anio = (int) $request->input('year');
        $people = $request->input('people');
        $serviceId = $request->input('serviceId');

        $meses = ['Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6, 'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12];
        $mes = $meses[$mesStr] ?? (int) $mesStr;
        if (! $mes) {
            $mes = 10;
        }

        if (empty($people)) {
            return response()->json(['success' => true]);
        }

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

            // Si se pasa un servicio/departamento específico, ese es el departamento del rol
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

        // 1. VALIDACIÓN INTERNA: Verificar que dentro del mismo guardado ningún empleado tenga turnos superpuestos entre áreas distintas
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
                if ($prev['dept_id'] !== $ins['dept_id'] && $this->shiftsOverlap($ins['shift_code'], $prev['shift_code'])) {
                    $empName = $empleadosDb[$ins['id_empleado']]->nombre_completo ?? "ID {$ins['id_empleado']}";
                    $dia = (int) date('d', strtotime($ins['fecha']));

                    return response()->json([
                        'success' => false,
                        'error' => "Conflicto de horario: El funcionario {$empName} no puede tener turnos en el mismo horario el día {$dia} entre los servicios \"{$prev['dept_name']}\" ({$prev['shift_code']}) y \"{$ins['dept_name']}\" ({$ins['shift_code']}).",
                    ], 422);
                }
            }
            $activeByEmpDate[$key][] = $ins;
        }

        // 2. VALIDACIÓN EXTERNA: Verificar contra asignaciones activas existentes en la base de datos de otros servicios/roles
        if (count($inserts) > 0) {
            $empleadosIds = collect($inserts)->pluck('id_empleado')->unique()->toArray();

            $startDate = sprintf('%04d-%02d-01', $anio, $mes);
            $endDate = date('Y-m-t', strtotime($startDate));

            $existingAssignments = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno', 'empleado'])
                ->whereIn('id_empleado', $empleadosIds)
                ->whereBetween('fecha', [$startDate, $endDate])
                ->whereHas('turno', function ($qt) {
                    $qt->where('codigo_turno', '!=', 'L');
                })
                ->get()
                ->groupBy(function ($item) {
                    return $item->id_empleado.'_'.$item->fecha;
                });

            foreach ($inserts as $ins) {
                if ($ins['shift_code'] === 'L') {
                    continue;
                }

                $key = $ins['id_empleado'].'_'.$ins['fecha'];
                if (isset($existingAssignments[$key])) {
                    foreach ($existingAssignments[$key] as $conflict) {
                        $conflictDeptId = $conflict->rolMensual ? (int) $conflict->rolMensual->id_departamento : null;
                        // Si pertenece a OTRO departamento diferente al que se está guardando para este insert:
                        if ($conflictDeptId !== null && $conflictDeptId !== (int) $ins['dept_id']) {
                            $extCode = $conflict->turno ? $conflict->turno->codigo_turno : 'L';
                            if ($this->shiftsOverlap($ins['shift_code'], $extCode)) {
                                $empName = $conflict->empleado ? $conflict->empleado->nombre_completo : "ID {$ins['id_empleado']}";
                                $deptName = $conflict->rolMensual && $conflict->rolMensual->departamento
                                    ? $conflict->rolMensual->departamento->nombre_departmento
                                    : 'otra área';
                                $dia = (int) date('d', strtotime($ins['fecha']));
                                $extTurnoName = $conflict->turno ? $conflict->turno->nombre_turno : $extCode;

                                return response()->json([
                                    'success' => false,
                                    'error' => "Conflicto de horario: El funcionario {$empName} ya tiene programado el turno {$extCode} ({$extTurnoName}) en el área \"{$deptName}\" el día {$dia}. No puede tener turnos en el mismo horario con más de un jefe.",
                                ], 422);
                            }
                        }
                    }
                }
            }

            // Eliminar ÚNICAMENTE las asignaciones anteriores de este departamento específico para los empleados que se están guardando en él
            foreach ($rolMensuales as $deptKey => $rolMensual) {
                $deptIdNum = (int) $deptKey;
                $empsInThisDept = collect($inserts)
                    ->where('dept_id', $deptIdNum)
                    ->pluck('id_empleado')
                    ->unique()
                    ->toArray();

                if (! empty($empsInThisDept)) {
                    AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual)
                        ->whereIn('id_empleado', $empsInThisDept)
                        ->delete();
                }
            }
        }

        // Limpiar campos temporales antes de insertar
        $cleanInserts = array_map(function ($item) {
            unset($item['shift_code'], $item['dept_id'], $item['dept_name']);

            return $item;
        }, $inserts);

        // Batch insert para mejorar rendimiento
        foreach (array_chunk($cleanInserts, 100) as $chunk) {
            AsignacionTurnoDiario::insert($chunk);
        }

        return response()->json(['success' => true]);
    }

    public function history(Request $request)
    {
        $deptId = $request->input('departamento_id');
        $query = RolMensual::with('departamento')
            ->select('id_rol_mensual', 'mes', 'anio', 'id_departamento')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc');

        if ($deptId && $deptId !== 'todos') {
            if ($deptId === 'null') {
                $query->whereNull('id_departamento');
            } else {
                $query->where('id_departamento', $deptId);
            }
        }

        $roles = $query->get();

        $mesesNombres = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];

        $result = collect();

        // Para consultas globales o de Administrador: incluir entrada de Rol Consolidado para cada período con roles
        if (! $deptId || $deptId === 'todos') {
            $periodos = $roles->map(function ($r) {
                return $r->anio.'_'.$r->mes;
            })->unique();

            foreach ($periodos as $p) {
                [$anio, $mes] = explode('_', $p);
                $anio = (int) $anio;
                $mes = (int) $mes;

                $result->push([
                    'id' => 'consolidado_'.$mes.'_'.$anio,
                    'mes' => $mes,
                    'mesNombre' => $mesesNombres[$mes] ?? 'Desconocido',
                    'anio' => $anio,
                    'departamento_id' => 'todos',
                    'departamento_nombre' => 'Administración (Global - Todos los departamentos)',
                    'is_consolidado' => true,
                ]);
            }
        }

        foreach ($roles as $r) {
            $deptNombre = $r->departamento ? $r->departamento->nombre_departmento : 'Personal Sin Área';
            $result->push([
                'id' => $r->id_rol_mensual,
                'mes' => $r->mes,
                'mesNombre' => $mesesNombres[$r->mes] ?? 'Desconocido',
                'anio' => $r->anio,
                'departamento_id' => $r->id_departamento ?? 'null',
                'departamento_nombre' => $deptNombre,
                'is_consolidado' => false,
            ]);
        }

        return $result;
    }

    public function getUnassigned(Request $request)
    {
        $mes = $request->filled('mes') ? (int) $request->input('mes') : null;
        $anio = $request->filled('anio') ? (int) $request->input('anio') : null;

        $rolesMensuales = collect();
        if ($mes && $anio) {
            $rolesMensuales = RolMensual::with('departamento')
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->get();
        }

        $rolesIds = $rolesMensuales->pluck('id_rol_mensual')->toArray();
        $empDeptsMap = []; // id_empleado => [ [ 'dept_id' => ..., 'dept_name' => ... ], ... ]

        if (count($rolesIds) > 0) {
            $asignaciones = AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)->get();
            foreach ($rolesMensuales as $rol) {
                $eIds = $asignaciones->where('id_rol_mensual', $rol->id_rol_mensual)
                    ->pluck('id_empleado')
                    ->unique();
                foreach ($eIds as $eId) {
                    if (! isset($empDeptsMap[$eId])) {
                        $empDeptsMap[$eId] = [];
                    }
                    $empDeptsMap[$eId][] = [
                        'dept_id' => (int) $rol->id_departamento,
                        'dept_name' => $rol->departamento ? $rol->departamento->nombre_departmento : 'Sin Área',
                    ];
                }
            }
        }

        // Fetch all employees EXCEPT those whose cargo is Administrador de Sistema
        $empleados = Empleado::with(['cargo', 'departamento'])
            ->whereHas('cargo', function ($q) {
                $q->where('nombre_cargo', '!=', 'Administrador de Sistema');
            })
            ->get();

        return $empleados->map(function ($emp) use ($empDeptsMap) {
            $baseDeptId = $emp->id_departamento ? (int) $emp->id_departamento : null;
            $baseDeptName = $emp->departamento ? $emp->departamento->nombre_departmento : 'Sin Área';

            $assignedDepts = $empDeptsMap[$emp->id_empleado] ?? [];
            $assignedDeptIds = collect($assignedDepts)->pluck('dept_id')->unique()->values()->toArray();
            $assignedDeptNames = collect($assignedDepts)->pluck('dept_name')->unique()->values()->toArray();

            if (! empty($assignedDeptNames)) {
                $deptName = implode(' / ', $assignedDeptNames);
                $deptId = $assignedDeptIds[0];
            } else {
                $deptName = $baseDeptName;
                $deptId = $baseDeptId;
            }

            $isJefe = $emp->cargo && in_array($emp->cargo->nombre_cargo, ['Jefe de Servicio', 'Jefe de Area']);

            return [
                'id' => $emp->id_empleado,
                'bio' => $emp->codigo_biometrico,
                'name' => $emp->nombre_completo,
                'role' => $emp->cargo ? $emp->cargo->nombre_cargo : 'Sin cargo',
                'dept' => $deptName,
                'dept_id' => $deptId,
                'base_dept_id' => $baseDeptId,
                'base_dept_name' => $baseDeptName,
                'assigned_dept_ids' => $assignedDeptIds,
                'assigned_dept_names' => $assignedDeptNames,
                'is_jefe' => $isJefe,
            ];
        });
    }

    public function assignEmployee(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'department_id' => 'required|integer',
        ]);

        $employeeId = (int) $request->employee_id;
        $deptId = (int) $request->department_id;
        $mes = $request->filled('mes') ? (int) $request->mes : null;
        $anio = $request->filled('anio') ? (int) $request->anio : null;

        $emp = Empleado::find($employeeId);
        if (! $emp) {
            return response()->json(['success' => false, 'error' => 'Empleado no encontrado'], 404);
        }

        if ($mes && $anio) {
            // Asignar en el rol mensual del departamento especificado para este mes
            // UN EMPLEADO PUEDE ESTAR CON MÁS DE UN JEFE / ÁREA (no se borran sus asignaciones en otras áreas)
            $rol = RolMensual::firstOrCreate([
                'mes' => $mes,
                'anio' => $anio,
                'id_departamento' => $deptId,
            ]);

            // Insertar turnos diarios en Libre ('L') en este rol si aún no existen
            $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
            $turnoLibre = Turno::where('codigo_turno', 'L')->first();
            $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

            // Asegurar que el Jefe de este departamento SIEMPRE esté en el rol
            $jefesDeptIds = Empleado::where('id_departamento', $deptId)
                ->whereHas('cargo', function ($q) {
                    $q->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                })
                ->pluck('id_empleado')
                ->toArray();

            if (empty($jefesDeptIds)) {
                $primerEmpId = Empleado::where('id_departamento', $deptId)->orderBy('id_empleado')->value('id_empleado');
                if ($primerEmpId) {
                    $jefesDeptIds = [$primerEmpId];
                }
            }

            foreach ($jefesDeptIds as $jefeId) {
                $jefeYaExiste = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                    ->where('id_empleado', $jefeId)
                    ->exists();

                if (! $jefeYaExiste) {
                    $insertsJefe = [];
                    for ($d = 1; $d <= $diasEnMes; $d++) {
                        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                        $insertsJefe[] = [
                            'id_rol_mensual' => $rol->id_rol_mensual,
                            'id_empleado' => $jefeId,
                            'id_fuente' => null,
                            'fecha' => $fecha,
                            'id_turno' => $idTurnoLibre,
                        ];
                    }
                    AsignacionTurnoDiario::insert($insertsJefe);
                }
            }

            $yaExiste = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                ->where('id_empleado', $employeeId)
                ->exists();

            if (! $yaExiste) {
                $inserts = [];
                for ($d = 1; $d <= $diasEnMes; $d++) {
                    $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                    $inserts[] = [
                        'id_rol_mensual' => $rol->id_rol_mensual,
                        'id_empleado' => $employeeId,
                        'id_fuente' => null,
                        'fecha' => $fecha,
                        'id_turno' => $idTurnoLibre,
                    ];
                }
                AsignacionTurnoDiario::insert($inserts);
            }
        } else {
            // Asignación permanente de departamento base
            $emp->id_departamento = $deptId;
            $emp->save();
        }

        return response()->json(['success' => true]);
    }

    public function removeEmployee(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $employeeId = (int) $request->employee_id;
        $deptId = $request->input('department_id');
        $mes = $request->filled('mes') ? (int) $request->mes : null;
        $anio = $request->filled('anio') ? (int) $request->anio : null;

        $emp = Empleado::find($employeeId);
        // El Jefe no se puede remover de su área
        if ($emp && $emp->cargo && in_array($emp->cargo->nombre_cargo, ['Jefe de Area', 'Jefe de Servicio'])) {
            return response()->json(['success' => false, 'error' => 'No se puede remover al Jefe del departamento'], 422);
        }

        if ($mes && $anio) {
            // Eliminar asignaciones de este empleado ÚNICAMENTE en el rol del departamento seleccionado
            $query = RolMensual::where('mes', $mes)->where('anio', $anio);
            if ($deptId && $deptId !== 'todos') {
                $query->where('id_departamento', (int) $deptId);
            }
            $rolesIds = $query->pluck('id_rol_mensual');

            if ($rolesIds->count() > 0) {
                AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)
                    ->where('id_empleado', $employeeId)
                    ->delete();
            }
        } else {
            if ($emp) {
                $emp->id_departamento = null;
                $emp->save();
            }
        }

        return response()->json(['success' => true]);
    }

    public function copyMonthAssignments(Request $request)
    {
        $mes = (int) $request->input('mes');
        $anio = (int) $request->input('anio');
        $deptId = $request->input('department_id');

        $prevMes = $mes === 1 ? 12 : $mes - 1;
        $prevAnio = $mes === 1 ? $anio - 1 : $anio;

        $mesesNombres = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
        $prevNombre = $mesesNombres[$prevMes] ?? 'Anterior';

        // Buscar rol del mes anterior
        $queryPrev = RolMensual::where('mes', $prevMes)
            ->where('anio', $prevAnio);

        if ($deptId && $deptId !== 'todos') {
            $queryPrev->where('id_departamento', $deptId);
        }

        $prevRoles = $queryPrev->get();
        if ($prevRoles->isEmpty()) {
            return response()->json(['success' => false, 'error' => "No se encontraron roles guardados en el mes anterior ({$prevNombre} {$prevAnio})."], 404);
        }

        $prevRolesIds = $prevRoles->pluck('id_rol_mensual')->toArray();
        $prevAsignaciones = AsignacionTurnoDiario::whereIn('id_rol_mensual', $prevRolesIds)->get();

        if ($prevAsignaciones->isEmpty()) {
            return response()->json(['success' => false, 'error' => "El mes anterior ({$prevNombre} {$prevAnio}) no tenía empleados asignados."], 404);
        }

        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $turnoLibre = Turno::where('codigo_turno', 'L')->first();
        $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

        $copiedCount = 0;
        foreach ($prevRoles as $prevRol) {
            $rolActual = RolMensual::firstOrCreate([
                'mes' => $mes,
                'anio' => $anio,
                'id_departamento' => $prevRol->id_departamento,
            ]);

            // Asegurar que el Jefe de este departamento siempre esté presente
            $jefesDeptIds = Empleado::where('id_departamento', $prevRol->id_departamento)
                ->whereHas('cargo', function ($q) {
                    $q->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                })
                ->pluck('id_empleado')
                ->toArray();

            if (empty($jefesDeptIds)) {
                $primerEmpId = Empleado::where('id_departamento', $prevRol->id_departamento)->orderBy('id_empleado')->value('id_empleado');
                if ($primerEmpId) {
                    $jefesDeptIds = [$primerEmpId];
                }
            }

            foreach ($jefesDeptIds as $jefeId) {
                $yaExisteJefe = AsignacionTurnoDiario::where('id_rol_mensual', $rolActual->id_rol_mensual)
                    ->where('id_empleado', $jefeId)
                    ->exists();
                if (! $yaExisteJefe) {
                    $insertsJefe = [];
                    for ($d = 1; $d <= $diasEnMes; $d++) {
                        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                        $insertsJefe[] = [
                            'id_rol_mensual' => $rolActual->id_rol_mensual,
                            'id_empleado' => $jefeId,
                            'id_fuente' => null,
                            'fecha' => $fecha,
                            'id_turno' => $idTurnoLibre,
                        ];
                    }
                    AsignacionTurnoDiario::insert($insertsJefe);
                }
            }

            $empIds = $prevAsignaciones->where('id_rol_mensual', $prevRol->id_rol_mensual)
                ->pluck('id_empleado')
                ->unique();

            foreach ($empIds as $empId) {
                // Verificar si ya existe en este rol actual
                $yaExiste = AsignacionTurnoDiario::where('id_rol_mensual', $rolActual->id_rol_mensual)
                    ->where('id_empleado', $empId)
                    ->exists();

                if (! $yaExiste) {
                    $inserts = [];
                    for ($d = 1; $d <= $diasEnMes; $d++) {
                        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                        $inserts[] = [
                            'id_rol_mensual' => $rolActual->id_rol_mensual,
                            'id_empleado' => $empId,
                            'id_fuente' => null,
                            'fecha' => $fecha,
                            'id_turno' => $idTurnoLibre,
                        ];
                    }
                    AsignacionTurnoDiario::insert($inserts);
                    $copiedCount++;
                }
            }
        }

        return response()->json(['success' => true, 'count' => $copiedCount, 'prev_mes_nombre' => $prevNombre, 'prev_anio' => $prevAnio]);
    }
}
