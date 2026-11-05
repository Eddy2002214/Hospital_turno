<?php

namespace App\Http\Controllers;

use App\Services\RrhhService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RrhhController extends Controller
{
    public function __construct(
        protected RrhhService $rrhhService
    ) {}

    /**
     * Devuelve el estado de conexión con SQL Server (BDRRHH).
     */
    public function status(): JsonResponse
    {
        $status = $this->rrhhService->testConnection();

        return response()->json($status);
    }

    /**
     * Catálogo oficial de departamentos activos desde SQL Server.
     */
    public function catalogoDepartamentos(): JsonResponse
    {
        $departamentos = $this->rrhhService->getDepartamentosCatalogo();

        return response()->json($departamentos);
    }

    /**
     * Ejecuta la sincronización bajo demanda desde la interfaz.
     */
    public function sync(Request $request): JsonResponse
    {
        $result = $this->rrhhService->syncPersonnel(force: true);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Devuelve el personal consultado directamente de SQL Server con la consulta oficial.
     */
    public function getPersonalDirect(Request $request): JsonResponse
    {
        $data = $this->rrhhService->getPersonalFromSqlServer(useCache: ! $request->has('fresh'));

        return response()->json([
            'total' => count($data),
            'source' => 'SQL Server (192.168.9.23 / BDRRHH)',
            'data' => $data,
        ]);
    }
}
