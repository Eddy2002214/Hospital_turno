<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

$r = RolMensual::find(29);
echo "Rol 29: anio={$r->anio}, mes={$r->mes}, dept={$r->id_departamento}, jefe={$r->id_jefe}, created_at={$r->created_at}, updated_at={$r->updated_at}\n";
