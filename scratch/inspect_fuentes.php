<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\FuenteFinanciamiento;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "--- FUENTES DE FINANCIAMIENTO ---\n";
$fuentes = FuenteFinanciamiento::all();
foreach ($fuentes as $f) {
    echo "ID: {$f->id_fuente} | Codigo: {$f->codigo_fuente} | Desc: {$f->descripcion}\n";
}

echo "--- COLUMNAS EN EMPLEADOS ---\n";
$cols = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Empleados'");
foreach ($cols as $c) {
    echo "  {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
}

echo "--- COLUMNAS EN ASIGNACION_TURNOS_DIARIOS ---\n";
$cols2 = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Asignacion_Turnos_Diarios'");
foreach ($cols2 as $c) {
    echo "  {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
}
