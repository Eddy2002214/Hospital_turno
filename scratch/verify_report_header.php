<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ReportController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = app(ReportController::class);

echo "--- PRUEBA ÁREA ESPECÍFICA (SERVICIO SOCIAL - ID 184) ---\n";
$req1 = Request::create('/reporte/imprimir', 'GET', [
    'mes' => 2,
    'anio' => 2026,
    'departamento_id' => 184,
]);
$view1 = $controller->printReport($req1);
$rendered1 = $view1->render();
echo 'Renderizado OK (Tamaño: '.strlen($rendered1)." bytes)\n";
if (strpos($rendered1, 'SERVICIO SOCIAL') !== false) {
    echo "VERIFICADO: El título contiene 'SERVICIO SOCIAL' correctamente.\n";
} else {
    echo "ALERTA: No se encontró 'SERVICIO SOCIAL' en el reporte.\n";
}

echo "\n--- PRUEBA 'TODOS LOS SERVICIOS' ---\n";
$req2 = Request::create('/reporte/imprimir', 'GET', [
    'mes' => 2,
    'anio' => 2026,
    'departamento_id' => 'todos',
]);
$view2 = $controller->printReport($req2);
$rendered2 = $view2->render();
echo 'Renderizado OK (Tamaño: '.strlen($rendered2)." bytes)\n";
if (strpos($rendered2, 'TODOS LOS SERVICIOS') !== false) {
    echo "VERIFICADO: El título contiene 'TODOS LOS SERVICIOS' correctamente.\n";
} else {
    echo "ALERTA: No se encontró 'TODOS LOS SERVICIOS' en el reporte.\n";
}
