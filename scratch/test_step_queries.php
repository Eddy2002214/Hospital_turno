<?php

use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "--- PASO 1: Departamentos ---\n";
$q1 = DB::connection('sqlsrv')->select('SELECT id_departamento, nombre_departmento FROM Departamentos_Servicios;');
foreach ($q1 as $r) {
    echo "ID: {$r->id_departamento} | {$r->nombre_departmento}\n";
}

echo "\n--- PASO 2: Jefes ---\n";
$q2 = DB::connection('sqlsrv')->select("
SELECT e.id_empleado, e.codigo_biometrico, e.nombre_completo AS Jefe, c.nombre_cargo AS Cargo, e.id_departamento
FROM Empleados e
INNER JOIN Cargos c ON c.id_cargo = e.id_cargo
WHERE c.nombre_cargo IN ('Jefe de Area', 'Jefe de Servicio');
");
foreach ($q2 as $r) {
    echo "Emp ID: {$r->id_empleado} | Jefe: {$r->Jefe} | Dept ID: {$r->id_departamento}\n";
}

echo "\n--- PASO 3: Usuarios Jefes ---\n";
$q3 = DB::connection('sqlsrv')->select("
SELECT u.id_usuario, u.nombre_usuario, u.rol, e.id_empleado, e.nombre_completo AS Jefe_Titular
FROM Usuarios u
INNER JOIN Empleados e ON e.id_empleado = u.id_empleado
WHERE u.rol IN ('Jefe de Area', 'Jefe de Servicio');
");
foreach ($q3 as $r) {
    echo "User ID: {$r->id_usuario} | User: {$r->nombre_usuario} | Rol: {$r->rol} | Jefe: {$r->Jefe_Titular}\n";
}

echo "\n--- PASO 4: Empleados Operadores ---\n";
$q4 = DB::connection('sqlsrv')->select('
SELECT e.id_empleado, e.codigo_biometrico, e.nombre_completo AS Empleado, c.nombre_cargo AS Cargo, e.id_departamento
FROM Empleados e
INNER JOIN Cargos c ON c.id_cargo = e.id_cargo
WHERE e.id_cargo = 2;
');
foreach ($q4 as $r) {
    echo "Emp ID: {$r->id_empleado} | Empleado: {$r->Empleado} | Dept ID: {$r->id_departamento}\n";
}

echo "\n--- PASO 5: Roles Mensuales ---\n";
$q5 = DB::connection('sqlsrv')->select('
SELECT rm.id_rol_mensual, rm.mes, rm.anio, d.id_departamento, d.nombre_departmento AS Departamento
FROM Roles_Mensuales rm
INNER JOIN Departamentos_Servicios d ON d.id_departamento = rm.id_departamento;
');
foreach ($q5 as $r) {
    echo "Rol ID: {$r->id_rol_mensual} | Mes: {$r->mes}/{$r->anio} | Dept ID: {$r->id_departamento} ({$r->Departamento})\n";
}

echo "\n--- PASO 6: Asignaciones Turnos Diarios ---\n";
$q6 = DB::connection('sqlsrv')->select('
SELECT TOP 5 atd.id_rol_mensual, e.nombre_completo AS Empleado, atd.fecha, t.codigo_turno
FROM Asignacion_Turnos_Diarios atd
INNER JOIN Empleados e ON e.id_empleado = atd.id_empleado
INNER JOIN Turnos t ON t.id_turno = atd.id_turno;
');
foreach ($q6 as $r) {
    echo "Rol ID: {$r->id_rol_mensual} | Emp: {$r->Empleado} | Fecha: {$r->fecha} | Turno: {$r->codigo_turno}\n";
}
