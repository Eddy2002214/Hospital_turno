<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$seeds = [
    ['id_departamento' => 1, 'nombre_departmento' => 'Tecnología de la Información'],
    ['id_departamento' => 2, 'nombre_departmento' => 'Enfermería'],
    ['id_departamento' => 3, 'nombre_departmento' => 'Emergencias'],
    ['id_departamento' => 4, 'nombre_departmento' => 'Pediatría'],
    ['id_departamento' => 5, 'nombre_departmento' => 'Traumotoligia'],
];

foreach ($seeds as $s) {
    DB::table('departamentos_servicios')->updateOrInsert(
        ['id_departamento' => $s['id_departamento']],
        ['nombre_departmento' => $s['nombre_departmento']]
    );
}

echo "Restaurados los 5 departamentos iniciales exactamente como estaban antes:\n";
foreach (DB::table('departamentos_servicios')->whereIn('id_departamento', [1, 2, 3, 4, 5])->get() as $d) {
    echo "ID: {$d->id_departamento} | {$d->nombre_departmento}\n";
}
