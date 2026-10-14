<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $code = $request->input('code');

        $empleado = Empleado::where('codigo_biometrico', $code)->first();

        if (! $empleado || ! $empleado->usuario) {
            return response()->json(['error' => 'Código no válido o usuario no encontrado'], 401);
        }

        $user = $empleado->usuario;

        return response()->json([
            'id_usuario' => $user->id_usuario,
            'id_empleado' => $empleado->id_empleado,
            'nombre' => $empleado->nombre_completo,
            'rol' => $user->rol,
            'id_departamento' => $empleado->id_departamento,
        ]);
    }
}
