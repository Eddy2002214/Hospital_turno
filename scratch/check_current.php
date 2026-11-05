<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
echo "DEPARTAMENTOS:\n";
print_r($pdo->query('SELECT * FROM departamentos_servicios')->fetchAll(PDO::FETCH_ASSOC));
echo "USUARIOS:\n";
print_r($pdo->query('SELECT * FROM usuarios')->fetchAll(PDO::FETCH_ASSOC));
echo "EMPLEADOS:\n";
print_r($pdo->query('SELECT * FROM empleados')->fetchAll(PDO::FETCH_ASSOC));
