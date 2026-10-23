<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\DepartamentoServicio;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "========================================================\n";
echo "   TEST DE RESTRICCIONES Y CÓDIGOS DE ÁREAS\n";
echo "========================================================\n\n";

// 1. Probar GET /api/departamentos
$depts = DepartamentoServicio::with(['empleados' => function ($q) {
    $q->whereHas('cargo', function ($q2) {
        $q2->whereIn('nombre_cargo', ['Jefe de Area', 'Jefe de Servicio']);
    });
}])->get();

echo "--- 1. CÓDIGO DE JEFE PARA INGRESAR A PLANILLA ---\n";
foreach ($depts as $d) {
    $jefe = $d->empleados->first();
    if ($jefe) {
        echo "✔ Área: {$d->nombre_departmento}\n";
        echo "  - Jefe: {$jefe->nombre_completo}\n";
        echo "  - Código para planilla: {$jefe->codigo_biometrico}\n";
    }
}

// 2. Probar restricción de duplicados en /api/departamentos
echo "\n--- 2. TEST DE RESTRICCIÓN DE ÁREAS REPETIDAS ---\n";
$reqDuplicate = Request::create('/api/departamentos', 'POST', [
    'nombre_departmento' => '  lavanderia  ', // minúsculas y espacios
]);

$response = app()->handle($reqDuplicate);
echo "Status code al intentar crear 'lavanderia': ".$response->getStatusCode()."\n";
echo 'Respuesta JSON: '.$response->getContent()."\n";

if ($response->getStatusCode() === 422) {
    echo "✔ RESTRICCIÓN EXITOSA: Se rechazó la creación de área duplicada.\n";
} else {
    echo "❌ ERROR: No se rechazó el duplicado.\n";
}

echo "\n========================================================\n";
