<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Models\AsignacionTurnoDiario;
use App\Models\RolMensual;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "--- 1. Probar guardado con Fuentes de Financiamiento variadas ---\n";
$roleCtrl = app(RoleController::class);

$people = [
    [
        'id' => 35, // Lizeth (Jefe)
        'dept_id' => 8,
        'ff' => 'TGN',
        'is_jefe' => true,
        'shifts' => array_fill(0, 30, 'L'),
    ],
    [
        'id' => 680, // Jorge Tellez
        'dept_id' => 8,
        'ff' => 'GOB',
        'is_jefe' => false,
        'shifts' => array_fill(0, 30, 'M'),
    ],
    [
        'id' => 1459, // Jose Antonio Romero
        'dept_id' => 8,
        'ff' => 'IDH/HIPC',
        'is_jefe' => false,
        'shifts' => array_fill(0, 30, 'M'),
    ],
    [
        'id' => 482, // Mariel Parada
        'dept_id' => 8,
        'ff' => 'RP',
        'is_jefe' => false,
        'shifts' => array_fill(0, 30, 'M*'),
    ],
];

$saveReq = Request::create('/api/roles/save', 'POST', [
    'year' => '2026',
    'month' => 'Noviembre',
    'serviceId' => '8',
    'people' => $people,
]);

$saveRes = $roleCtrl->saveRole($saveReq);
echo 'Save Response: '.$saveRes->getContent()."\n";

echo "\n--- 2. Verificar id_fuente en la base de datos para Noviembre 2026 ---\n";
$rol = RolMensual::where('mes', 11)->where('anio', 2026)->where('id_departamento', 8)->first();
$asigs = AsignacionTurnoDiario::with(['fuente', 'empleado'])->where('id_rol_mensual', $rol->id_rol_mensual)->get()->groupBy('id_empleado');
foreach ($asigs as $eId => $items) {
    $eName = $items->first()->empleado->nombre_completo ?? "ID {$eId}";
    $fName = $items->first()->fuente->codigo_fuente ?? 'null';
    $fId = $items->first()->id_fuente;
    echo "  - {$eName}: id_fuente={$fId} ({$fName})\n";
}

echo "\n--- 3. Verificar que getEmpleados devuelva la ff correcta ---\n";
$getReq = Request::create('/api/empleados', 'GET', [
    'mes' => 11,
    'anio' => 2026,
    'departamento_id' => 8,
]);
$getRes = $roleCtrl->getEmpleados($getReq);
$data = is_array($getRes) ? $getRes : $getRes->toArray();
foreach ($data as $p) {
    echo "  - {$p['name']}: ff={$p['ff']}\n";
}

echo "\n--- 4. Verificar ReportController printReport ---\n";
$reportCtrl = app(ReportController::class);
$printReq = Request::create('/reporte/imprimir', 'GET', [
    'mes' => 11,
    'anio' => 2026,
    'departamento_id' => 8,
]);
$view = $reportCtrl->printReport($printReq);
$html = $view->render();
echo 'Reporte impreso renderizado OK (Longitud: '.strlen($html)." bytes)\n";
if (strpos($html, 'GOB') !== false && strpos($html, 'IDH/HIPC') !== false && strpos($html, 'RP') !== false) {
    echo "VERIFICADO: El HTML impreso contiene las fuentes GOB, IDH/HIPC y RP correctamente!\n";
} else {
    echo "ALERTA: Faltan fuentes en el HTML impreso.\n";
}
