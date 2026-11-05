<?php

$start = microtime(true);
$c = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
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
WHERE f.estado = 'V' 
ORDER BY d.pdepDescri ASC, p.pperPatern ASC, p.pperNombre ASC";

$stmt = $c->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$duration = microtime(true) - $start;

echo 'Total records: '.count($rows).' fetched in '.round($duration, 3)."s\n";
echo 'First record: '.json_encode($rows[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
