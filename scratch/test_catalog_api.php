<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\DepartamentoServicio;
use Illuminate\Contracts\Console\Kernel;

$csvFile = database_path('personal_hospital.csv');
$catalog = [];

if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
    fgetcsv($handle, 0, ';'); // skip header
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (! empty($row[9])) {
            $name = trim(preg_replace('/\s+/', ' ', $row[9]));
            if ($name !== '' && strtolower($name) !== 'sin servicio asignado') {
                $catalog[$name] = true;
            }
        }
    }
    fclose($handle);
}

$existing = DepartamentoServicio::pluck('nombre_departmento')->toArray();
$merged = array_unique(array_merge(array_keys($catalog), $existing));
sort($merged, SORT_NATURAL | SORT_FLAG_CASE);

echo 'Total áreas en catálogo: '.count($merged)."\n";
echo "Primeras 10:\n";
foreach (array_slice($merged, 0, 10) as $a) {
    echo "  - $a\n";
}
