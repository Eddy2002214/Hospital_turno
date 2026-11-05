<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital;charset=utf8mb4', 'root', '', [
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
]);

// Orden ordenado por dependencias
$tablesOrder = [
    'departamentos_servicios',
    'cargos',
    'fuentes_financiamiento',
    'turnos',
    'feriados',
    'empleados',
    'usuarios',
    'roles_mensuales',
    'asignacion_turnos_diarios',
    'cambios_turnos',
    'licencias_vacaciones',
];

$sqlDump = "-- =====================================================\n";
$sqlDump .= "-- ESQUEMA DE BASE DE DATOS: control_turnos_hospital\n";
$sqlDump .= "-- Base de Datos limpia para Sistema de Control de Turnos\n";
$sqlDump .= "-- =====================================================\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tablesOrder as $table) {
    $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $createSql = $row[1];
    $sqlDump .= "-- -----------------------------------------------------\n";
    $sqlDump .= "-- Tabla: $table\n";
    $sqlDump .= "-- -----------------------------------------------------\n";
    $sqlDump .= $createSql.";\n\n";
}

$sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__.'/../esquema_base_de_datos.sql', $sqlDump);
echo "Esquema exportado con codificación UTF-8 limpia y orden de dependencias.\n";
