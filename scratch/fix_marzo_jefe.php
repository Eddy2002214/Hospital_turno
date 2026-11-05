<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;

AsignacionTurnoDiario::where('id_rol_mensual', 16)->where('id_empleado', 29)->delete();

$inserts = [];
for ($d = 1; $d <= 31; $d++) {
    $inserts[] = [
        'id_rol_mensual' => 16,
        'id_empleado' => 36,
        'id_fuente' => null,
        'fecha' => sprintf('2026-03-%02d', $d),
        'id_turno' => 5,
    ];
}
AsignacionTurnoDiario::insert($inserts);
RolMensual::where('id_rol_mensual', 16)->update(['id_jefe' => 36]);

echo "Successfully updated Marzo rol 16 to Victoria Endara (id_empleado = 36)!\n";
