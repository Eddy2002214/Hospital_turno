<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Middleware\CheckRole;
use App\Models\Cargo;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/login', [AuthController::class, 'login']);

Route::get('/api/departamentos', function () {
    return DepartamentoServicio::with(['empleados' => function ($q) {
        $q->whereHas('cargo', function ($q2) {
            $q2->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
        });
    }])->get();
});
Route::get('/api/departamentos/catalogo', function () {
    $csvFile = database_path('personal_hospital.csv');
    $catalog = [];

    if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
        fgetcsv($handle, 0, ';'); // skip header
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (! empty($row[9])) {
                $name = trim(preg_replace('/\s+/', ' ', $row[9]));
                if ($name !== '' && strtolower($name) !== 'sin servicio asignado') {
                    $catalog[$name] = true;
                }
            }
        }
        fclose($handle);
    }

    $existing = DepartamentoServicio::pluck('nombre_departmento')->toArray();
    $merged = array_unique(array_merge(array_keys($catalog), $existing));
    sort($merged, SORT_NATURAL | SORT_FLAG_CASE);

    return response()->json($merged);
});
Route::post('/api/departamentos', function (Request $request) {
    $nombre = trim($request->nombre_departmento);
    if (! $nombre) {
        return response()->json(['error' => 'El nombre del área es requerido.'], 422);
    }

    $exists = DepartamentoServicio::whereRaw('LOWER(TRIM(nombre_departmento)) = ?', [mb_strtolower($nombre)])->exists();
    if ($exists) {
        return response()->json([
            'error' => "El área '{$nombre}' ya existe en el sistema. No se permiten áreas repetidas.",
        ], 422);
    }

    $dept = DepartamentoServicio::create(['nombre_departmento' => $nombre]);
    $createdUsername = null;
    if ($request->jefe_empleado_id) {
        $empleado = Empleado::find($request->jefe_empleado_id);
        if ($empleado) {
            $empleado->id_departamento = $dept->id_departamento;
            $cargo = Cargo::firstOrCreate(['nombre_cargo' => 'Jefe de Area']);
            $empleado->id_cargo = $cargo->id_cargo;
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
Route::get('/api/roles/history', [RoleController::class, 'history']);

Route::get('/reporte/imprimir', [ReportController::class, 'printReport'])->middleware(CheckRole::class.':Administrador');
