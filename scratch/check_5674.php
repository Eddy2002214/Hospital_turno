<?php

$c = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
$stmt = $c->query("SELECT p.pperCodPer, p.pperIdePer, p.pperNombre, p.pperPatern, p.pperMatern 
    FROM perpersona p WHERE p.pperCodPer = 5674 OR p.pperIdePer LIKE '%5674%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
