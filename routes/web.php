<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/login', [\App\Http\Controllers\AuthController::class, 'login']);

Route::get('/api/departamentos', function () {
    return \App\Models\DepartamentoServicio::all();
});
Route::get('/api/turnos', function () {
    return \App\Models\Turno::all();
});

Route::get('/api/empleados', [\App\Http\Controllers\RoleController::class, 'getEmpleados']);
Route::post('/api/roles/save', [\App\Http\Controllers\RoleController::class, 'saveRole']);
Route::post('/api/kiosk/register', [\App\Http\Controllers\KioskController::class, 'register']);

Route::get('/reporte/imprimir', [\App\Http\Controllers\ReportController::class, 'printReport'])->middleware(\App\Http\Middleware\CheckRole::class . ':Administrador');
