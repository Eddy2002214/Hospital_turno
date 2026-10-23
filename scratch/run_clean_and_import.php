<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Cargo;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "=========================================================\n";
echo "   LIMPIEZA DE BASE DE DATOS E IMPORTACIÓN DE 20 AL AZAR\n";
echo "=========================================================\n\n";

// 1. Verificación de Backup
$backupPath = __DIR__.'/../database/backups/backup_control_turnos_hospital_pre_limpieza.sql';
if (! file_exists($backupPath) || filesize($backupPath) < 1000) {
    exit("ERROR: No se encontró el backup previo de seguridad. Abortando por seguridad.\n");
}
echo '✔ Backup de seguridad verificado ('.number_format(filesize($backupPath) / 1024, 2)." KB).\n\n";

// 2. Limpieza de base de datos
DB::statement('SET FOREIGN_KEY_CHECKS = 0');

echo "1. Limpiando asignaciones y roles mensuales de prueba...\n";
DB::table('asignacion_turnos_diarios')->truncate();
DB::table('roles_mensuales')->truncate();
DB::table('cambios_turnos')->truncate();
DB::table('licencias_vacaciones')->truncate();

echo "2. Limpiando usuarios de prueba (Conservando únicamente a 'admin')...\n";
// Mantener solo id_usuario = 1 o nombre_usuario = 'admin'
DB::table('usuarios')->where('id_usuario', '>', 1)->delete();
$adminUser = DB::table('usuarios')->where('id_usuario', 1)->first();
echo "   -> Usuario conservado: [ID: {$adminUser->id_usuario}] {$adminUser->nombre_usuario} ({$adminUser->rol})\n";

echo "3. Limpiando empleados de prueba (Conservando perfil del Administrador)...\n";
// El admin está vinculado a id_empleado = 1 (Super Admin, ADM-001) para que el login con código funcione
DB::table('empleados')->where('id_empleado', '>', 1)->delete();
$adminEmp = DB::table('empleados')->where('id_empleado', 1)->first();
echo "   -> Empleado conservado para login: [ID: {$adminEmp->id_empleado}] {$adminEmp->nombre_completo} (Código: {$adminEmp->codigo_biometrico})\n";

DB::statement('SET FOREIGN_KEY_CHECKS = 1');
echo "✔ Limpieza completada con éxito.\n\n";

// 3. Lectura del archivo CSV
$csvFile = __DIR__.'/../database/personal_hospital.csv';
if (! file_exists($csvFile)) {
    exit("ERROR: No se encontró el archivo database/personal_hospital.csv.\n");
}

$rows = [];
if (($handle = fopen($csvFile, 'r')) !== false) {
    $header = fgetcsv($handle, 0, ';');
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        if (count($data) >= 11 && ! empty(trim($data[0]))) {
            $rows[] = [
                'codigo' => trim($data[0]),
                'ci' => trim($data[1]),
                'nombres' => trim($data[2]),
                'paterno' => trim($data[3]),
                'materno' => trim($data[4]),
                'nombre_completo' => trim($data[5]),
                'celular' => trim($data[6]),
                'telefono_fijo' => trim($data[7]),
                'cargo' => trim($data[8]),
                'servicio' => trim($data[9]),
                'especialidad' => trim($data[10]),
                'estado' => trim($data[11] ?? 'ACTIVO'),
            ];
        }
    }
    fclose($handle);
}

echo 'Total de funcionarios disponibles en el archivo CSV: '.count($rows)."\n\n";

// Función para corregir caracteres y asegurar UTF-8 válido
function cleanText($str)
{
    if (! $str) {
        return null;
    }
    // Si no es UTF-8 válido, convertir de ISO-8859-1 / Windows-1252 a UTF-8
    if (! mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
    }
    // Corregir posibles bytes huérfanos o signos de interrogación
    $str = str_replace(["\xEF\xBF\xBD", ''], 'Ñ', $str);
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
}

// 4. Selección aleatoria de 20 funcionarios
shuffle($rows);
$selected = array_slice($rows, 0, 20);

echo "=========================================================\n";
echo "   LOS 20 FUNCIONARIOS SELECCIONADOS AL AZAR:\n";
echo "=========================================================\n";

$inserted = [];
$counter = 1;

foreach ($selected as $item) {
    $nombre = cleanText($item['nombre_completo']);
    $cargoName = cleanText($item['cargo']);
    $servicioName = cleanText($item['servicio']);
    $especialidad = cleanText($item['especialidad']);
    $ci = cleanText($item['ci']);
    if ($ci === 'No Tiene' || $ci === '0' || empty($ci)) {
        $ci = null;
    }

    $celular = cleanText($item['celular']);
    $fijo = cleanText($item['telefono_fijo']);
    $telefono = null;
    if ($celular && $celular !== '0' && $celular !== 'Sin Celular' && $celular !== '1') {
        $telefono = $celular;
    } elseif ($fijo && $fijo !== '0' && $fijo !== 'Sin Telefono' && $fijo !== '1') {
        $telefono = $fijo;
    }

    // Gestionar Cargo
    $cargo = null;
    if ($cargoName) {
        $cargo = Cargo::firstOrCreate(['nombre_cargo' => $cargoName]);
    }

    // Gestionar Departamento/Servicio
    $departamento = null;
    if ($servicioName && $servicioName !== 'Sin Servicio Asignado') {
        $departamento = DepartamentoServicio::firstOrCreate(['nombre_departmento' => $servicioName]);
    }

    // Crear Empleado
    $codigoBiometrico = cleanText($item['codigo']);

    // Verificar si el código ya existiera para evitar conflicto con ADM-001
    if ($codigoBiometrico === 'ADM-001' || $codigoBiometrico == '1') {
        $codigoBiometrico = 'EMP-'.$codigoBiometrico;
    }

    $empleado = Empleado::create([
        'item_id' => cleanText($item['codigo']),
        'codigo_biometrico' => $codigoBiometrico,
        'ci' => $ci,
        'telefono' => $telefono,
        'nombre_completo' => $nombre,
        'id_cargo' => $cargo ? $cargo->id_cargo : null,
        'id_departamento' => $departamento ? $departamento->id_departamento : null,
        'activo' => 1,
        'profesion_especialidad' => $especialidad,
    ]);

    $inserted[] = [
        'num' => $counter,
        'codigo' => $codigoBiometrico,
        'nombre' => $nombre,
        'ci' => $ci ?: 'S/CI',
        'cargo' => $cargoName,
        'servicio' => $servicioName ?: 'Sin Asignar',
        'especialidad' => $especialidad ?: '-',
        'telefono' => $telefono ?: '-',
    ];

    echo sprintf(
        "%-2d. [%-6s] %-35s | CI: %-12s | Cargo: %-22s | Servicio: %-25s | Esp: %s\n",
        $counter,
        $codigoBiometrico,
        mb_substr($nombre, 0, 35),
        $ci ?: 'S/CI',
        mb_substr($cargoName, 0, 22),
        mb_substr($servicioName, 0, 25),
        mb_substr($especialidad, 0, 25)
    );

    $counter++;
}

echo "\n✔ Se insertaron exitosamente los 20 empleados en la tabla 'empleados'.\n";
echo 'Total de empleados ahora en la base de datos: '.Empleado::count()." (1 Admin + 20 Nuevos)\n";
echo 'Total de usuarios activos: '.User::count()." (Solo 'admin')\n";
