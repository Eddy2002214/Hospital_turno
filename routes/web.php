<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Middleware\CheckRole;
use App\Models\DepartamentoServicio;
use App\Models\Turno;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/login', [AuthController::class, 'login']);

Route::get('/api/departamentos', function () {
    return DepartamentoServicio::with(['empleados' => function($q) {
        $q->whereHas('cargo', function($q2) {
            $q2->where('nombre_cargo', 'Jefe de Area');
        });
    }])->get();
});
Route::post('/api/departamentos', function (Illuminate\Http\Request $request) {
    $dept = DepartamentoServicio::create(['nombre_departmento' => $request->nombre_departmento]);
    $createdUsername = null;
    if ($request->jefe_empleado_id) {
        $empleado = App\Models\Empleado::find($request->jefe_empleado_id);
        if ($empleado) {
            $empleado->id_departamento = $dept->id_departamento;
            $cargo = App\Models\Cargo::firstOrCreate(['nombre_cargo' => 'Jefe de Area']);
            $empleado->id_cargo = $cargo->id_cargo;
            $empleado->save();

            $user = App\Models\User::where('id_empleado', $empleado->id_empleado)->first();
            if ($user) {
                $user->rol = 'Jefe de Area';
                $user->save();
            } else {
                App\Models\User::create([
                    'nombre_usuario' => $empleado->codigo_biometrico,
                    'correo' => strtolower($empleado->codigo_biometrico) . '@hospital.test',
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
Route::get('/api/empleados/all', function () {
    return App\Models\Empleado::with(['cargo', 'departamento'])->get();
});
Route::get('/api/turnos', function () {
    return Turno::all();
});

Route::get('/api/empleados', [RoleController::class, 'getEmpleados']);
Route::get('/api/empleados/unassigned', [RoleController::class, 'getUnassigned']);
Route::post('/api/empleados/assign', [RoleController::class, 'assignEmployee']);
Route::post('/api/empleados/remove', [RoleController::class, 'removeEmployee']);
Route::post('/api/roles/save', [RoleController::class, 'saveRole']);
Route::get('/api/roles/history', [RoleController::class, 'history']);
Route::post('/api/kiosk/register', [KioskController::class, 'register']);

Route::get('/reporte/imprimir', [ReportController::class, 'printReport'])->middleware(CheckRole::class.':Administrador');
