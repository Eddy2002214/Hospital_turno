<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

Config::set('database.connections.sqlsrv_rrhh', [
    'driver' => 'sqlsrv',
    'host' => '192.168.9.23',
    'port' => '1433',
    'database' => 'BDRRHH',
    'username' => 'RRHH-L',
    'password' => '1234567',
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'trust_server_certificate' => 'true',
]);

try {
    $results = DB::connection('sqlsrv_rrhh')->select('SELECT TOP 3 p.pperCodPer, p.pperNombre, p.pperPatern FROM perpersona p');
    echo "Laravel DB connection successful!\n";
    print_r($results);
} catch (Exception $e) {
    echo 'Connection failed: '.$e->getMessage()."\n";
}
