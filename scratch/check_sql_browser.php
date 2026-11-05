<?php

echo "--- Testing DSN Variations ---\n";

// 2. Test common variations of DSN
$variations = [
    'sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5',
    'sqlsrv:server=tcp:192.168.9.23,1433;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5',
    'sqlsrv:server=192.168.9.23,1433;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5',
    "sqlsrv:server=192.168.9.23\SQLEXPRESS;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5",
];

foreach ($variations as $dsn) {
    echo "\nTrying DSN: $dsn\n";
    try {
        $c = new PDO($dsn, 'RRHH-L', '1234567');
        echo "SUCCESS!\n";
        break;
    } catch (Exception $e) {
        echo 'FAIL: '.$e->getMessage()."\n";
    }
}
