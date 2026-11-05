<?php

namespace App\Console\Commands;

use App\Services\RrhhService;
use Illuminate\Console\Command;

class SyncRrhhCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rrhh:sync {--test : Solo prueba la conexión sin sincronizar datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae y sincroniza el personal activo directamente desde SQL Server (BDRRHH)';

    /**
     * Execute the console command.
     */
    public function handle(RrhhService $service): int
    {
        $this->info('======================================================');
        $this->info('  HOSPITAL JAPONÉS - CONEXIÓN SQL SERVER (BDRRHH)    ');
        $this->info('======================================================');

        $this->line('Host: '.config('database.connections.sqlsrv_rrhh.host'));
        $this->line('Base de Datos: '.config('database.connections.sqlsrv_rrhh.database'));
        $this->line('Usuario: '.config('database.connections.sqlsrv_rrhh.username'));
        $this->newLine();

        $this->info('Probando conexión...');
        $test = $service->testConnection();

        if (! $test['connected']) {
            $this->error('Error de conexión a SQL Server: '.($test['error'] ?? 'Desconocido'));

            return Command::FAILURE;
        }

        $this->info("✔ Conexión exitosa ({$test['latency_ms']} ms)");
        $this->info("Total funcionarios activos en SQL Server: {$test['active_employees']}");

        if ($this->option('test')) {
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Iniciando sincronización con el sistema local...');
        $result = $service->syncPersonnel(force: true);

        if (! $result['success']) {
            $this->error('Fallo en la sincronización: '.($result['error'] ?? 'Desconocido'));

            return Command::FAILURE;
        }

        $this->info("✔ Sincronizados exitosamente: {$result['count']} funcionarios en {$result['duration_seconds']} segundos.");

        return Command::SUCCESS;
    }
}
