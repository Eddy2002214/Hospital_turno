<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
print_r($pdo->query('DESCRIBE usuarios')->fetchAll(PDO::FETCH_ASSOC));
