<?php

$c = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
$stmt = $c->query("SELECT p.pperCodPer, count(*) as c 
    FROM perfuncionariohuj f 
    INNER JOIN perpersona p ON f.ppercodper = p.pperCodPer 
    WHERE f.estado = 'V' 
    GROUP BY p.pperCodPer 
    HAVING count(*) > 1");
$dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Duplicates of pperCodPer in active staff: '.count($dups)."\n";
