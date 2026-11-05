<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');

// Ensure Administrador de Sistema cargo exists
$cargoAdminId = $pdo->query("SELECT id_cargo FROM cargos WHERE nombre_cargo = 'Administrador de Sistema'")->fetchColumn();
if (! $cargoAdminId) {
    $pdo->exec("INSERT INTO cargos (nombre_cargo) VALUES ('Administrador de Sistema')");
    $cargoAdminId = $pdo->lastInsertId();
}

// Upsert Super Admin employee with id 999999
$pdo->exec("
    INSERT INTO empleados (id_empleado, item_id, codigo_biometrico, ci, telefono, nombre_completo, id_cargo, id_departamento, activo, profesion_especialidad)
    VALUES (999999, '999999', 'ADM-001', '0000000', '111111', 'Super Admin', $cargoAdminId, NULL, 1, 'Administración de Sistemas')
    ON DUPLICATE KEY UPDATE
        codigo_biometrico = 'ADM-001',
        nombre_completo = 'Super Admin',
        id_cargo = $cargoAdminId
");

// Update user 1 to point to 999999
$pdo->exec('UPDATE usuarios SET id_empleado = 999999 WHERE id_usuario = 1');

echo "Admin employee (999999) and user updated successfully.\n";
