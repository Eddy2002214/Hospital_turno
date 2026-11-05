<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empleado;

$emp = Empleado::with('usuario')->where('nombre_completo', 'like', '%ALEIDA YSENIA%')->first();
if ($emp) {
    echo "ID Funcionario (id_empleado): {$emp->id_empleado}\n";
    echo "Código Biométrico (codigo_biometrico): {$emp->codigo_biometrico}\n";
    echo "Ítem (item_id): {$emp->item_id}\n";
    echo "Carnet (ci): {$emp->ci}\n";
    echo "Usuario (usuarios.nombre_usuario): " . ($emp->usuario ? $emp->usuario->nombre_usuario : 'sin usuario') . "\n";
} else {
    echo "No encontrada\n";
}

