<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

$emp35 = Empleado::find(35);
echo "Empleado with id_empleado=35:\n";
echo '  Name: '.($emp35 ? $emp35->nombre_completo : 'None').', item_id: '.($emp35 ? $emp35->item_id : 'None')."\n";

$empItem35 = Empleado::where('item_id', '35')->first();
echo "Empleado with item_id='35':\n";
echo '  id_empleado: '.($empItem35 ? $empItem35->id_empleado : 'None').', Name: '.($empItem35 ? $empItem35->nombre_completo : 'None')."\n";
