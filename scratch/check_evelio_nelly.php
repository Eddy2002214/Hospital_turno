<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\Empleado;
use Illuminate\Contracts\Console\Kernel;

echo "--- VERIFICANDO ASIGNACIONES DE EVELIO Y NELLY ---\n";
foreach ([31 => 'Evelio Ancieta', 32 => 'Nelly Condo'] as $id => $name) {
    $emp = Empleado::find($id);
    $asigsCount = AsignacionTurnoDiario::where('id_empleado', $id)->count();
    echo "ID: $id | $name | Dept en Empleados: {$emp?->id_departamento} | Turnos asignados en BD: $asigsCount\n";
}
