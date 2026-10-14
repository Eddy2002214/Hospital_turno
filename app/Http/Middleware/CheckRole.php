<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // asumiendo uso de Sanctum o auth básico
        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }

            return redirect('/login');
        }

        if (! in_array(auth()->user()->rol, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acceso denegado'], 403);
            }
            abort(403, 'Acceso denegado: Se requiere rol de Administrador.');
        }

        return $next($request);
    }
}
