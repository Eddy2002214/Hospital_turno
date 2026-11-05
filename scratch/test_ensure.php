<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Test fetching one employee by ID from SQL Server and inserting ONLY them into MySQL
$id = 5554;
$c = DB::connection('sqlsrv');
$sql = "SELECT 
    p.pperCodPer AS [id_funcionario], 
    RTRIM(LTRIM(ISNULL(p.pperIdePer, ''))) AS [codigo_biometrico], 
    RTRIM(LTRIM(ISNULL(p.pperDocIde, ''))) AS [carnet_identidad], 
    RTRIM(LTRIM(ISNULL(p.pperNombre, ''))) AS [nombres], 
    RTRIM(LTRIM(ISNULL(p.pperPatern, ''))) AS [apellido_paterno], 
    RTRIM(LTRIM(ISNULL(p.pperMatern, ''))) AS [apellido_materno], 
    RTRIM(LTRIM(
        ISNULL(p.pperNombre, '') + ' ' + 
        ISNULL(p.pperPatern, '') + ' ' + 
        ISNULL(p.pperMatern, '')
    )) AS [nombre_completo], 
    RTRIM(LTRIM(ISNULL(p.pperTelCel, ''))) AS [celular], 
    RTRIM(LTRIM(ISNULL(p.pperTelDom, ''))) AS [telefono_fijo], 
    ISNULL(RTRIM(LTRIM(c.pcarDescri)), 'Sin Cargo Asignado') AS [cargo], 
    ISNULL(RTRIM(LTRIM(d.pdepDescri)), 'Sin Servicio Asignado') AS [departamento_servicio], 
    COALESCE( 
        NULLIF(RTRIM(LTRIM(p.pperEspMed)), ''), 
        RTRIM(LTRIM(pr.pproDescri)), 
        'General' 
    ) AS [profesion_especialidad], 
    'ACTIVO' AS [estado] 
FROM perfuncionariohuj f 
INNER JOIN perpersona p ON f.ppercodper = p.pperCodPer 
LEFT JOIN perdeparta d ON f.codDeparta = d.pdepCodDep 
LEFT JOIN percargosa c ON f.codCargo = c.pcarCodCar 
LEFT JOIN perprofesi pr ON p.pproCodPro = pr.pproCodPro 
WHERE p.pperCodPer = ?";

$row = (array) $c->selectOne($sql, [$id]);
print_r($row);
