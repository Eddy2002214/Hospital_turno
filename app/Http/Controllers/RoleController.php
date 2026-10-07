<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\AsignacionTurnoDiario;
use App\Models\Turno;
use App\Models\DepartamentoServicio;

class RoleController extends Controller
{
    public function getEmpleados(Request $request)
    {
        $query = Empleado::with('cargo');
        
        // Si se pasa "todos", no filtramos por departamento
        if ($request->has('departamento_id') && $request->departamento_id && $request->departamento_id !== 'todos') {
            $query->where('id_departamento', $request->departamento_id);
        }

        $empleados = $query->get();
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
            if ($r) $rolesMensuales->push($r);
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

        return $empleados->map(function($emp) use ($todasAsignaciones, $turnosById) {
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
        $departamentoId = $request->input('serviceId');
        $people = $request->input('people');
        
        $meses = ['Enero'=>1, 'Febrero'=>2, 'Marzo'=>3, 'Abril'=>4, 'Mayo'=>5, 'Junio'=>6, 'Julio'=>7, 'Agosto'=>8, 'Septiembre'=>9, 'Octubre'=>10, 'Noviembre'=>11, 'Diciembre'=>12];
        $mes = $meses[$mesStr] ?? 10;

        if ($departamentoId === 'todos' || !$departamentoId) {
            return response()->json(['error' => 'Debe seleccionar un departamento específico para guardar el rol'], 400);
        }

        $rolMensual = RolMensual::firstOrCreate([
            'mes' => $mes,
            'anio' => $anio,
            'id_departamento' => $departamentoId
        ]);

        // Eliminar las asignaciones previas de este rol
        AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual)->delete();

        $turnos = Turno::all()->keyBy('codigo_turno');

        $inserts = [];
        foreach ($people as $person) {
            foreach ($person['shifts'] as $index => $shiftCode) {
                $dia = $index + 1;
                // Ignorar días inválidos para el mes
                if (!checkdate($mes, $dia, $anio)) continue;
                
                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                $turno = $turnos[$shiftCode] ?? null;
                
                if ($turno) {
                    $inserts[] = [
                        'id_rol_mensual' => $rolMensual->id_rol_mensual,
                        'id_empleado' => $person['id'],
                        'id_fuente' => null,
                        'fecha' => $fecha,
                        'id_turno' => $turno->id_turno
                    ];
                }
            }
        }
        
        // Batch insert para mejorar rendimiento
        foreach (array_chunk($inserts, 100) as $chunk) {
            AsignacionTurnoDiario::insert($chunk);
        }

        return response()->json(['success' => true]);
    }
}
