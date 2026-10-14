<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use App\Models\Turno;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = new RoleController;

echo "=== 1. PREPARANDO ENTORNO DE PRUEBAS ===\n";
// Usuario Enfermería 1 (ID 7) en Rol 25 (Emergencias, ID 3) con turno M en Febrero 2026
$rol25 = RolMensual::firstOrCreate(['mes' => 2, 'anio' => 2026, 'id_departamento' => 3]);
$turnoM = Turno::where('codigo_turno', 'M')->first();

AsignacionTurnoDiario::where('id_rol_mensual', $rol25->id_rol_mensual)->where('id_empleado', 7)->delete();
for ($d = 1; $d <= 28; $d++) {
    AsignacionTurnoDiario::create([
        'id_rol_mensual' => $rol25->id_rol_mensual,
        'id_empleado' => 7,
        'fecha' => sprintf('2026-02-%02d', $d),
        'id_turno' => $turnoM->id_turno,
    ]);
}
echo "OK: Usuario Enfermería 1 tiene 28 turnos M en Emergencias para Febrero 2026.\n\n";

echo "=== 2. VERIFICANDO EXTERNAL_SHIFTS EN CONSULTAS ===\n";
// Consulta para Admin (departamento_id = null / todos)
$reqAdmin = new Request(['mes' => 2, 'anio' => 2026]);
$staffAdmin = $controller->getAllEmpleados($reqAdmin);
$emp7Admin = collect($staffAdmin)->firstWhere('id_empleado', 7);
$extAdminCount = count(array_filter($emp7Admin['external_shifts']));
echo "Admin ('todos'): Usuario Enfermería 1 external_shifts = {$extAdminCount} (Esperado: 28) -> ".($extAdminCount === 28 ? 'PASÓ' : 'FALLÓ')."\n";

// Consulta para Jefe de TI (departamento_id = 1)
$reqJefeTI = new Request(['mes' => 2, 'anio' => 2026, 'departamento_id' => 1]);
$staffJefeTI = $controller->getAllEmpleados($reqJefeTI);
$emp7JefeTI = collect($staffJefeTI)->firstWhere('id_empleado', 7);
$extJefeTICount = count(array_filter($emp7JefeTI['external_shifts']));
echo "Jefe TI (dept 1): Usuario Enfermería 1 external_shifts = {$extJefeTICount} (Esperado: 28) -> ".($extJefeTICount === 28 ? 'PASÓ' : 'FALLÓ')."\n\n";

echo "=== 3. PRUEBA DE GUARDADO: RESTRICCIÓN PARA ADMIN ===\n";
// Admin intenta guardar TI asignándole turno M a Usuario Enfermería 1
$reqSaveAdmin = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => 'todos',
    'people' => [
        [
            'id' => 2,
            'name' => 'Jefe de Sistemas',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
        [
            'id' => 7,
            'name' => 'Usuario Enfermería 1',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
    ],
]);
$resAdmin = $controller->saveRole($reqSaveAdmin);
echo 'Status Admin guardando turno superpuesto: '.$resAdmin->getStatusCode().' (Esperado: 422) -> '.($resAdmin->getStatusCode() === 422 ? 'PASÓ' : 'FALLÓ')."\n";
echo 'Mensaje: '.json_decode($resAdmin->getContent(), true)['error']."\n\n";

echo "=== 4. PRUEBA DE GUARDADO: RESTRICCIÓN PARA JEFES ===\n";
// Jefe de TI intenta guardar TI asignándole turno M a Usuario Enfermería 1
$reqSaveJefe = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => '1',
    'people' => [
        [
            'id' => 2,
            'name' => 'Jefe de Sistemas',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
        [
            'id' => 7,
            'name' => 'Usuario Enfermería 1',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
    ],
]);
$resJefe = $controller->saveRole($reqSaveJefe);
echo 'Status Jefe guardando turno superpuesto: '.$resJefe->getStatusCode().' (Esperado: 422) -> '.($resJefe->getStatusCode() === 422 ? 'PASÓ' : 'FALLÓ')."\n";
echo 'Mensaje: '.json_decode($resJefe->getContent(), true)['error']."\n\n";

echo "=== 5. PRUEBA DE GUARDADO: RESTRICCIÓN INTERNA ENTRE ÁREAS EN MISMO ENVÍO ===\n";
// Admin envía en un solo paquete personal de Emergencias y de TI con turnos superpuestos para Usuario Enfermería 1
$reqInternal = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => 'todos',
    'people' => [
        [
            'id' => 7,
            'name' => 'Usuario Enfermería 1',
            'dept_id' => 3,
            'dept_name' => 'Emergencias',
            'shifts' => array_fill(0, 28, 'M'),
        ],
        [
            'id' => 7,
            'name' => 'Usuario Enfermería 1',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
    ],
]);
$resInternal = $controller->saveRole($reqInternal);
echo 'Status guardado con conflicto interno: '.$resInternal->getStatusCode().' (Esperado: 422) -> '.($resInternal->getStatusCode() === 422 ? 'PASÓ' : 'FALLÓ')."\n";
echo 'Mensaje: '.json_decode($resInternal->getContent(), true)['error']."\n\n";

echo "=== 6. PRUEBA DE INTEGRIDAD: HORARIO COMPATIBLE / LIBRE ===\n";
// Se permite compartir personal si no hay choque de horario (ej. Libre en TI o turno que no choque)
$reqCompatible = new Request([
    'year' => 2026,
    'month' => 'Febrero',
    'serviceId' => '1',
    'people' => [
        [
            'id' => 2,
            'name' => 'Jefe de Sistemas',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'M'),
        ],
        [
            'id' => 7,
            'name' => 'Usuario Enfermería 1',
            'dept_id' => 1,
            'dept_name' => 'Tecnología de la Información',
            'shifts' => array_fill(0, 28, 'L'),
        ],
    ],
]);
$resCompatible = $controller->saveRole($reqCompatible);
echo 'Status guardado compatible (turnos L en TI): '.$resCompatible->getStatusCode().' (Esperado: 200) -> '.($resCompatible->getStatusCode() === 200 ? 'PASÓ' : 'FALLÓ')."\n";

// Verificar que los turnos en Emergencias sigan intactos (28)
$asigsEmergencias = AsignacionTurnoDiario::where('id_rol_mensual', $rol25->id_rol_mensual)->where('id_empleado', 7)->count();
echo "Turnos de Usuario Enfermería 1 en Emergencias tras guardar TI: {$asigsEmergencias} (Esperado: 28) -> ".($asigsEmergencias === 28 ? 'PASÓ' : 'FALLÓ')."\n";
