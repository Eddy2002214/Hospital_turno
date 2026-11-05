<?php

namespace App\Http\Controllers;

use App\Models\AsignacionTurnoDiario;
use App\Models\Cargo;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\FuenteFinanciamiento;
use App\Models\RolMensual;
use App\Models\Turno;
use App\Models\User;
use App\Services\RrhhService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function getEmpleados(Request $request)
    {
        $mes = (int) $request->input('mes', 10);
        $anio = (int) $request->input('anio', 2026);
        $departamentoId = $request->input('departamento_id');

        // 1. Buscar roles mensuales existentes para este período
        $queryRoles = RolMensual::with('departamento')->where('mes', $mes)->where('anio', $anio);
        if ($departamentoId && $departamentoId !== 'todos') {
            $queryRoles->where('id_departamento', (int) $departamentoId);
        }
        $rolesMensuales = $queryRoles->get();

        if ($rolesMensuales->isEmpty()) {
            return collect();
        }

        $rolesIds = $rolesMensuales->pluck('id_rol_mensual')->toArray();
        $asignacionesCollection = AsignacionTurnoDiario::with('fuente')->whereIn('id_rol_mensual', $rolesIds)->get();

        // Obtener todos los IDs de empleados que están en estos roles
        $allEmpIds = $asignacionesCollection->pluck('id_empleado')->unique()->toArray();
        foreach ($rolesMensuales as $r) {
            if ($r->id_jefe) {
                $allEmpIds[] = (int) $r->id_jefe;
            }
        }
        $allEmpIds = array_unique(array_filter($allEmpIds));

        if (empty($allEmpIds)) {
            return collect();
        }

        // Cargar empleados de la base de datos local excluyendo al Super Admin
        $empleadosDb = Empleado::with(['cargo', 'departamento'])
            ->whereIn('id_empleado', $allEmpIds)
            ->whereHas('cargo', function ($q) {
                $q->where('nombre_cargo', '!=', 'Administrador de Sistema');
            })
            ->where('id_empleado', '!=', 999999)
            ->get()
            ->keyBy('id_empleado');

        $turnosById = Turno::all()->keyBy('id_turno');

        $startDate = sprintf('%04d-%02d-01', $anio, $mes);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Todos los turnos activos en todo el mes de estos empleados (para marcar external_shifts en ámbar)
        $allActiveAssignments = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno'])
            ->whereBetween('fecha', [$startDate, $endDate])
            ->whereIn('id_empleado', $allEmpIds)
            ->whereHas('turno', function ($qt) {
                $qt->where('codigo_turno', '!=', 'L');
            })
            ->get()
            ->groupBy('id_empleado');

        // Construir la lista de empleados asignados respetando cada departamento/rol por separado
        $items = collect();

        foreach ($rolesMensuales as $rol) {
            $deptId = (int) $rol->id_departamento;
            $deptName = $rol->departamento ? $rol->departamento->nombre_departmento : 'Área Asignada';

            // Empleados asignados a este rol mensual específico
            $empIdsInThisRole = $asignacionesCollection
                ->where('id_rol_mensual', $rol->id_rol_mensual)
                ->pluck('id_empleado')
                ->unique()
                ->toArray();

            // Incluir al jefe si no tiene turnos diarios pero está designado como jefe en el rol
            if ($rol->id_jefe && ! in_array((int) $rol->id_jefe, $empIdsInThisRole)) {
                $empIdsInThisRole[] = (int) $rol->id_jefe;
            }

            // Agrupar asignaciones de este rol por id_empleado
            $asigsByEmpInThisRole = $asignacionesCollection
                ->where('id_rol_mensual', $rol->id_rol_mensual)
                ->groupBy('id_empleado');

            foreach ($empIdsInThisRole as $empId) {
                $emp = $empleadosDb[$empId] ?? null;
                if (! $emp) {
                    continue;
                }

                $isJefe = ($rol->id_jefe && (int) $rol->id_jefe === (int) $emp->id_empleado);
                if (! $rol->id_jefe && $emp->cargo && in_array($emp->cargo->nombre_cargo, ['Jefe de Servicio', 'Jefe de Area'])) {
                    $isJefe = true;
                }

                $shifts = array_fill(0, 31, 'L');
                // Para los jefes de área, los turnos hospitalarios siempre son en cero / Libre ('L')
                if (! $isJefe && isset($asigsByEmpInThisRole[$empId])) {
                    foreach ($asigsByEmpInThisRole[$empId] as $asig) {
                        $dia = (int) date('d', strtotime($asig->fecha)) - 1;
                        if ($dia >= 0 && $dia < 31 && isset($turnosById[$asig->id_turno])) {
                            $shifts[$dia] = $turnosById[$asig->id_turno]->codigo_turno;
                        }
                    }
                }

                // Turnos externos en OTROS departamentos durante el mismo mes (para pintar punto ámbar)
                $externalShifts = array_fill(0, 31, null);
                if (isset($allActiveAssignments[$empId])) {
                    foreach ($allActiveAssignments[$empId] as $extAsig) {
                        $extDeptId = $extAsig->rolMensual ? (int) $extAsig->rolMensual->id_departamento : null;
                        if ($extDeptId !== null && $extDeptId !== $deptId) {
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

                $displayRole = $isJefe ? 'Jefe de Area' : ($emp->cargo ? $emp->cargo->nombre_cargo : 'Operador / Especialista');
                if (! $isJefe && in_array($displayRole, ['Jefe de Area', 'Jefe de Servicio'])) {
                    $displayRole = 'Operador / Especialista';
                }

                $empAsigs = $asigsByEmpInThisRole[$empId] ?? collect();
                $firstAsigWithFuente = $empAsigs->first(fn ($a) => $a->id_fuente !== null);
                $fuenteCodigo = $firstAsigWithFuente && $firstAsigWithFuente->fuente
                    ? $firstAsigWithFuente->fuente->codigo_fuente
                    : 'TGN';

                $items->push([
                    'id' => $emp->id_empleado,
                    'no' => $emp->id_empleado,
                    'ff' => $fuenteCodigo,
                    'item' => $emp->item_id,
                    'bio' => $emp->codigo_biometrico,
                    'phone' => $emp->telefono,
                    'name' => $emp->nombre_completo,
                    'role' => $displayRole,
                    'dept_id' => $deptId,
                    'dept_name' => $deptName,
                    'is_jefe' => $isJefe,
                    'shifts' => $shifts,
                    'external_shifts' => $externalShifts,
                ]);
            }
        }

        // Ordenar: Departamento primero, dentro del departamento el Jefe primero, luego por nombre
        return $items->sort(function ($a, $b) {
            $cmpDept = strcmp($a['dept_name'] ?? '', $b['dept_name'] ?? '');
            if ($cmpDept !== 0) {
                return $cmpDept;
            }

            if ($a['is_jefe'] !== $b['is_jefe']) {
                return $a['is_jefe'] ? -1 : 1;
            }

            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        })->values();
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

        $rawPersonnel = app(RrhhService::class)->getPersonalFromSqlServer();
        $localEmployees = Empleado::with(['cargo', 'departamento'])
            ->where('id_empleado', '!=', 999999)
            ->get();
        $localEmployeesById = $localEmployees->keyBy('id_empleado');
        $localEmployeesByItem = $localEmployees->filter(fn ($e) => ! empty($e->item_id))->keyBy('item_id');
        $localEmployeesByBio = $localEmployees->filter(fn ($e) => ! empty($e->codigo_biometrico))->keyBy('codigo_biometrico');

        $depts = DepartamentoServicio::all();
        $deptsByName = $depts->keyBy(fn ($d) => mb_strtolower(trim($d->nombre_departmento)));

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

            $targetDept = ($deptId && $deptId !== 'todos') ? (int) $deptId : null;

            foreach ($allActive as $empIdKey => $assignments) {
                $ext = array_fill(0, 31, null);
                foreach ($assignments as $a) {
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
                $externalByEmp[$empIdKey] = $ext;
            }
        }

        $result = [];
        foreach ($rawPersonnel as $row) {
            $empId = (int) $row['id_funcionario'];
            if ($empId === 999999) {
                continue;
            }

            $bio = $row['codigo_biometrico'] ?: (string) $empId;
            $localEmp = $localEmployeesById[$empId]
                ?? ($localEmployeesByItem[(string) $empId]
                ?? ($localEmployeesByBio[$bio] ?? null));
            $localEmpId = $localEmp ? $localEmp->id_empleado : $empId;

            $deptServicio = trim($row['departamento_servicio'] ?? '');
            $matchedDept = $deptsByName[mb_strtolower($deptServicio)] ?? null;

            $finalDeptId = $localEmp?->id_departamento ?? ($matchedDept?->id_departamento);
            $finalDeptName = $localEmp?->departamento?->nombre_departmento
                ?? ($matchedDept?->nombre_departmento ?? ($deptServicio && strcasecmp($deptServicio, 'Sin Servicio Asignado') !== 0 ? $deptServicio : null));

            $cargoName = $localEmp?->cargo?->nombre_cargo ?? ($row['cargo'] ?: 'Sin Cargo Asignado');

            $result[] = [
                'id_empleado' => $localEmpId,
                'item_id' => (string) $empId,
                'codigo_biometrico' => $bio,
                'ci' => $row['carnet_identidad'] ?: null,
                'telefono' => $row['celular'] ?: ($row['telefono_fijo'] ?: null),
                'nombre_completo' => $row['nombre_completo'],
                'profesion_especialidad' => $row['profesion_especialidad'],
                'id_departamento' => $finalDeptId,
                'departamento_servicio' => $deptServicio,
                'departamento' => $finalDeptName ? [
                    'id_departamento' => $finalDeptId,
                    'nombre_departmento' => $finalDeptName,
                ] : null,
                'id_cargo' => $localEmp?->id_cargo,
                'cargo' => [
                    'id_cargo' => $localEmp?->id_cargo,
                    'nombre_cargo' => $cargoName,
                ],
                'external_shifts' => $externalByEmp[$localEmpId] ?? ($externalByEmp[$empId] ?? array_fill(0, 31, null)),
            ];
        }

        return response()->json($result);
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

        $rrhhService = app(RrhhService::class);
        foreach ($people as $person) {
            $pId = (int) $person['id'];
            if ($pId === 999999) {
                continue;
            }
            $pDeptId = ($serviceId && $serviceId !== 'todos')
                ? (int) $serviceId
                : ($person['dept_id'] ?? null);
            $rrhhService->ensureEmployeeInLocal($pId, $pDeptId ? (int) $pDeptId : null);
        }

        $ids = collect($people)->pluck('id')->map(fn ($v) => (int) $v)->filter()->unique()->values()->toArray();
        $empleadosDb = Empleado::with(['cargo', 'departamento'])
            ->whereIn('id_empleado', $ids)
            ->get();

        $empMap = $empleadosDb->keyBy('id_empleado');

        $turnos = Turno::all()->keyBy('codigo_turno');
        $deptsDb = DepartamentoServicio::all()->keyBy('id_departamento');
        $fuentesByCode = FuenteFinanciamiento::all()->keyBy('codigo_fuente');

        // Deduplicar filas si un mismo funcionario viene repetido para la misma área
        $uniquePeople = collect($people)->unique(function ($p) use ($serviceId) {
            $dId = ($serviceId && $serviceId !== 'todos') ? $serviceId : ($p['dept_id'] ?? 'nodept');

            return $p['id'].'_'.$dId;
        })->values()->toArray();

        $rolMensuales = [];
        $inserts = [];

        foreach ($uniquePeople as $person) {
            $empDb = $empMap[$person['id']] ?? null;
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
                $rolMensual = RolMensual::firstOrCreate([
                    'mes' => $mes,
                    'anio' => $anio,
                    'id_departamento' => $deptId,
                ]);

                // Asignar id_jefe al rol mensual si no lo tiene
                if (! $rolMensual->id_jefe) {
                    $jefeInDept = collect($people)->first(function ($p) use ($deptId) {
                        $pDept = $p['dept_id'] ?? null;

                        return (int) $pDept === (int) $deptId && ! empty($p['is_jefe']);
                    });

                    if ($jefeInDept) {
                        $rolMensual->id_jefe = (int) $jefeInDept['id'];
                        $rolMensual->save();
                    } else {
                        $activeJefeId = Empleado::where('id_departamento', $deptId)
                            ->whereHas('cargo', function ($q) {
                                $q->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
                            })
                            ->value('id_empleado');
                        if ($activeJefeId) {
                            $rolMensual->id_jefe = $activeJefeId;
                            $rolMensual->save();
                        }
                    }
                }

                $rolMensuales[$deptKey] = $rolMensual;
            }
            $rolMensual = $rolMensuales[$deptKey];

            // Si en el payload se especificó un jefe explícito para este departamento, sincronizar id_jefe
            $jefePerson = collect($people)->first(function ($p) use ($deptId) {
                $pDept = $p['dept_id'] ?? null;

                return (int) $pDept === (int) $deptId && ! empty($p['is_jefe']);
            });
            if ($jefePerson && (int) $rolMensual->id_jefe !== (int) $jefePerson['id']) {
                $rolMensual->id_jefe = (int) $jefePerson['id'];
                $rolMensual->save();
            }

            $deptName = $person['dept_name'] ?? ($deptsDb[$deptId]->nombre_departmento ?? 'Área Asignada');

            $isJefe = ! empty($person['is_jefe']) || ((int) $rolMensual->id_jefe === (int) $empDb->id_empleado);

            $personFf = trim($person['ff'] ?? 'TGN');
            $fuenteModel = $fuentesByCode[$personFf] ?? ($fuentesByCode['TGN'] ?? null);
            $fuenteId = $fuenteModel ? $fuenteModel->id_fuente : 1;

            foreach ($person['shifts'] as $index => $shiftCode) {
                $dia = $index + 1;
                if (! checkdate($mes, $dia, $anio)) {
                    continue;
                }

                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                // Para los Jefes de Área, los turnos se registran siempre en cero / Libre ('L')
                $finalCode = $isJefe ? 'L' : $shiftCode;
                $turno = $turnos[$finalCode] ?? null;

                if ($turno) {
                    $inserts[] = [
                        'id_rol_mensual' => $rolMensual->id_rol_mensual,
                        'id_empleado' => $empDb->id_empleado,
                        'id_fuente' => $fuenteId,
                        'fecha' => $fecha,
                        'id_turno' => $turno->id_turno,
                        'shift_code' => $finalCode,
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

            // Sincronizar asignaciones de turnos para cada departamento guardado:
            foreach ($rolMensuales as $deptKey => $rolMensual) {
                $deptIdNum = (int) $deptKey;
                $empsInThisDept = collect($inserts)
                    ->where('dept_id', $deptIdNum)
                    ->pluck('id_empleado')
                    ->unique()
                    ->toArray();

                // 1. Eliminar asignaciones anteriores de los empleados que se están guardando (para reinsertar sus turnos actualizados)
                if (! empty($empsInThisDept)) {
                    AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual)
                        ->whereIn('id_empleado', $empsInThisDept)
                        ->delete();
                }

                // 2. Eliminar a los empleados que estaban antes en este rol mensual pero fueron QUITADOS de la lista
                // (Se preserva al Jefe del mes para no desvincular la jefatura)
                $queryRemoved = AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual);
                if (! empty($empsInThisDept)) {
                    $queryRemoved->whereNotIn('id_empleado', $empsInThisDept);
                }
                if ($rolMensual->id_jefe) {
                    $queryRemoved->where('id_empleado', '!=', $rolMensual->id_jefe);
                }
                $queryRemoved->delete();
            }

            // Si se guarda "Todos los departamentos", purgar roles huérfanos que ya no pertenezcan a las áreas enviadas
            if (! $serviceId || $serviceId === 'todos') {
                $savedDeptIds = collect($rolMensuales)
                    ->pluck('id_departamento')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->toArray();

                $orphanedRoles = RolMensual::where('mes', $mes)
                    ->where('anio', $anio)
                    ->whereNotIn('id_departamento', $savedDeptIds)
                    ->get();

                if ($orphanedRoles->isNotEmpty()) {
                    $orphanedIds = $orphanedRoles->pluck('id_rol_mensual')->toArray();
                    AsignacionTurnoDiario::whereIn('id_rol_mensual', $orphanedIds)->delete();
                    RolMensual::whereIn('id_rol_mensual', $orphanedIds)->delete();
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
        $jefeIdsThisMonth = $rolesMensuales->pluck('id_jefe')->filter()->map(fn ($id) => (int) $id)->toArray();
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

        $rawPersonnel = app(RrhhService::class)->getPersonalFromSqlServer();
        $localEmployees = Empleado::with(['cargo', 'departamento'])
            ->where('id_empleado', '!=', 999999)
            ->get();
        $localEmployeesById = $localEmployees->keyBy('id_empleado');
        $localEmployeesByItem = $localEmployees->filter(fn ($e) => ! empty($e->item_id))->keyBy('item_id');
        $localEmployeesByBio = $localEmployees->filter(fn ($e) => ! empty($e->codigo_biometrico))->keyBy('codigo_biometrico');
        $depts = DepartamentoServicio::all();
        $deptsByName = $depts->keyBy(fn ($d) => mb_strtolower(trim($d->nombre_departmento)));

        $list = [];
        foreach ($rawPersonnel as $row) {
            $empId = (int) $row['id_funcionario'];
            if ($empId === 999999) {
                continue;
            }

            $bio = $row['codigo_biometrico'] ?: (string) $empId;
            $localEmp = $localEmployeesById[$empId]
                ?? ($localEmployeesByItem[(string) $empId]
                ?? ($localEmployeesByBio[$bio] ?? null));
            $localEmpId = $localEmp ? $localEmp->id_empleado : $empId;
            $deptServicio = trim($row['departamento_servicio'] ?? '');
            $matchedDept = $deptsByName[mb_strtolower($deptServicio)] ?? null;

            if ($localEmp && $localEmp->departamento) {
                $baseDeptId = (int) $localEmp->id_departamento;
                $baseDeptName = $localEmp->departamento->nombre_departmento;
            } elseif ($matchedDept) {
                $baseDeptId = (int) $matchedDept->id_departamento;
                $baseDeptName = $matchedDept->nombre_departmento;
            } else {
                $baseDeptId = null;
                $baseDeptName = ($deptServicio && strcasecmp($deptServicio, 'Sin Servicio Asignado') !== 0) ? $deptServicio : 'Sin Área';
            }

            $assignedDepts = $empDeptsMap[$localEmpId] ?? ($empDeptsMap[$empId] ?? []);
            $assignedDeptIds = collect($assignedDepts)->pluck('dept_id')->unique()->values()->toArray();
            $assignedDeptNames = collect($assignedDepts)->pluck('dept_name')->unique()->values()->toArray();

            if (! empty($assignedDeptNames)) {
                $deptName = implode(' / ', $assignedDeptNames);
                $deptId = $assignedDeptIds[0];
            } else {
                $deptName = $baseDeptName;
                $deptId = $baseDeptId;
            }

            $roleName = $localEmp?->cargo?->nombre_cargo ?? ($row['cargo'] ?: 'Sin cargo');
            $isJefe = in_array((int) $localEmpId, $jefeIdsThisMonth);
            $displayRole = $isJefe ? 'Jefe de Area' : ($roleName ?: 'Operador / Especialista');
            if (! $isJefe && in_array($displayRole, ['Jefe de Servicio', 'Jefe de Area'])) {
                $displayRole = 'Operador / Especialista';
            }

            $list[] = [
                'id' => $empId,
                'bio' => $row['codigo_biometrico'] ?: (string) $empId,
                'name' => $row['nombre_completo'],
                'role' => $displayRole,
                'dept' => $deptName,
                'dept_id' => $deptId,
                'base_dept_id' => $baseDeptId,
                'base_dept_name' => $baseDeptName,
                'assigned_dept_ids' => $assignedDeptIds,
                'assigned_dept_names' => $assignedDeptNames,
                'is_jefe' => $isJefe,
            ];
        }

        return response()->json($list);
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

        $emp = app(RrhhService::class)->ensureEmployeeInLocal($employeeId, $deptId);
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

            if (! $rol->id_jefe && ! empty($jefesDeptIds)) {
                $rol->id_jefe = $jefesDeptIds[0];
                $rol->save();
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
                ->where('id_empleado', $emp->id_empleado)
                ->exists();

            if (! $yaExiste) {
                $inserts = [];
                for ($d = 1; $d <= $diasEnMes; $d++) {
                    $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                    $inserts[] = [
                        'id_rol_mensual' => $rol->id_rol_mensual,
                        'id_empleado' => $emp->id_empleado,
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

        $emp = Empleado::where('id_empleado', $employeeId)
            ->orWhere('item_id', (string) $employeeId)
            ->first();
        $targetId = $emp ? $emp->id_empleado : $employeeId;

        if ($mes && $anio) {
            // Eliminar asignaciones de este empleado ÚNICAMENTE en el rol del departamento seleccionado
            $query = RolMensual::where('mes', $mes)->where('anio', $anio);
            if ($deptId && $deptId !== 'todos') {
                $query->where('id_departamento', (int) $deptId);
            }
            $roles = $query->get();
            $rolesIds = $roles->pluck('id_rol_mensual');

            // El Jefe asignado en este mes no se puede remover con el botón de quitar operador
            $isJefeInRole = $roles->contains(fn ($r) => (int) $r->id_jefe === (int) $targetId);
            if ($isJefeInRole) {
                return response()->json(['success' => false, 'error' => 'No se puede remover al Jefe del departamento en este mes.'], 422);
            }

            if ($rolesIds->count() > 0) {
                AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)
                    ->where('id_empleado', $targetId)
                    ->delete();
            }
        } else {
            // El Jefe no se puede remover de su área general
            if ($emp && $emp->cargo && in_array($emp->cargo->nombre_cargo, ['Jefe de Area', 'Jefe de Servicio'])) {
                return response()->json(['success' => false, 'error' => 'No se puede remover al Jefe del departamento'], 422);
            }

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

        // Limpiar mes/departamento objetivo antes de copiar para que sea un REEMPLAZO limpio
        if (! $deptId || $deptId === 'todos') {
            $existingTargetRoles = RolMensual::where('mes', $mes)->where('anio', $anio)->get();
            if ($existingTargetRoles->isNotEmpty()) {
                $targetRoleIds = $existingTargetRoles->pluck('id_rol_mensual')->toArray();
                AsignacionTurnoDiario::whereIn('id_rol_mensual', $targetRoleIds)->delete();
                RolMensual::whereIn('id_rol_mensual', $targetRoleIds)->delete();
            }
        } else {
            $targetRole = RolMensual::where('mes', $mes)->where('anio', $anio)->where('id_departamento', $deptId)->first();
            if ($targetRole) {
                AsignacionTurnoDiario::where('id_rol_mensual', $targetRole->id_rol_mensual)->delete();
            }
        }

        $copiedCount = 0;
        foreach ($prevRoles as $prevRol) {
            $rolActual = RolMensual::firstOrCreate([
                'mes' => $mes,
                'anio' => $anio,
                'id_departamento' => $prevRol->id_departamento,
            ]);

            // Preservar jefe del mes anterior o el jefe asignado
            if ($prevRol->id_jefe) {
                $rolActual->id_jefe = $prevRol->id_jefe;
                $rolActual->save();
            }

            // Empleados asignados a este rol en el mes anterior
            $empIds = $prevAsignaciones->where('id_rol_mensual', $prevRol->id_rol_mensual)
                ->pluck('id_empleado')
                ->unique();

            // Si el jefe no estaba en las asignaciones pero existe en el rol anterior, agregarlo
            if ($rolActual->id_jefe && ! $empIds->contains($rolActual->id_jefe)) {
                $empIds->push($rolActual->id_jefe);
            }

            $inserts = [];
            foreach ($empIds as $empId) {
                $isJefe = ($empId == $rolActual->id_jefe);

                for ($d = 1; $d <= $diasEnMes; $d++) {
                    $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);

                    if ($isJefe) {
                        $shiftId = $idTurnoLibre;
                    } else {
                        // Buscar turno exacto del mes anterior para el mismo día
                        $prevFecha = sprintf('%04d-%02d-%02d', $prevAnio, $prevMes, $d);
                        $prevShift = $prevAsignaciones->where('id_rol_mensual', $prevRol->id_rol_mensual)
                            ->where('id_empleado', $empId)
                            ->where('fecha', $prevFecha)
                            ->first();

                        $shiftId = $prevShift ? $prevShift->id_turno : $idTurnoLibre;
                    }

                    $inserts[] = [
                        'id_rol_mensual' => $rolActual->id_rol_mensual,
                        'id_empleado' => $empId,
                        'id_fuente' => $prevShift ? $prevShift->id_fuente : 1,
                        'fecha' => $fecha,
                        'id_turno' => $shiftId,
                    ];
                }
                $copiedCount++;
            }

            if (! empty($inserts)) {
                foreach (array_chunk($inserts, 100) as $chunk) {
                    AsignacionTurnoDiario::insert($chunk);
                }
            }
        }

        return response()->json(['success' => true, 'count' => $copiedCount, 'prev_mes_nombre' => $prevNombre, 'prev_anio' => $prevAnio]);
    }

    public function resetMonth(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer',
            'anio' => 'required|integer',
        ]);

        $mes = (int) $request->mes;
        $anio = (int) $request->anio;
        $deptId = $request->input('departamento_id');

        $query = RolMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', 'Borrador');
            });

        if ($deptId && $deptId !== 'todos') {
            $query->where('id_departamento', (int) $deptId);
        }

        $roles = $query->get();
        $rolesIds = $roles->pluck('id_rol_mensual')->toArray();

        if (! empty($rolesIds)) {
            AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)->delete();
            RolMensual::whereIn('id_rol_mensual', $rolesIds)->delete();
        }

        return response()->json(['success' => true, 'message' => "El mes {$mes}/{$anio} se ha restablecido a cero."]);
    }

    public function changeMonthJefe(Request $request)
    {
        $mes = (int) $request->input('mes');
        $anio = (int) $request->input('anio');
        $deptId = (int) ($request->input('department_id') ?? $request->input('id_departamento'));
        $rawEmpId = $request->input('employee_id', $request->input('jefe_empleado_id'));
        $empId = ($rawEmpId !== null && $rawEmpId !== '' && $rawEmpId !== 0 && $rawEmpId !== '0') ? (int) $rawEmpId : null;

        $request->merge([
            'mes' => $mes,
            'anio' => $anio,
            'department_id' => $deptId,
            'employee_id' => $empId,
        ]);

        $request->validate([
            'mes' => 'required|integer|between:1,12',
            'anio' => 'required|integer|min:2020|max:2050',
            'department_id' => 'required|integer|exists:Departamentos_Servicios,id_departamento',
            'employee_id' => 'nullable|integer',
        ]);

        $rol = RolMensual::firstOrCreate([
            'mes' => $mes,
            'anio' => $anio,
            'id_departamento' => $deptId,
        ]);

        $turnoLibre = Turno::where('codigo_turno', 'L')->first();
        $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

        // Caso: Dejar sin jefe en este mes
        if (! $empId) {
            $prevJefeId = $rol->id_jefe;
            $rol->id_jefe = null;
            $rol->save();

            if ($prevJefeId) {
                $prevAssignments = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                    ->where('id_empleado', $prevJefeId)
                    ->get();
                $onlyL = $prevAssignments->every(fn ($a) => (int) $a->id_turno === (int) $idTurnoLibre);
                if ($onlyL || $prevAssignments->isEmpty()) {
                    AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                        ->where('id_empleado', $prevJefeId)
                        ->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se ha quitado la jefatura del departamento exclusivamente para {$mes}/{$anio}.",
                'jefe' => null,
            ]);
        }

        $emp = app(RrhhService::class)->ensureEmployeeInLocal($empId, $deptId, 'Jefe de Area');
        if (! $emp) {
            return response()->json(['success' => false, 'error' => 'Empleado no encontrado.'], 404);
        }

        $prevJefeId = $rol->id_jefe;
        $rol->id_jefe = $emp->id_empleado;
        $rol->save();

        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);

        // Si el jefe anterior estaba en AsignacionTurnoDiario y solo tenía 'L', removerlo de este mes
        if ($prevJefeId && $prevJefeId !== $emp->id_empleado) {
            $prevAssignments = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                ->where('id_empleado', $prevJefeId)
                ->get();
            $onlyL = $prevAssignments->every(fn ($a) => (int) $a->id_turno === (int) $idTurnoLibre);
            if ($onlyL || $prevAssignments->isEmpty()) {
                AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                    ->where('id_empleado', $prevJefeId)
                    ->delete();
            }
        }

        // Insertar al nuevo jefe en este mes con turnos 'L' si no tiene
        $hasShifts = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
            ->where('id_empleado', $emp->id_empleado)
            ->exists();

        if (! $hasShifts) {
            $inserts = [];
            for ($d = 1; $d <= $diasEnMes; $d++) {
                $inserts[] = [
                    'id_rol_mensual' => $rol->id_rol_mensual,
                    'id_empleado' => $emp->id_empleado,
                    'id_fuente' => null,
                    'fecha' => sprintf('%04d-%02d-%02d', $anio, $mes, $d),
                    'id_turno' => $idTurnoLibre,
                ];
            }
            AsignacionTurnoDiario::insert($inserts);
        }

        // Asegurar usuario para inicio de sesión
        $createdUsername = null;
        $user = User::where('id_empleado', $emp->id_empleado)->first();
        if ($user) {
            if ($user->rol !== 'Administrador') {
                $user->rol = 'Jefe de Area';
                $user->save();
            }
            $createdUsername = $user->nombre_usuario ?: $emp->codigo_biometrico;
        } else {
            User::create([
                'nombre_usuario' => $emp->codigo_biometrico,
                'correo' => strtolower($emp->codigo_biometrico).'@hospital.test',
                'password_hash' => bcrypt('password'),
                'rol' => 'Jefe de Area',
                'id_empleado' => $emp->id_empleado,
            ]);
            $createdUsername = $emp->codigo_biometrico;
        }

        return response()->json([
            'success' => true,
            'message' => "Jefe del departamento actualizado exclusivamente para {$mes}/{$anio}.",
            'created_username' => $createdUsername,
            'jefe' => [
                'id' => $emp->id_empleado,
                'id_empleado' => $emp->id_empleado,
                'name' => $emp->nombre_completo,
                'nombre_completo' => $emp->nombre_completo,
                'bio' => $emp->codigo_biometrico,
                'codigo_biometrico' => $emp->codigo_biometrico,
                'cargo' => 'Jefe de Area',
            ],
        ]);
    }

    public function addAreaToMonth(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer',
            'anio' => 'required|integer',
            'department_id' => 'required|integer',
            'jefe_employee_id' => 'nullable|integer',
        ]);

        $mes = (int) $request->mes;
        $anio = (int) $request->anio;
        $deptId = (int) $request->department_id;
        $jefeId = $request->filled('jefe_employee_id') ? (int) $request->jefe_employee_id : null;

        $dept = DepartamentoServicio::findOrFail($deptId);

        $rol = RolMensual::firstOrCreate([
            'mes' => $mes,
            'anio' => $anio,
            'id_departamento' => $deptId,
        ]);

        if ($jefeId) {
            $emp = app(RrhhService::class)->ensureEmployeeInLocal($jefeId, $deptId, 'Jefe de Area');
            if ($emp) {
                $rol->id_jefe = $emp->id_empleado;
                $rol->save();

                $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
                $turnoLibre = Turno::where('codigo_turno', 'L')->first();
                $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

                $hasShifts = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                    ->where('id_empleado', $emp->id_empleado)
                    ->exists();

                if (! $hasShifts) {
                    $inserts = [];
                    for ($d = 1; $d <= $diasEnMes; $d++) {
                        $inserts[] = [
                            'id_rol_mensual' => $rol->id_rol_mensual,
                            'id_empleado' => $emp->id_empleado,
                            'id_fuente' => null,
                            'fecha' => sprintf('%04d-%02d-%02d', $anio, $mes, $d),
                            'id_turno' => $idTurnoLibre,
                        ];
                    }
                    AsignacionTurnoDiario::insert($inserts);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Área \"{$dept->nombre_departmento}\" habilitada para {$mes}/{$anio}.",
        ]);
    }

    public function getDepartamentosMes(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer',
            'anio' => 'required|integer',
        ]);

        $mes = (int) $request->mes;
        $anio = (int) $request->anio;

        $roles = RolMensual::with(['departamento', 'jefe.cargo'])
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        $result = [];
        foreach ($roles as $rol) {
            if (! $rol->departamento) {
                continue;
            }

            $jefeData = null;
            if ($rol->jefe) {
                $jefeData = [
                    'id_empleado' => $rol->jefe->id_empleado,
                    'nombre_completo' => $rol->jefe->nombre_completo,
                    'codigo_biometrico' => $rol->jefe->codigo_biometrico,
                    'cargo' => $rol->jefe->cargo?->nombre_cargo ?? 'Jefe de Area',
                ];
            }

            $result[] = [
                'id_rol_mensual' => $rol->id_rol_mensual,
                'id_departamento' => $rol->id_departamento,
                'nombre_departmento' => $rol->departamento->nombre_departmento,
                'jefe' => $jefeData,
                'empleados' => $jefeData ? [$jefeData] : [],
            ];
        }

        usort($result, fn ($a, $b) => strcasecmp($a['nombre_departmento'], $b['nombre_departmento']));

        return response()->json($result);
    }

    public function addDepartamentoMes(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer',
            'anio' => 'required|integer',
            'nombre_departmento' => 'nullable|string',
            'department_id' => 'nullable|integer',
            'id_departamento' => 'nullable|integer',
            'jefe_empleado_id' => 'nullable|integer',
            'employee_id' => 'nullable|integer',
        ]);

        $mes = (int) $request->mes;
        $anio = (int) $request->anio;
        $deptId = $request->filled('department_id')
            ? (int) $request->department_id
            : ($request->filled('id_departamento') ? (int) $request->id_departamento : null);
        $nombre = trim($request->nombre_departmento ?? '');
        $jefeId = $request->filled('jefe_empleado_id')
            ? (int) $request->jefe_empleado_id
            : ($request->filled('employee_id') ? (int) $request->employee_id : null);

        if (! $deptId) {
            if (! $nombre) {
                return response()->json(['error' => 'Debe indicar el nombre del área o seleccionar una.'], 422);
            }
            $dept = DepartamentoServicio::firstOrCreate(
                ['nombre_departmento' => $nombre],
                ['nombre_departmento' => $nombre]
            );
            $deptId = $dept->id_departamento;
        } else {
            $dept = DepartamentoServicio::findOrFail($deptId);
        }

        return DB::transaction(function () use ($mes, $anio, $deptId, $dept, $jefeId) {
            $exists = RolMensual::where('mes', $mes)
                ->where('anio', $anio)
                ->where('id_departamento', $deptId)
                ->first();

            if ($exists) {
                if ($jefeId && ! $exists->id_jefe) {
                    $rol = $exists;
                } else {
                    return response()->json([
                        'error' => "El área '{$dept->nombre_departmento}' ya se encuentra habilitada para {$mes}/{$anio}.",
                    ], 422);
                }
            } else {
                $rol = RolMensual::create([
                    'mes' => $mes,
                    'anio' => $anio,
                    'id_departamento' => $deptId,
                    'id_jefe' => null,
                ]);
            }

            $createdUsername = null;
            if ($jefeId) {
                $cargoJefeArea = Cargo::firstOrCreate(['nombre_cargo' => 'Jefe de Area']);
                $empleado = app(RrhhService::class)->ensureEmployeeInLocal($jefeId, $deptId, 'Jefe de Area');
                if ($empleado) {
                    $rol->id_jefe = $empleado->id_empleado;
                    $rol->save();

                    $user = User::where('id_empleado', $empleado->id_empleado)->first();
                    if ($user) {
                        if ($user->rol !== 'Administrador') {
                            $user->rol = 'Jefe de Area';
                            $user->save();
                        }
                        $createdUsername = $user->nombre_usuario ?: $empleado->codigo_biometrico;
                    } else {
                        User::create([
                            'nombre_usuario' => $empleado->codigo_biometrico,
                            'correo' => strtolower($empleado->codigo_biometrico).'@hospital.test',
                            'password_hash' => bcrypt('password'),
                            'rol' => 'Jefe de Area',
                            'id_empleado' => $empleado->id_empleado,
                        ]);
                        $createdUsername = $empleado->codigo_biometrico;
                    }

                    $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
                    $turnoLibre = Turno::where('codigo_turno', 'L')->first();
                    $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

                    $hasShifts = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                        ->where('id_empleado', $empleado->id_empleado)
                        ->exists();

                    if (! $hasShifts) {
                        $inserts = [];
                        for ($d = 1; $d <= $diasEnMes; $d++) {
                            $inserts[] = [
                                'id_rol_mensual' => $rol->id_rol_mensual,
                                'id_empleado' => $empleado->id_empleado,
                                'id_fuente' => null,
                                'fecha' => sprintf('%04d-%02d-%02d', $anio, $mes, $d),
                                'id_turno' => $idTurnoLibre,
                            ];
                        }
                        AsignacionTurnoDiario::insert($inserts);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Área '{$dept->nombre_departmento}' habilitada exitosamente para {$mes}/{$anio}.",
                'created_username' => $createdUsername,
                'rol' => $rol,
                'dept' => $dept,
            ]);
        });
    }

    public function removeAreaFromMonth(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer',
            'anio' => 'required|integer',
            'department_id' => 'required|integer',
        ]);

        $mes = (int) $request->mes;
        $anio = (int) $request->anio;
        $deptId = (int) $request->department_id;

        $rol = RolMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->where('id_departamento', $deptId)
            ->first();

        if (! $rol) {
            return response()->json(['error' => 'El área no está habilitada en este mes.'], 404);
        }

        AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)->delete();
        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Área deshabilitada correctamente para este período.',
        ]);
    }

    public function copyMonthAreas(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer',
            'anio' => 'required|integer',
        ]);

        $mes = (int) $request->mes;
        $anio = (int) $request->anio;

        $prevMes = $mes === 1 ? 12 : $mes - 1;
        $prevAnio = $mes === 1 ? $anio - 1 : $anio;

        $prevRoles = RolMensual::where('mes', $prevMes)
            ->where('anio', $prevAnio)
            ->get();

        if ($prevRoles->isEmpty()) {
            return response()->json([
                'error' => "No hay áreas registradas en el mes anterior ({$prevMes}/{$prevAnio}) para copiar.",
            ], 422);
        }

        // Limpiar áreas del mes actual que no existen en el mes anterior para que sea un reemplazo fiel
        $prevDeptIds = $prevRoles->pluck('id_departamento')->map(fn ($id) => (int) $id)->filter()->toArray();
        $orphanedRoles = RolMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->whereNotIn('id_departamento', $prevDeptIds)
            ->get();

        if ($orphanedRoles->isNotEmpty()) {
            $orphanedIds = $orphanedRoles->pluck('id_rol_mensual')->toArray();
            AsignacionTurnoDiario::whereIn('id_rol_mensual', $orphanedIds)->delete();
            RolMensual::whereIn('id_rol_mensual', $orphanedIds)->delete();
        }

        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $turnoLibre = Turno::where('codigo_turno', 'L')->first();
        $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

        $copiedCount = 0;
        foreach ($prevRoles as $prevRol) {
            $rol = RolMensual::firstOrCreate([
                'mes' => $mes,
                'anio' => $anio,
                'id_departamento' => $prevRol->id_departamento,
            ]);

            if ($prevRol->id_jefe) {
                $rol->id_jefe = $prevRol->id_jefe;
                $rol->save();

                $hasShifts = AsignacionTurnoDiario::where('id_rol_mensual', $rol->id_rol_mensual)
                    ->where('id_empleado', $prevRol->id_jefe)
                    ->exists();

                if (! $hasShifts) {
                    $inserts = [];
                    for ($d = 1; $d <= $diasEnMes; $d++) {
                        $inserts[] = [
                            'id_rol_mensual' => $rol->id_rol_mensual,
                            'id_empleado' => $prevRol->id_jefe,
                            'id_fuente' => null,
                            'fecha' => sprintf('%04d-%02d-%02d', $anio, $mes, $d),
                            'id_turno' => $idTurnoLibre,
                        ];
                    }
                    AsignacionTurnoDiario::insert($inserts);
                }
            }
            $copiedCount++;
        }

        return response()->json([
            'success' => true,
            'count' => $copiedCount,
            'message' => "Se copiaron {$copiedCount} áreas desde {$prevMes}/{$prevAnio}.",
        ]);
    }
}
