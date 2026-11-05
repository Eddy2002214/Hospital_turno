<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in control_turnos_hospital:\n";
foreach ($tables as $t) {
    $count = $pdo->query("SELECT count(*) FROM `$t`")->fetchColumn();
    echo "- $t ($count rows)\n";
}

echo "\nForeign keys referencing empleados or others:\n";
$fks = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'control_turnos_hospital' AND REFERENCED_TABLE_NAME IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($fks as $fk) {
    echo "{$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
}
