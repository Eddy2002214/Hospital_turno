<?php

use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "--- CONSULTA 1: JEFES Y SUS EMPLEADOS POR DEPARTAMENTO ---\n";
$sql1 = "
SELECT 
    d.id_departamento,
    d.nombre_departmento AS departamento,
    jefe.nombre_completo AS nombre_jefe,
    cargo_jefe.nombre_cargo AS cargo_jefe,
    emp.id_empleado,
    emp.codigo_biometrico AS codigo_emp,
    emp.nombre_completo AS nombre_empleado,
    cargo_emp.nombre_cargo AS cargo_empleado
FROM Departamentos_Servicios d
INNER JOIN Empleados jefe 
    ON jefe.id_departamento = d.id_departamento
INNER JOIN Cargos cargo_jefe 
    ON cargo_jefe.id_cargo = jefe.id_cargo 
    AND cargo_jefe.nombre_cargo IN ('Jefe de Area', 'Jefe de Servicio')
LEFT JOIN Empleados emp 
    ON emp.id_departamento = d.id_departamento 
    AND emp.id_empleado != jefe.id_empleado
LEFT JOIN Cargos cargo_emp 
    ON cargo_emp.id_cargo = emp.id_cargo
ORDER BY d.nombre_departmento, emp.nombre_completo;
";

$res1 = DB::connection('sqlsrv')->select($sql1);
foreach ($res1 as $r) {
    echo "Depto: {$r->departamento} | Jefe: {$r->nombre_jefe} ({$r->cargo_jefe}) | Empleado: ".($r->nombre_empleado ?: '(Sin empleados aún)').' ['.($r->cargo_empleado ?: '-')."]\n";
}

echo "\n--- CONSULTA 2: JEFES Y SUS EMPLEADOS POR ROL MENSUAL (OCTUBRE 2026) ---\n";
$sql2 = "
SELECT DISTINCT
    rm.mes,
    rm.anio,
    d.nombre_departmento AS departamento,
    jefe.nombre_completo AS nombre_jefe,
    emp.codigo_biometrico AS codigo_emp,
    emp.nombre_completo AS nombre_empleado,
    cargo_emp.nombre_cargo AS cargo_empleado
FROM Roles_Mensuales rm
INNER JOIN Departamentos_Servicios d 
    ON d.id_departamento = rm.id_departamento
INNER JOIN Empleados jefe 
    ON jefe.id_departamento = d.id_departamento
INNER JOIN Cargos cargo_jefe 
    ON cargo_jefe.id_cargo = jefe.id_cargo 
    AND cargo_jefe.nombre_cargo IN ('Jefe de Area', 'Jefe de Servicio')
INNER JOIN Asignacion_Turnos_Diarios atd 
    ON atd.id_rol_mensual = rm.id_rol_mensual
INNER JOIN Empleados emp 
    ON emp.id_empleado = atd.id_empleado
INNER JOIN Cargos cargo_emp 
    ON cargo_emp.id_cargo = emp.id_cargo
WHERE rm.mes = 10 AND rm.anio = 2026 AND emp.id_empleado != jefe.id_empleado
ORDER BY d.nombre_departmento, emp.nombre_completo;
";

$res2 = DB::connection('sqlsrv')->select($sql2);
foreach ($res2 as $r) {
    echo "Mes: {$r->mes}/{$r->anio} | Depto: {$r->departamento} | Jefe: {$r->nombre_jefe} | Empleado en Rol: {$r->nombre_empleado} ({$r->cargo_empleado})\n";
}
