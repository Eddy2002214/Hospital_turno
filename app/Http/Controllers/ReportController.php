<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\AsignacionTurnoDiario;
use App\Models\Turno;
use App\Models\DepartamentoServicio;

class ReportController extends Controller
{
    public function printReport(Request $request)
    {
        $mes = $request->input('mes', date('n'));
        $anio = $request->input('anio', date('Y'));
        $departamentoId = $request->input('departamento_id');

        $departamento = null;
        $query = Empleado::with('cargo');
        
        if ($departamentoId && $departamentoId !== 'todos') {
            $query->where('id_departamento', $departamentoId);
            $departamento = DepartamentoServicio::find($departamentoId);
        }

        $empleados = $query->get();
        $turnosById = Turno::all()->keyBy('id_turno');
        $turnos = Turno::all();

        $rolMensual = null;
        if ($departamentoId && $departamentoId !== 'todos') {
            $rolMensual = RolMensual::where('mes', $mes)
                                    ->where('anio', $anio)
                                    ->where('id_departamento', $departamentoId)
                                    ->first();
        }

        $matrix = [];
        foreach ($empleados as $emp) {
            $shifts = array_fill(1, 31, 'L');
            
            if ($rolMensual) {
                $asignaciones = AsignacionTurnoDiario::where('id_rol_mensual', $rolMensual->id_rol_mensual)
                                                     ->where('id_empleado', $emp->id_empleado)
                                                     ->get();
                foreach ($asignaciones as $asig) {
                    $dia = (int) date('d', strtotime($asig->fecha));
                    if (isset($turnosById[$asig->id_turno])) {
                        $shifts[$dia] = $turnosById[$asig->id_turno]->codigo_turno;
                    }
                }
            }
            
            $matrix[] = [
                'item' => $emp->item_id,
                'codigo_biometrico' => $emp->codigo_biometrico,
                'nombre' => $emp->nombre_completo,
                'cargo' => $emp->cargo ? $emp->cargo->nombre_cargo : '',
                'shifts' => $shifts
            ];
        }
        
        $mesesStr = [1=>'ENERO', 2=>'FEBRERO', 3=>'MARZO', 4=>'ABRIL', 5=>'MAYO', 6=>'JUNIO', 7=>'JULIO', 8=>'AGOSTO', 9=>'SEPTIEMBRE', 10=>'OCTUBRE', 11=>'NOVIEMBRE', 12=>'DICIEMBRE'];

        return view('reporte', [
            'matrix' => $matrix,
            'mes' => $mesesStr[(int)$mes] ?? $mes,
            'anio' => $anio,
            'departamento' => $departamento ? $departamento->nombre_departamento : 'TODOS LOS SERVICIOS',
            'turnos' => $turnos
        ]);
    }
}
