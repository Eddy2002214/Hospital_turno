<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Services\RrhhService;
use Illuminate\Contracts\Console\Kernel;

$allRrhh = app(RrhhService::class)->getPersonalFromSqlServer(false);
$found = [];
foreach ($allRrhh as $r) {
    if (stripos($r['nombre_completo'], 'CONDO') !== false) {
        $found[] = $r;
    }
}
print_r($found);
