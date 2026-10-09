<?php

namespace App\Http\Controllers;

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function getEmpleados(Request $request)
    {
        $query = Empleado::with('cargo');

        // Si se pasa "todos", no filtramos por departamento
        if ($request->has('departamento_id') && $request->departamento_id && $request->departamento_id !== 'todos') {
            $query->where('id_departamento', $request->departamento_id);
        }

        $empleados = $query->get()->sortByDesc(function ($emp) {
            if (!$emp->cargo) return 0;
            if ($emp->cargo->nombre_cargo === 'Administrador de Sistema') return 3;
            if ($emp->cargo->nombre_cargo === 'Jefe de Servicio') return 2;
            if ($emp->cargo->nombre_cargo === 'Jefe de Area') return 1;
            return 0;
        })->values();
        $mes = $request->input('mes', 10);
        $anio = $request->input('anio', 2026);
        $departamentoId = $request->input('departamento_id');

        // Buscar si ya existe un rol para ese mes/año/departamento
        $rolesMensuales = collect();
        if ($departamentoId && $departamentoId !== 'todos') {
            $r = RolMensual::where('mes', $mes)
                ->where('anio', $anio)
                ->where('id_departamento', $departamentoId)
                ->first();
            if ($r) {
                $rolesMensuales->push($r);
            }
        } else {
            $rolesMensuales = RolMensual::where('mes', $mes)
                ->where('anio', $anio)
                ->get();
        }

        $turnosDb = Turno::all()->keyBy('codigo_turno');
        $turnosById = Turno::all()->keyBy('id_turno');

        $rolesIds = $rolesMensuales->pluck('id_rol_mensual')->toArray();
        $todasAsignaciones = [];
        if (count($rolesIds) > 0) {
            $todasAsignaciones = AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)->get()->groupBy('id_empleado');
        }

        return $empleados->map(function ($emp) use ($todasAsignaciones, $turnosById) {
            $shifts = array_fill(0, 31, 'L');

            if (isset($todasAsignaciones[$emp->id_empleado])) {
                foreach ($todasAsignaciones[$emp->id_empleado] as $asig) {
                    $dia = (int) date('d', strtotime($asig->fecha)) - 1;
                    if (isset($turnosById[$asig->id_turno])) {
                        $shifts[$dia] = $turnosById[$asig->id_turno]->codigo_turno;
                    }
                }
            }

            return [
                'id' => $emp->id_empleado,
                'no' => $emp->id_empleado,
                'ff' => 'TGN',
                'item' => $emp->item_id,
                'bio' => $emp->codigo_biometrico,
                'phone' => $emp->telefono,
                'name' => $emp->nombre_completo,
                'role' => $emp->cargo ? $emp->cargo->nombre_cargo : 'Sin cargo',
                'shifts' => $shifts,
            ];
        });
    }

    public function saveRole(Request $request)
    {
        $mesStr = $request->input('month');
        $anio = $request->input('year');
        $people = $request->input('people');

        $meses = ['Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6, 'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12];
        $mes = $meses[$mesStr] ?? 10;

        if (empty($people)) {
            return response()->json(['success' => true]);
        }

        $empleadosDb = Empleado::whereIn('id_empleado', collect($people)->pluck('id'))->get()->keyBy('id_empleado');
        $turnos = Turno::all()->keyBy('codigo_turno');

        $rolMensuales = [];
        $inserts = [];

        foreach ($people as $person) {
            $empDb = $empleadosDb[$person['id']] ?? null;
            if (! $empDb) {
                continue;
            }

            $deptId = $empDb->id_departamento; // Could be null
            $deptKey = $deptId ?? 'null';

            if (! isset($rolMensuales[$deptKey])) {
                $rolMensuales[$deptKey] = RolMensual::firstOrCreate([
                    'mes' => $mes,
                    'anio' => $anio,
                    'id_departamento' => $deptId,
                ]);
            }
            $rolMensual = $rolMensuales[$deptKey];

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
                    ];
                }
            }
        }

        // Eliminar las asignaciones previas solo para los empleados que estamos guardando
        if (count($rolMensuales) > 0) {
            $rolesIds = collect($rolMensuales)->pluck('id_rol_mensual')->toArray();
            $empleadosIds = collect($people)->pluck('id')->toArray();

            // VALIDACIÓN: Verificar que no estén asignados a otra área en la misma fecha
            $startDate = sprintf('%04d-%02d-01', $anio, $mes);
            $endDate = date('Y-m-t', strtotime($startDate));

            $existingAssignments = AsignacionTurnoDiario::with(['rolMensual.departamento', 'turno'])
                ->whereIn('id_empleado', $empleadosIds)
                ->whereBetween('fecha', [$startDate, $endDate])
                ->whereNotIn('id_rol_mensual', $rolesIds)
                ->get()
                ->groupBy(function ($item) {
                    return $item->id_empleado.'_'.$item->fecha;
                });

            $conflictsToDelete = [];
            foreach ($inserts as $insert) {
                $key = $insert['id_empleado'].'_'.$insert['fecha'];
                if (isset($existingAssignments[$key])) {
                    $conflict = $existingAssignments[$key]->first();
                    $conflictsToDelete[] = $conflict->id_asignacion;
                }
            }
            if (!empty($conflictsToDelete)) {
                AsignacionTurnoDiario::whereIn('id_asignacion', $conflictsToDelete)->delete();
            }
            AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesIds)
                ->whereIn('id_empleado', $empleadosIds)
                ->delete();
        }

        // Batch insert para mejorar rendimiento
        foreach (array_chunk($inserts, 100) as $chunk) {
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

        return $roles->map(function ($r) use ($mesesNombres) {
            return [
                'id' => $r->id_rol_mensual,
                'mes' => $r->mes,
                'mesNombre' => $mesesNombres[$r->mes] ?? 'Desconocido',
                'anio' => $r->anio,
                'departamento_id' => $r->id_departamento ?? 'null',
                'departamento_nombre' => $r->departamento ? $r->departamento->nombre_departmento : 'Administración (Global)',
            ];
        });
    }

    public function getUnassigned(Request $request)
    {
        // Fetch all employees EXCEPT those whose cargo is Administrador de Sistema or Jefe de Servicio
        $empleados = Empleado::with(['cargo', 'departamento'])
            ->whereHas('cargo', function ($q) {
                $q->whereNotIn('nombre_cargo', ['Administrador de Sistema', 'Jefe de Servicio']);
            })
            ->get();

        return $empleados->map(function ($emp) {
            return [
                'id' => $emp->id_empleado,
                'bio' => $emp->codigo_biometrico,
                'name' => $emp->nombre_completo,
                'role' => $emp->cargo ? $emp->cargo->nombre_cargo : 'Sin cargo',
                'dept' => $emp->departamento ? $emp->departamento->nombre_departmento : 'Sin Área',
                'dept_id' => $emp->id_departamento,
            ];
        });
    }

    public function assignEmployee(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'department_id' => 'required|integer',
        ]);

        $emp = Empleado::find($request->employee_id);
        if ($emp) {
            $emp->id_departamento = $request->department_id;
            $emp->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'error' => 'Empleado no encontrado'], 404);
    }

    public function removeEmployee(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $emp = Empleado::find($request->employee_id);
        if ($emp) {
            // Eliminar asignaciones futuras si es necesario, o solo quitar del departamento
            $emp->id_departamento = null;
            $emp->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'error' => 'Empleado no encontrado'], 404);
    }
}
