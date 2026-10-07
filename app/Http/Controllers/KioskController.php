<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Empleado;
use App\Models\AsignacionTurnoDiario;
use App\Models\ConsumoComedor;
use Carbon\Carbon;

class KioskController extends Controller
{
    public function register(Request $request)
    {
        $identificador = $request->input('identificador');
        
        // 1. Buscar empleado por codigo_biometrico o item_id
        $empleado = Empleado::where('codigo_biometrico', $identificador)
                            ->orWhere('item_id', $identificador)
                            ->first();
                            
        if (!$empleado) {
            return response()->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
        }

        $hoy = Carbon::now()->toDateString();
        $horaActual = Carbon::now();

        // 2. Validar turno de hoy
        $asignacion = AsignacionTurnoDiario::with('turno')
                        ->where('id_empleado', $empleado->id_empleado)
                        ->where('fecha', $hoy)
                        ->first();

        if (!$asignacion || !$asignacion->turno || $asignacion->turno->codigo_turno === 'L') {
            return response()->json(['success' => false, 'message' => 'No tiene turno activo hoy'], 403);
        }

        $turno = $asignacion->turno;
        
        // 3. Validar franja de comida (ej: últimas 2 horas del turno)
        if ($turno->hora_salida) {
            $horaSalida = Carbon::createFromFormat('H:i:s', $turno->hora_salida);
            $inicioComida = $horaSalida->copy()->subHours(2);
            $finComida = $horaSalida->copy()->addMinutes(30);

            // Validar si está en la franja, de manera simplificada
            if (!$horaActual->between($inicioComida, $finComida)) {
                return response()->json(['success' => false, 'message' => 'Fuera de horario de comida permitido'], 403);
            }
        }

        // 4. Validar si ya comió
        $consumo = ConsumoComedor::where('id_empleado', $empleado->id_empleado)
                                 ->where('fecha', $hoy)
                                 ->first();
                                 
        if ($consumo) {
            return response()->json(['success' => false, 'message' => 'Consumo ya registrado a las ' . Carbon::parse($consumo->hora)->format('H:i')], 403);
        }

        // 5. Guardar consumo
        ConsumoComedor::create([
            'id_empleado' => $empleado->id_empleado,
            'fecha' => $hoy,
            'hora' => $horaActual->toTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consumo registrado exitosamente',
            'data' => [
                'nombre' => $empleado->nombre_completo,
                'cargo' => $empleado->cargo ? $empleado->cargo->nombre_cargo : 'Sin cargo',
                'turno' => $turno->nombre_turno
            ]
        ]);
    }
}
