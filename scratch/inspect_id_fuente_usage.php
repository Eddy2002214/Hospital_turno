<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$res = DB::select('SELECT id_fuente, COUNT(*) as cnt FROM Asignacion_Turnos_Diarios GROUP BY id_fuente');
foreach ($res as $r) {
    echo 'id_fuente: '.var_export($r->id_fuente, true).' -> '.$r->cnt." filas\n";
}
