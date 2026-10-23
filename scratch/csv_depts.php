<?php

$f = fopen(__DIR__.'/../database/personal_hospital.csv', 'r');
$header = fgetcsv($f, 0, ';');
$depts = [];
while (($row = fgetcsv($f, 0, ';')) !== false) {
    if (isset($row[9])) {
        $d = trim($row[9]);
        $depts[$d] = ($depts[$d] ?? 0) + 1;
    }
}
fclose($f);
arsort($depts);
echo "DEPARTAMENTOS EN EL CSV OFICIAL DEL HOSPITAL:\n";
foreach ($depts as $d => $count) {
    echo sprintf("%-40s : %d empleados\n", $d, $count);
}
