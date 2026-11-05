<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
$stmt = $pdo->query('SELECT * FROM empleados LIMIT 10');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
