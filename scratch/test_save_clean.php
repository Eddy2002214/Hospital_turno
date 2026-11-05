<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = app(RoleController::class);

// First let's artificially create a ghost role in Abril 2026 (mes 4)
$ghost = RolMensual::create([
    'mes' => 4,
    'anio' => 2026,
    'id_departamento' => 8, // Finanzas
    'id_jefe' => 35,
]);

// Now let's save Abril 2026 with only Servicio Social (dept 184)
$people = [
    [
        'id' => 43, // Aleida
        'dept_id' => 184,
        'shifts' => array_fill(0, 30, 'L'),
    ],
    [
        'id' => 271, // Ernesto
        'dept_id' => 184,
        'shifts' => array_fill(0, 30, 'M'),
    ],
];

$req = Request::create('/api/roles/save', 'POST', [
    'year' => '2026',
    'month' => 'Abril',
    'serviceId' => 'todos',
    'people' => $people,
]);

$res = $controller->saveRole($req);
echo 'Save response: '.$res->getContent()."\n";

// Check if ghost role was purged
$rolesAbril = RolMensual::where('mes', 4)->where('anio', 2026)->get();
echo "Roles en Abril tras save:\n";
foreach ($rolesAbril as $r) {
    echo "  - Rol ID: {$r->id_rol_mensual} | Dept: {$r->id_departamento}\n";
}
// Clean up test data for Abril 2026
AsignacionTurnoDiario::whereIn('id_rol_mensual', $rolesAbril->pluck('id_rol_mensual'))->delete();
RolMensual::whereIn('id_rol_mensual', $rolesAbril->pluck('id_rol_mensual'))->delete();
echo "Abril test data cleaned up.\n";
