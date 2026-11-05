<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code'));

        if ($code === '') {
            return response()->json(['error' => 'Ingresa un código de acceso o usuario.'], 422);
        }

        // 1. Buscar primero por código biométrico del empleado
        $empleado = Empleado::where('codigo_biometrico', $code)->first();

        // 2. Si no se encuentra como biométrico, buscar por nombre de usuario o correo (ej: 'admin', 'ADM-001')
        $user = null;
        if ($empleado && $empleado->usuario) {
            $user = $empleado->usuario;
        } else {
            $user = User::where('nombre_usuario', $code)->orWhere('correo', $code)->first();
            if ($user && $user->empleado) {
                $empleado = $user->empleado;
            }
        }

        // 3. Fallback especial para Super Admin si entra con ADM-001 o admin
        if (! $user && in_array(strtoupper($code), ['ADM-001', 'ADMIN'])) {
            $user = User::where('rol', 'Administrador')->first();
            if ($user && $user->empleado) {
                $empleado = $user->empleado;
            }
        }

        if (! $user) {
            return response()->json(['error' => 'Código no válido o usuario no encontrado.'], 401);
        }

        // Iniciar sesión en Laravel para que las rutas web protegidas (como /reporte/imprimir) funcionen correctamente
        Auth::login($user);

        $nombre = $empleado ? $empleado->nombre_completo : ($user->nombre_usuario ?: 'Administrador');
        $deptId = $empleado ? $empleado->id_departamento : null;

        return response()->json([
            'id_usuario' => $user->id_usuario,
            'id_empleado' => $empleado ? $empleado->id_empleado : $user->id_empleado,
            'nombre' => $nombre,
            'rol' => $user->rol,
            'id_departamento' => $deptId,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }
}
