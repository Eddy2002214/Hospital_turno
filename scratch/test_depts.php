<?php

$c = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
$sql = "SELECT DISTINCT RTRIM(LTRIM(d.pdepDescri)) as dep
        FROM perfuncionariohuj f 
        LEFT JOIN perdeparta d ON f.codDeparta = d.pdepCodDep
        WHERE f.estado = 'V' AND d.pdepDescri IS NOT NULL
        ORDER BY dep ASC";
$stmt = $c->query($sql);
$deps = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Departamentos en SQL Server ('.count($deps)."):\n";
print_r($deps);
