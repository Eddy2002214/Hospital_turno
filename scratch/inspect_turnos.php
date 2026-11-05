<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Turno;
use Illuminate\Contracts\Console\Kernel;

$turnos = Turno::all();
foreach ($turnos as $t) {
    echo "ID: {$t->id_turno} | Cod: {$t->codigo_turno} | Nombre: {$t->nombre_turno} | Entrada: {$t->hora_entrada} | Salida: {$t->hora_salida}\n";
}
