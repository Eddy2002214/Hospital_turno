<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RoleController;
use App\Models\DepartamentoServicio;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo "   VERIFICACIÓN DE REQUERIMIENTOS DEL USUARIO\n";
echo "========================================================\n\n";

$controller = new RoleController;

// 1. Verificar getEmpleados (Matriz de captura masiva)
echo "--- 1. MATRIZ DE CAPTURA MASIVA (JEFES IDENTIFICADOS CON SU ÁREA) ---\n";
$reqMatrix = new Request(['mes' => 10, 'anio' => 2026, 'departamento_id' => 'todos']);
$staffMatrix = $controller->getEmpleados($reqMatrix);
foreach ($staffMatrix as $p) {
    if ($p['is_jefe']) {
        $etiqueta = "{$p['role']}: {$p['dept_name']}";
        echo "✔ Funcionario: {$p['name']} [JEFE]\n";
        echo "  -> Cargo mostrado: '{$etiqueta}'\n";
    }
}

// 2. Verificar getUnassigned (Asignación de Personal)
echo "\n--- 2. ASIGNACIÓN DE PERSONAL (PERSONAL DE LAVANDERÍA) ---\n";
$reqUnassigned = new Request(['mes' => 10, 'anio' => 2026]);
$unassigned = $controller->getUnassigned($reqUnassigned);

$lavanderiaStaff = collect($unassigned)->filter(function ($emp) {
    return (isset($emp['base_dept_name']) && stripos($emp['base_dept_name'], 'LAVAN') !== false) ||
           (isset($emp['dept']) && stripos($emp['dept'], 'LAVAN') !== false) ||
           (isset($emp['assigned_dept_names']) && in_array('LAVANDERIA', $emp['assigned_dept_names']));
});

echo 'Total personas encontradas para Lavandería: '.$lavanderiaStaff->count()."\n";
foreach ($lavanderiaStaff as $emp) {
    echo "✔ [ID {$emp['id']}] {$emp['name']} ({$emp['bio']})\n";
    echo "  - Cargo: {$emp['role']}\n";
    echo "  - Área actual: {$emp['dept']}\n";
    echo "  - Área base: {$emp['base_dept_name']} (ID: {$emp['base_dept_id']})\n";
    echo '  - Asignado este mes: '.json_encode($emp['assigned_dept_names'])."\n";
}

echo "\n--- 3. VERIFICAR QUE NO EXISTEN DEPARTAMENTOS DUPLICADOS ---\n";
$depts = DepartamentoServicio::select('nombre_departmento', DB::raw('count(*) as c'))
    ->groupBy('nombre_departmento')
    ->having('c', '>', 1)
    ->get();
if ($depts->isEmpty()) {
    echo "✔ Ningún departamento duplicado en la base de datos.\n";
} else {
    echo '❌ Hay departamentos duplicados: '.json_encode($depts)."\n";
}

echo "\n========================================================\n";
