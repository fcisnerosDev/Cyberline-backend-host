<?php

namespace App\Http\Controllers\Api\Monitoreo;

use App\Exports\MonitoreoReporte;
use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\MonResumen;
use App\Models\Oficina;
use App\Models\SysNodo;
use Illuminate\Http\Request;
use App\Models\Monitoreo;
use Illuminate\Support\Facades\Http;

class MonitoreoAgenteController extends Controller
{
    public function getListSede(Request $request)
    {
        // Parámetro recibido por query
        $idSedePerspectiva = $request->query('idSedePerspectiva');

        // Base URL del servicio
        $baseUrl = env('SERVICE_OCITCS');

        if (!$baseUrl) {
            return response()->json([
                'error' => true,
                'message' => 'La variable SERVICE_OCITCS no está configurada en el .env'
            ], 500);
        }

        // Endpoint
        $endpoint = rtrim($baseUrl, '/') . '/api/sedes-disponibles';

        // Parámetros a enviar
        $params = [];

        if (!empty($idSedePerspectiva)) {
            $params['idSedePerspectiva'] = $idSedePerspectiva;
        }

        // Llamada al servicio
        $response = Http::get($endpoint, $params);

        // Validar respuesta
        if (!$response->successful()) {
            return response()->json([
                'error' => true,
                'message' => 'Error al consumir el servicio de equipos',
                'status' => $response->status()
            ], $response->status());
        }

        // Retornar la respuesta del servicio
        return $response->json();
    }


    public function getEquipos(Request $request)
    {
        // Parámetros recibidos por query
        $idEquipoPerspectiva = $request->query('idEquipoPerspectiva');
        $idSede = $request->query('id_sede');

        // Base URL del servicio
        $baseUrl = env('SERVICE_OCITCS');

        if (!$baseUrl) {
            return response()->json([
                'error' => true,
                'message' => 'La variable SERVICE_OCITCS no está configurada en el .env'
            ], 500);
        }

        // Endpoint remoto
        $endpoint = rtrim($baseUrl, '/') . '/api/equipos-disponibles';

        // Parámetros a enviar
        $params = [];

        // if (!empty($idEquipoPerspectiva)) {
        //     $params['idEquipoPerspectiva'] = $idEquipoPerspectiva;
        // }

        //  Filtro opcional por sede
        if (!empty($idSede)) {
            $params['id_sede'] = $idSede;
        }

        // Llamada al servicio remoto
        $response = Http::get($endpoint, $params);

        // Validar respuesta
        if (!$response->successful()) {
            return response()->json([
                'error' => true,
                'message' => 'Error al consumir el servicio de equipos',
                'status' => $response->status()
            ], $response->status());
        }

        // Retornar la respuesta del servicio
        return $response->json();
    }


    //Reporte
    // public function exportReportMonitoreo(Request $request)
    // {
    //     $idOficinaPerspectiva = $request->query('idOficinaPerspectiva');
    //     $fechaHora = now()->format('d-m-Y_H-i');

    //     return \Maatwebsite\Excel\Facades\Excel::download(
    //         new MonitoreoReporte($idOficinaPerspectiva),
    //         "reporte-monitoreo-compania-{$idOficinaPerspectiva}-{$fechaHora}.xlsx"
    //     );
    // }
}
