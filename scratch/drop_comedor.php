<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');
$pdo->exec('DROP TABLE IF EXISTS `consumos_comedor`');
echo "Tabla consumos_comedor eliminada correctamente de MySQL.\n";

require __DIR__.'/export_schema.php';
