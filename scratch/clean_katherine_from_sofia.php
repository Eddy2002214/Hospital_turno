<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\AsignacionTurnoDiario;
use Illuminate\Contracts\Console\Kernel;

echo "--- ELIMINANDO ASIGNACION ERRONEA DE KATHERINE EN ROL 2 (COMPRAS MENORES) ---\n";
$deleted = AsignacionTurnoDiario::where('id_rol_mensual', 2)
    ->where('id_empleado', 28)
    ->delete();

echo "Turnos eliminados de Katherine en el área de Sofía: $deleted\n";

$remainingInRol2 = AsignacionTurnoDiario::where('id_rol_mensual', 2)->count();
echo "Turnos restantes en el Rol de Sofía (Rol 2): $remainingInRol2 (debe ser 31, solo Sofía)\n";
