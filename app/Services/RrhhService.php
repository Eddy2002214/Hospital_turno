<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\RolMensual;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RrhhService
{
    /**
     * Consulta oficial para extraer el personal activo de BDRRHH en SQL Server.
     */
    public const SQL_PERSONAL_ACTIVO = <<<'SQL'
SELECT 
    p.pperCodPer AS [id_funcionario], 
    RTRIM(LTRIM(ISNULL(p.pperIdePer, ''))) AS [codigo_biometrico], 
    RTRIM(LTRIM(ISNULL(p.pperDocIde, ''))) AS [carnet_identidad], 
    RTRIM(LTRIM(ISNULL(p.pperNombre, ''))) AS [nombres], 
    RTRIM(LTRIM(ISNULL(p.pperPatern, ''))) AS [apellido_paterno], 
    RTRIM(LTRIM(ISNULL(p.pperMatern, ''))) AS [apellido_materno], 
    -- Se reemplaza CONCAT por el operador + controlando los valores NULL
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
ORDER BY d.pdepDescri ASC, p.pperPatern ASC, p.pperNombre ASC
SQL;

    /**
     * Consulta para obtener el catálogo de áreas y departamentos activos desde SQL Server.
     */
    public const SQL_DEPARTAMENTOS_CATALOGO = <<<'SQL'
SELECT DISTINCT RTRIM(LTRIM(d.pdepDescri)) AS departamento
FROM perfuncionariohuj f 
INNER JOIN perdeparta d ON f.codDeparta = d.pdepCodDep 
WHERE f.estado = 'V' 
  AND d.pdepDescri IS NOT NULL 
  AND RTRIM(LTRIM(d.pdepDescri)) != ''
ORDER BY departamento ASC
SQL;

    /**
     * Verifica la conexión a la base de datos SQL Server (BDRRHH).
     *
     * @return array<string, mixed>
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);
        try {
            $connection = DB::connection('sqlsrv_rrhh');
            $connection->getPdo();

            $count = $connection->table('perfuncionariohuj')
                ->where('estado', 'V')
                ->count();

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'connected' => true,
                'host' => config('database.connections.sqlsrv_rrhh.host'),
                'database' => config('database.connections.sqlsrv_rrhh.database'),
                'active_employees' => $count,
                'latency_ms' => $durationMs,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'connected' => false,
                'host' => config('database.connections.sqlsrv_rrhh.host'),
                'database' => config('database.connections.sqlsrv_rrhh.database'),
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Extrae el personal directamente de la base de datos SQL Server ejecutando la consulta oficial.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPersonalFromSqlServer(bool $useCache = true): array
    {
        if ($useCache) {
            return Cache::remember('rrhh_sql_server_personal', 60, function () {
                return $this->fetchFromSqlServer();
            });
        }

        $data = $this->fetchFromSqlServer();
        Cache::put('rrhh_sql_server_personal', $data, 60);

        return $data;
    }

    /**
     * Ejecuta el query contra SQL Server.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFromSqlServer(): array
    {
        $results = DB::connection('sqlsrv_rrhh')->select(self::SQL_PERSONAL_ACTIVO);

        return array_map(function ($row) {
            return (array) $row;
        }, $results);
    }

    /**
     * Obtiene el catálogo de departamentos activos directamente desde SQL Server
     * combinado con los departamentos ya registrados en MySQL.
     *
     * @return array<int, string>
     */
    public function getDepartamentosCatalogo(): array
    {
        return Cache::remember('rrhh_catalogo_departamentos', 300, function () {
            $fromSqlServer = [];

            try {
                $rows = DB::connection('sqlsrv_rrhh')->select(self::SQL_DEPARTAMENTOS_CATALOGO);
                foreach ($rows as $r) {
                    $dep = trim($r->departamento ?? '');
                    if ($dep !== '' && strcasecmp($dep, 'Sin Servicio Asignado') !== 0) {
                        $fromSqlServer[$dep] = true;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('No se pudo consultar el catálogo de departamentos en SQL Server: '.$e->getMessage());
            }

            $existingInMysql = [];
            try {
                $existingInMysql = DepartamentoServicio::pluck('nombre_departmento')->toArray();
            } catch (Throwable) {
                // Modo test o sin tabla creada aún
            }

            $merged = array_unique(array_merge(array_keys($fromSqlServer), $existingInMysql));
            sort($merged, SORT_NATURAL | SORT_FLAG_CASE);

            return array_values($merged);
        });
    }

    /**
     * Sincroniza los funcionarios activos de SQL Server con la base de datos local (MySQL),
     * garantizando que nuevos ingresos aparezcan inmediatamente y que las asignaciones
     * de turnos y roles mantengan integridad referencial.
     *
     * @return array<string, mixed>
     */
    public function syncPersonnel(bool $force = false): array
    {
        $startTime = microtime(true);

        try {
            $rows = $this->fetchFromSqlServer();
            if (empty($rows)) {
                return [
                    'success' => false,
                    'count' => 0,
                    'error' => 'No se obtuvieron registros de SQL Server.',
                ];
            }

            DB::beginTransaction();

            // Mapeo en memoria de Cargos y Departamentos para máxima velocidad
            $cargosMap = Cargo::pluck('id_cargo', 'nombre_cargo')->toArray();
            $deptsMap = DepartamentoServicio::pluck('id_departamento', 'nombre_departmento')->toArray();

            $upsertData = [];
            $now = now();

            $operadorCargo = Cargo::where('nombre_cargo', 'Operador / Especialista')->first();
            $operadorId = $operadorCargo ? $operadorCargo->id_cargo : 2;

            foreach ($rows as $r) {
                $cargoName = trim($r['cargo'] ?: '');
                $cargoId = $cargosMap[$cargoName] ?? $operadorId;

                $deptName = trim($r['departamento_servicio'] ?: '');
                $deptId = null;
                if ($deptName !== '' && strcasecmp($deptName, 'Sin Servicio Asignado') !== 0) {
                    if (! isset($deptsMap[$deptName])) {
                        $newDept = DepartamentoServicio::create(['nombre_departmento' => $deptName]);
                        $deptsMap[$deptName] = $newDept->id_departamento;
                    }
                    $deptId = $deptsMap[$deptName];
                }

                $idFuncionario = (int) $r['id_funcionario'];
                // Proteger al Super Admin si tiene id reservado
                if ($idFuncionario === 999999) {
                    continue;
                }

                $bio = trim($r['codigo_biometrico']) ?: (string) $idFuncionario;
                $ci = trim($r['carnet_identidad']) ?: null;
                $tel = trim($r['celular']) ?: (trim($r['telefono_fijo']) ?: null);
                $nombre = trim($r['nombre_completo']);
                $prof = trim($r['profesion_especialidad']) ?: 'General';

                $upsertData[] = [
                    'item_id' => (string) $idFuncionario,
                    'codigo_biometrico' => $bio,
                    'ci' => $ci,
                    'telefono' => $tel,
                    'nombre_completo' => $nombre,
                    'id_cargo' => $cargoId,
                    'id_departamento' => $deptId,
                    'activo' => 1,
                    'profesion_especialidad' => $prof,
                ];
            }

            // Realizar upsert en lotes por codigo_biometrico (único) para no chocar con la columna IDENTITY id_empleado
            foreach (array_chunk($upsertData, 100) as $chunk) {
                Empleado::upsert(
                    $chunk,
                    ['codigo_biometrico'],
                    [
                        'item_id',
                        'ci',
                        'telefono',
                        'nombre_completo',
                        'activo',
                        'profesion_especialidad',
                        // id_cargo se preserva en empleados existentes para que la sincronización no baje de rango a ningún Jefe de Área ni Administrador
                        // id_departamento se preserva en empleados existentes para mantener las asignaciones de área
                    ]
                );
            }

            // Asegurar que los funcionarios designados como jefes en roles mensuales mantengan su cargo de Jefe de Area
            $jefeIds = RolMensual::whereNotNull('id_jefe')->pluck('id_jefe')->unique()->toArray();
            if (! empty($jefeIds)) {
                $cargoJefeId = Cargo::where('nombre_cargo', 'Jefe de Area')->value('id_cargo') ?? 4;
                Empleado::whereIn('id_empleado', $jefeIds)
                    ->where('id_cargo', '!=', $cargoJefeId)
                    ->update(['id_cargo' => $cargoJefeId]);
            }

            // Asegurar que el Super Admin mantenga su cargo de Administrador de Sistema
            $adminCargoId = Cargo::where('nombre_cargo', 'Administrador de Sistema')->value('id_cargo') ?? 3;
            Empleado::whereIn('id_empleado', [1, 999999])->update(['id_cargo' => $adminCargoId]);

            DB::commit();

            $duration = round(microtime(true) - $startTime, 3);
            $totalCount = count($upsertData);

            Cache::put('rrhh_last_sync_timestamp', now()->timestamp, 86400);
            Cache::put('rrhh_total_synced', $totalCount, 86400);
            Cache::forget('rrhh_catalogo_departamentos');

            return [
                'success' => true,
                'count' => $totalCount,
                'duration_seconds' => $duration,
                'synced_at' => now()->toDateTimeString(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error en la sincronización de personal desde SQL Server: '.$e->getMessage());

            return [
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Asegura que un funcionario específico de SQL Server esté presente en la tabla local
     * empleados únicamente cuando sea asignado como Jefe o incorporado a un rol mensual,
     * manteniendo la base de datos local limpia sin duplicar masivamente todo el personal.
     */
    public function ensureEmployeeInLocal(int $idFuncionario, ?int $deptId = null, ?string $cargoName = null): ?Empleado
    {
        if ($idFuncionario === 999999) {
            return Empleado::find(999999);
        }

        $emp = Empleado::where('id_empleado', $idFuncionario)
            ->orWhere('item_id', (string) $idFuncionario)
            ->first();

        if (! $emp) {
            $data = $this->getEmployeeByIdFromSqlServer($idFuncionario);
            if (! $data) {
                return null;
            }

            $bioCode = $data['codigo_biometrico'] ?: (string) $idFuncionario;
            $emp = Empleado::where('codigo_biometrico', $bioCode)->first();

            if (! $emp) {
                $finalCargoName = in_array($cargoName, ['Jefe de Area', 'Jefe de Servicio', 'Administrador de Sistema'])
                    ? $cargoName
                    : 'Operador / Especialista';
                $cargo = Cargo::where('nombre_cargo', $finalCargoName)->first()
                    ?? Cargo::firstOrCreate(['nombre_cargo' => $finalCargoName]);

                $emp = Empleado::create([
                    'item_id' => (string) $idFuncionario,
                    'codigo_biometrico' => $bioCode,
                    'ci' => $data['carnet_identidad'] ?: null,
                    'telefono' => $data['celular'] ?: ($data['telefono_fijo'] ?: null),
                    'nombre_completo' => $data['nombre_completo'],
                    'id_cargo' => $cargo->id_cargo,
                    'id_departamento' => $deptId,
                    'activo' => 1,
                    'profesion_especialidad' => $data['cargo'] ?: ($data['profesion_especialidad'] ?: 'General'),
                ]);
            }
        }

        if ($emp) {
            $isJefeCargo = $cargoName !== null && in_array($cargoName, ['Jefe de Area', 'Jefe de Servicio', 'Administrador de Sistema']);

            if ($deptId !== null) {
                if ($isJefeCargo || empty($emp->id_departamento)) {
                    $emp->id_departamento = $deptId;
                }
            }

            if ($cargoName !== null) {
                $finalCargoName = in_array($cargoName, ['Jefe de Area', 'Jefe de Servicio', 'Administrador de Sistema'])
                    ? $cargoName
                    : 'Operador / Especialista';
                $cargo = Cargo::where('nombre_cargo', $finalCargoName)->first()
                    ?? Cargo::firstOrCreate(['nombre_cargo' => $finalCargoName]);
                $emp->id_cargo = $cargo->id_cargo;
            }
            $emp->save();
        }

        return $emp;
    }

    /**
     * Consulta un único funcionario por ID directamente desde SQL Server.
     */
    public function getEmployeeByIdFromSqlServer(int $idFuncionario): ?array
    {
        $all = $this->getPersonalFromSqlServer();
        foreach ($all as $item) {
            if ((int) $item['id_funcionario'] === $idFuncionario) {
                return $item;
            }
        }

        return null;
    }

    /**
     * No realiza inserción masiva a menos que se llame explícitamente.
     */
    public function syncIfNeeded(int $maxAgeSeconds = 300): void
    {
        // Mantiene la base de datos limpia: la visualización es directa desde SQL Server
    }
}
