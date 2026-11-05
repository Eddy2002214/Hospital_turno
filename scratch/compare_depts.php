<?php

$c = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
$pdo = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');

// MySQL depts
$mysqlDepts = $pdo->query('SELECT id_departamento, nombre_departmento FROM departamentos_servicios')->fetchAll(PDO::FETCH_KEY_PAIR);

// SQL Server depts
$sql = "SELECT DISTINCT RTRIM(LTRIM(d.pdepDescri)) as dep
        FROM perfuncionariohuj f 
        LEFT JOIN perdeparta d ON f.codDeparta = d.pdepCodDep
        WHERE f.estado = 'V' AND d.pdepDescri IS NOT NULL
        ORDER BY dep ASC";
$sqlDepts = $c->query($sql)->fetchAll(PDO::FETCH_COLUMN);

echo 'MySQL Depts count: '.count($mysqlDepts)."\n";
echo 'SQL Server Depts count: '.count($sqlDepts)."\n";

$matched = 0;
foreach ($sqlDepts as $sd) {
    $found = false;
    foreach ($mysqlDepts as $mid => $mName) {
        if (strcasecmp(trim($mName), trim($sd)) === 0) {
            $matched++;
            $found = true;
            break;
        }
    }
    if (! $found) {
        echo "Not in MySQL yet: $sd\n";
    }
}
echo "Matched: $matched / ".count($sqlDepts)."\n";
