<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

try {
    $cols = DB::connection('sqlsrv_rrhh')->select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'perfuncionariohuj'");
    echo "Columnas en perfuncionariohuj:\n";
    foreach ($cols as $c) {
        echo "  {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
    }
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
