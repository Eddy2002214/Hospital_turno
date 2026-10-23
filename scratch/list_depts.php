<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\DepartamentoServicio;
use Illuminate\Contracts\Console\Kernel;

foreach (DepartamentoServicio::all() as $d) {
    echo "ID: {$d->id_departamento} | Nombre: {$d->nombre_departmento}\n";
}
