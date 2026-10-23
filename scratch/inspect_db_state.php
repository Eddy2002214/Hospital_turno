<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo '=== USUARIOS ACTUALES ==='.PHP_EOL;
$usuarios = DB::table('usuarios')->get();
foreach ($usuarios as $u) {
    echo "ID: {$u->id_usuario} | User: {$u->nombre_usuario} | Rol: {$u->rol} | id_empleado: {$u->id_empleado}".PHP_EOL;
}

echo PHP_EOL.'=== CONTEO DE TABLAS ==='.PHP_EOL;
$tables = ['empleados', 'usuarios', 'roles_mensuales', 'asignacion_turnos_diarios', 'cambios_turnos', 'licencias_vacaciones', 'cargos', 'departamentos_servicios'];
foreach ($tables as $t) {
    try {
        echo "$t: ".DB::table($t)->count().' registros'.PHP_EOL;
    } catch (Exception $e) {
        echo "$t: error ".$e->getMessage().PHP_EOL;
    }
}
