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
    return DepartamentoServicio::all();
});
Route::post('/api/departamentos', function (Illuminate\Http\Request $request) {
    $dept = DepartamentoServicio::create(['nombre_departmento' => $request->nombre_departmento]);
    if ($request->jefe_id) {
        App\Models\Empleado::where('id_empleado', $request->jefe_id)->update(['id_departamento' => $dept->id_departamento]);
    }
    return $dept;
});
Route::get('/api/jefes', function () {
    return App\Models\Empleado::whereHas('cargo', function ($q) {
        $q->where('nombre_cargo', 'Jefe de Area');
    })->get();
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
