<?php

namespace Tests\Feature;

use App\Services\RrhhService;
use Tests\TestCase;

class RrhhConnectionTest extends TestCase
{
    /**
     * Test RRHH service can connect to SQL Server.
     */
    public function test_rrhh_service_connects_to_sql_server(): void
    {
        $service = new RrhhService;
        $status = $service->testConnection();

        $this->assertTrue($status['success']);
        $this->assertTrue($status['connected']);
        $this->assertGreaterThan(1000, $status['active_employees']);
    }

    /**
     * Test SQL Server query returns active employees.
     */
    public function test_rrhh_service_fetches_sql_server_personal(): void
    {
        $service = new RrhhService;
        $personal = $service->getPersonalFromSqlServer(useCache: false);

        $this->assertIsArray($personal);
        $this->assertGreaterThan(1000, count($personal));

        $first = $personal[0];
        $this->assertArrayHasKey('id_funcionario', $first);
        $this->assertArrayHasKey('codigo_biometrico', $first);
        $this->assertArrayHasKey('carnet_identidad', $first);
        $this->assertArrayHasKey('nombre_completo', $first);
        $this->assertArrayHasKey('cargo', $first);
        $this->assertArrayHasKey('departamento_servicio', $first);
        $this->assertEquals('ACTIVO', $first['estado']);
    }

    /**
     * Test catalog departments extracted from SQL Server.
     */
    public function test_rrhh_service_fetches_departments_catalog(): void
    {
        $service = new RrhhService;
        $depts = $service->getDepartamentosCatalogo();

        $this->assertIsArray($depts);
        $this->assertNotEmpty($depts);
    }
}
