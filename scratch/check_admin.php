<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
$admin = $pdo->query('SELECT * FROM empleados WHERE id_empleado = 1')->fetch(PDO::FETCH_ASSOC);
echo "Admin in empleados:\n";
print_r($admin);

$sqlSrv = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
$stmt = $sqlSrv->query("SELECT p.pperCodPer, p.pperNombre FROM perfuncionariohuj f INNER JOIN perpersona p ON f.ppercodper = p.pperCodPer WHERE f.estado = 'V' AND p.pperCodPer = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'SQL Server has pperCodPer = 1? '.($row ? json_encode($row) : 'NO')."\n";
