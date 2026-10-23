<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\DepartamentoServicio;
use Illuminate\Contracts\Console\Kernel;

$csvFile = __DIR__.'/../database/personal_hospital.csv';
$csvDepts = [];

if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
    fgetcsv($handle, 0, ';'); // skip header
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (! empty($row[9])) {
            $name = trim($row[9]);
            if ($name !== '' && strtolower($name) !== 'sin servicio asignado') {
                $csvDepts[$name] = true;
            }
        }
    }
    fclose($handle);
}

$dbDepts = DepartamentoServicio::pluck('nombre_departmento')->toArray();

echo 'Total en CSV: '.count($csvDepts)."\n";
echo 'Total en DB: '.count($dbDepts)."\n";

$all = array_unique(array_merge(array_keys($csvDepts), $dbDepts));
sort($all);

echo 'Total consolidado: '.count($all)."\n\n";
foreach ($all as $d) {
    $inDb = in_array($d, $dbDepts) ? '[YA REGISTRADA]' : '[DISPONIBLE PARA CREAR]';
    echo sprintf("%-45s %s\n", $d, $inDb);
}
