<?php

$start = microtime(true);
$sqlSrv = new PDO('sqlsrv:server=192.168.9.23;Database=BDRRHH;TrustServerCertificate=true;LoginTimeout=5', 'RRHH-L', '1234567');
$mysql = new PDO('mysql:host=127.0.0.1;dbname=control_turnos_hospital', 'root', '');

$query = "SELECT 
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

$stmt = $sqlSrv->query($query);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Fetched '.count($rows).' rows from SQL Server in '.round(microtime(true) - $start, 3)."s\n";

// Let's see how fast a bulk transaction upsert in MySQL is
$mysql->beginTransaction();

// Preload cargos and depts to map IDs
$cargosMap = $mysql->query('SELECT nombre_cargo, id_cargo FROM cargos')->fetchAll(PDO::FETCH_KEY_PAIR);
$deptsMap = $mysql->query('SELECT nombre_departmento, id_departamento FROM departamentos_servicios')->fetchAll(PDO::FETCH_KEY_PAIR);

// Insert missing cargos or depts
$cargoStmt = $mysql->prepare('INSERT INTO cargos (nombre_cargo) VALUES (?)');
$deptStmt = $mysql->prepare('INSERT INTO departamentos_servicios (nombre_departmento) VALUES (?)');

$upsertStmt = $mysql->prepare('
    INSERT INTO empleados (id_empleado, item_id, codigo_biometrico, ci, telefono, nombre_completo, id_cargo, id_departamento, activo, profesion_especialidad)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
    ON DUPLICATE KEY UPDATE
        item_id = VALUES(item_id),
        codigo_biometrico = VALUES(codigo_biometrico),
        ci = VALUES(ci),
        telefono = VALUES(telefono),
        nombre_completo = VALUES(nombre_completo),
        id_cargo = VALUES(id_cargo),
        id_departamento = IF(empleados.id_departamento IS NOT NULL AND empleados.id_departamento > 0, empleados.id_departamento, VALUES(id_departamento)),
        activo = 1,
        profesion_especialidad = VALUES(profesion_especialidad)
');

foreach ($rows as $r) {
    $cargoName = trim($r['cargo']);
    if (! isset($cargosMap[$cargoName])) {
        $cargoStmt->execute([$cargoName]);
        $cargosMap[$cargoName] = $mysql->lastInsertId();
    }
    $cargoId = $cargosMap[$cargoName];

    $deptName = trim($r['departamento_servicio']);
    $deptId = null;
    if ($deptName && strcasecmp($deptName, 'Sin Servicio Asignado') !== 0) {
        if (! isset($deptsMap[$deptName])) {
            $deptStmt->execute([$deptName]);
            $deptsMap[$deptName] = $mysql->lastInsertId();
        }
        $deptId = $deptsMap[$deptName];
    }

    $idFuncionario = (int) $r['id_funcionario'];
    $bio = $r['codigo_biometrico'] ?: (string) $idFuncionario;
    $ci = $r['carnet_identidad'] ?: null;
    $tel = $r['celular'] ?: ($r['telefono_fijo'] ?: null);
    $nombre = $r['nombre_completo'];
    $prof = $r['profesion_especialidad'];

    $upsertStmt->execute([
        $idFuncionario,
        (string) $idFuncionario,
        $bio,
        $ci,
        $tel,
        $nombre,
        $cargoId,
        $deptId,
        $prof,
    ]);
}

$mysql->commit();
echo 'Bulk upserted '.count($rows).' employees into MySQL in '.round(microtime(true) - $start, 3)."s\n";
