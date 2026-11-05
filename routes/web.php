<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RrhhController;
use App\Http\Middleware\CheckRole;
use App\Models\AsignacionTurnoDiario;
use App\Models\Cargo;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\RolMensual;
use App\Models\Turno;
use App\Models\User;
use App\Services\RrhhService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => redirect('/'))->name('login');

Route::get('/api/login', [AuthController::class, 'login']);
Route::post('/api/logout', [AuthController::class, 'logout']);

// Conexión y sincronización directa con SQL Server (BDRRHH)
Route::get('/api/rrhh/status', [RrhhController::class, 'status']);
Route::get('/api/rrhh/personal', [RrhhController::class, 'getPersonalDirect']);
Route::post('/api/rrhh/sync', [RrhhController::class, 'sync']);

Route::get('/api/departamentos', function () {
    return DepartamentoServicio::with(['empleados' => function ($q) {
        $q->whereHas('cargo', function ($q2) {
            $q2->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
        });
    }])->get();
});
Route::get('/api/departamentos/catalogo', [RrhhController::class, 'catalogoDepartamentos']);
Route::post('/api/departamentos', function (Request $request) {
    $nombre = trim($request->nombre_departmento);
    if (! $nombre) {
        return response()->json(['error' => 'El nombre del área es requerido.'], 422);
    }

    $exists = DepartamentoServicio::whereRaw('LOWER(RTRIM(LTRIM(nombre_departmento))) = ?', [mb_strtolower($nombre)])->exists();
    if ($exists) {
        return response()->json([
            'error' => "El área '{$nombre}' ya existe en el sistema. No se permiten áreas repetidas.",
        ], 422);
    }

    $dept = DepartamentoServicio::create(['nombre_departmento' => $nombre]);
    $createdUsername = null;
    if ($request->jefe_empleado_id) {
        $jefeId = (int) $request->jefe_empleado_id;
        $empleado = app(RrhhService::class)->ensureEmployeeInLocal($jefeId, $dept->id_departamento, 'Jefe de Area');
        if ($empleado) {
            $empleado->id_departamento = $dept->id_departamento;
            $empleado->save();

            $user = User::where('id_empleado', $empleado->id_empleado)->first();
            if ($user) {
                $user->rol = 'Jefe de Area';
                $user->save();
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
        }
    }

    return response()->json(['dept' => $dept, 'created_username' => $createdUsername]);
});

Route::post('/api/departamentos/{id}/jefe', function (Request $request, int $id) {
    $dept = DepartamentoServicio::findOrFail($id);
    $jefeId = $request->jefe_empleado_id ? (int) $request->jefe_empleado_id : null;

    $cargosJefe = Cargo::whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio'])->pluck('id_cargo');
    $cargoOperador = Cargo::firstOrCreate(['nombre_cargo' => 'Operador / Especialista']);
    $cargoJefeArea = Cargo::firstOrCreate(['nombre_cargo' => 'Jefe de Area']);

    $prevJefes = Empleado::where('id_departamento', $dept->id_departamento)
        ->whereIn('id_cargo', $cargosJefe)
        ->get();

    $createdUsername = null;

    $turnoLibre = Turno::where('codigo_turno', 'L')->first();
    $idTurnoLibre = $turnoLibre ? $turnoLibre->id_turno : 5;

    if ($jefeId) {
        $empleado = app(RrhhService::class)->ensureEmployeeInLocal($jefeId, $dept->id_departamento, 'Jefe de Area');
        if ($empleado) {
            $empleado->id_cargo = $cargoJefeArea->id_cargo;
            $empleado->id_departamento = $dept->id_departamento;
            $empleado->save();
        }

        foreach ($prevJefes as $prevJefe) {
            if ($empleado && $prevJefe->id_empleado !== $empleado->id_empleado) {
                $prevJefe->id_cargo = $cargoOperador->id_cargo;
                $prevJefe->save();

                $prevUser = User::where('id_empleado', $prevJefe->id_empleado)->first();
                if ($prevUser && $prevUser->rol === 'Jefe de Area') {
                    $prevUser->rol = 'Operador';
                    $prevUser->save();
                }
            }
        }

        if ($empleado) {
            $user = User::where('id_empleado', $empleado->id_empleado)->first();
            if ($user) {
                $user->rol = 'Jefe de Area';
                $user->save();
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

            // Si se pasa un período específico (mes y anio), se sincroniza ÚNICAMENTE ese período
            if ($request->filled('mes') && $request->filled('anio')) {
                $mesReq = (int) $request->mes;
                $anioReq = (int) $request->anio;

                $dRol = RolMensual::where('id_departamento', $dept->id_departamento)
                    ->where('mes', $mesReq)
                    ->where('anio', $anioReq)
                    ->first();

                if ($dRol) {
                    $prevJefeId = $dRol->id_jefe;
                    $dRol->id_jefe = $empleado->id_empleado;
                    $dRol->save();

                    if ($prevJefeId && $prevJefeId !== $empleado->id_empleado) {
                        $prevAssignments = AsignacionTurnoDiario::where('id_rol_mensual', $dRol->id_rol_mensual)
                            ->where('id_empleado', $prevJefeId)
                            ->get();
                        $onlyL = $prevAssignments->every(fn ($a) => (int) $a->id_turno === (int) $idTurnoLibre);
                        if ($onlyL || $prevAssignments->isEmpty()) {
                            AsignacionTurnoDiario::where('id_rol_mensual', $dRol->id_rol_mensual)
                                ->where('id_empleado', $prevJefeId)
                                ->delete();
                        }
                    }

                    $hasShifts = AsignacionTurnoDiario::where('id_rol_mensual', $dRol->id_rol_mensual)
                        ->where('id_empleado', $empleado->id_empleado)
                        ->exists();

                    if (! $hasShifts) {
                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesReq, $anioReq);
                        $inserts = [];
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $inserts[] = [
                                'id_rol_mensual' => $dRol->id_rol_mensual,
                                'id_empleado' => $empleado->id_empleado,
                                'id_fuente' => null,
                                'fecha' => sprintf('%04d-%02d-%02d', $anioReq, $mesReq, $d),
                                'id_turno' => $idTurnoLibre,
                            ];
                        }
                        AsignacionTurnoDiario::insert($inserts);
                    }
                }
            }
        }
    } else {
        foreach ($prevJefes as $prevJefe) {
            $prevJefe->id_cargo = $cargoOperador->id_cargo;
            $prevJefe->save();

            $prevUser = User::where('id_empleado', $prevJefe->id_empleado)->first();
            if ($prevUser && $prevUser->rol === 'Jefe de Area') {
                $prevUser->rol = 'Operador';
                $prevUser->save();
            }
        }

        // Si se pasa mes y anio específicos para quitar el jefe de ese mes
        if ($request->filled('mes') && $request->filled('anio')) {
            $mesReq = (int) $request->mes;
            $anioReq = (int) $request->anio;

            $dRol = RolMensual::where('id_departamento', $dept->id_departamento)
                ->where('mes', $mesReq)
                ->where('anio', $anioReq)
                ->first();

            if ($dRol) {
                $prevJefeId = $dRol->id_jefe;
                $dRol->id_jefe = null;
                $dRol->save();

                if ($prevJefeId) {
                    $prevAssignments = AsignacionTurnoDiario::where('id_rol_mensual', $dRol->id_rol_mensual)
                        ->where('id_empleado', $prevJefeId)
                        ->get();
                    $onlyL = $prevAssignments->every(fn ($a) => (int) $a->id_turno === (int) $idTurnoLibre);
                    if ($onlyL || $prevAssignments->isEmpty()) {
                        AsignacionTurnoDiario::where('id_rol_mensual', $dRol->id_rol_mensual)
                            ->where('id_empleado', $prevJefeId)
                            ->delete();
                    }
                }
            }
        }
    }

    $updatedDept = DepartamentoServicio::with(['empleados' => function ($q) {
        $q->whereHas('cargo', function ($q2) {
            $q2->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
        });
    }])->find($dept->id_departamento);

    return response()->json([
        'dept' => $updatedDept,
        'created_username' => $createdUsername,
        'message' => $jefeId ? 'Jefe asignado correctamente.' : 'Se ha desvinculado el jefe de esta área.',
    ]);
});
Route::get('/api/empleados/all', [RoleController::class, 'getAllEmpleados']);
Route::get('/api/turnos', function () {
    return Turno::all();
});

Route::get('/api/empleados', [RoleController::class, 'getEmpleados']);
Route::get('/api/empleados/unassigned', [RoleController::class, 'getUnassigned']);
Route::post('/api/empleados/assign', [RoleController::class, 'assignEmployee']);
Route::post('/api/empleados/remove', [RoleController::class, 'removeEmployee']);
Route::post('/api/empleados/copy-month', [RoleController::class, 'copyMonthAssignments']);
Route::post('/api/roles/save', [RoleController::class, 'saveRole']);
Route::post('/api/roles/reset-month', [RoleController::class, 'resetMonth']);
Route::get('/api/departamentos/mes', [RoleController::class, 'getDepartamentosMes']);
Route::post('/api/departamentos/mes', [RoleController::class, 'addDepartamentoMes']);
Route::post('/api/roles/remove-area-month', [RoleController::class, 'removeAreaFromMonth']);
Route::post('/api/roles/copy-areas-month', [RoleController::class, 'copyMonthAreas']);
Route::post('/api/roles/cambiar-jefe-mes', [RoleController::class, 'changeMonthJefe']);
Route::post('/api/roles/add-area-month', [RoleController::class, 'addAreaToMonth']);
Route::get('/api/roles/history', [RoleController::class, 'history']);

Route::get('/reporte/imprimir', [ReportController::class, 'printReport'])->middleware(CheckRole::class.':Administrador');
