<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== LIMPIEZA TOTAL DE MYSQL (CONSERVANDO ÚNICAMENTE SUPER ADMIN) ===\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

echo "1. Vaciando tablas transaccionales de turnos y roles...\n";
$pdo->exec('TRUNCATE TABLE asignacion_turnos_diarios');
$pdo->exec('TRUNCATE TABLE roles_mensuales');
$pdo->exec('TRUNCATE TABLE cambios_turnos');
$pdo->exec('TRUNCATE TABLE licencias_vacaciones');
$pdo->exec('TRUNCATE TABLE consumos_comedor');

echo "2. Vaciando áreas y departamentos anteriores...\n";
$pdo->exec('TRUNCATE TABLE departamentos_servicios');

echo "3. Asegurando cargo Administrador de Sistema...\n";
$stmtCargo = $pdo->query("SELECT id_cargo FROM cargos WHERE nombre_cargo = 'Administrador de Sistema'");
$cargoAdminId = $stmtCargo->fetchColumn();
if (! $cargoAdminId) {
    $pdo->exec("INSERT INTO cargos (nombre_cargo) VALUES ('Administrador de Sistema')");
    $cargoAdminId = $pdo->lastInsertId();
}

// Asegurar cargos para cuando se asignen jefes en el sistema
foreach (['Jefe de Area', 'Jefe de Servicio'] as $cName) {
    $cId = $pdo->query("SELECT id_cargo FROM cargos WHERE nombre_cargo = '$cName'")->fetchColumn();
    if (! $cId) {
        $pdo->exec("INSERT INTO cargos (nombre_cargo) VALUES ('$cName')");
    }
}

echo "4. Limpiando empleados (Dejando únicamente id_empleado = 999999: Super Admin)...\n";
$pdo->exec('TRUNCATE TABLE empleados');
$stmtEmp = $pdo->prepare('
    INSERT INTO empleados (id_empleado, item_id, codigo_biometrico, ci, telefono, nombre_completo, id_cargo, id_departamento, activo, profesion_especialidad)
    VALUES (999999, "999999", "ADM-001", "0000000", "111111", "Super Admin", ?, NULL, 1, "Administración de Sistemas")
');
$stmtEmp->execute([$cargoAdminId]);

echo "5. Limpiando usuarios (Dejando únicamente id_usuario = 1: admin)...\n";
$pdo->exec('TRUNCATE TABLE usuarios');
$hash = password_hash('admin', PASSWORD_BCRYPT);
$pdo->exec("
    INSERT INTO usuarios (id_usuario, nombre_usuario, correo, password_hash, rol, id_empleado)
    VALUES (1, 'admin', 'admin@hospital.com', '$hash', 'Administrador', 999999)
");

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\n✔ Limpieza completada con éxito.\n";
echo "Estado actual de MySQL:\n";
$tables = ['usuarios', 'empleados', 'departamentos_servicios', 'cargos', 'roles_mensuales', 'asignacion_turnos_diarios'];
foreach ($tables as $t) {
    echo "- $t: ".$pdo->query("SELECT count(*) FROM `$t`")->fetchColumn()." registros\n";
}
echo "Admin en usuarios:\n";
print_r($pdo->query('SELECT * FROM usuarios')->fetchAll(PDO::FETCH_ASSOC));
echo "Admin en empleados:\n";
print_r($pdo->query('SELECT * FROM empleados')->fetchAll(PDO::FETCH_ASSOC));
