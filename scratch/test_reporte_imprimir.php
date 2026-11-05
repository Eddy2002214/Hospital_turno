<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ReportController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$controller = app(ReportController::class);

echo "--- PRUEBA 1: mes=2, anio=2026, departamento_id='todos' ---\n";
try {
    $req = Request::create('/reporte/imprimir', 'GET', [
        'mes' => 2,
        'anio' => 2026,
        'departamento_id' => 'todos',
    ]);
    $res = $controller->printReport($req);
    echo 'Éxito al renderizar vista. Contenido generado: '.strlen($res->render())." bytes.\n";
} catch (Throwable $e) {
    echo 'ERROR CAPTURADO: '.$e->getMessage().' en '.$e->getFile().':'.$e->getLine()."\n";
}

echo "--- PRUEBA 2: mes=2, anio=2026, sin departamento_id ---\n";
try {
    $req = Request::create('/reporte/imprimir', 'GET', [
        'mes' => 2,
        'anio' => 2026,
    ]);
    $res = $controller->printReport($req);
    echo 'Éxito al renderizar vista. Contenido generado: '.strlen($res->render())." bytes.\n";
} catch (Throwable $e) {
    echo 'ERROR CAPTURADO: '.$e->getMessage().' en '.$e->getFile().':'.$e->getLine()."\n";
}
