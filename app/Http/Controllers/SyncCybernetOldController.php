<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Frecuencia;
use App\Models\Ip;
use App\Models\Maestro;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Monitoreo;
use App\Models\MonitoreoSecundario;
use App\Models\Servicio;
use App\Models\ServicioSecundario;
use App\Models\SysNodo;
use Illuminate\Support\Facades\DB;
//redis
use Illuminate\Support\Facades\Redis;

class SyncCybernetOldController extends Controller
{
    public function UpdateMonitoreoData()
    {
        $idNodos = $this->getValidNodoIdForCybernetPrimary();

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos para la sincronización."
            ], 400);
        }

        $sysNodos = SysNodo::whereIn('idNodo', $idNodos)->get();
        $updatedRecords = [];

        foreach ($sysNodos as $sysNodo) {
            $url = rtrim($sysNodo->urlWs, '/') . "/sync.php";

            try {
                $response = Http::timeout(15)->get($url);
            } catch (\Exception $e) {
                echo "Error HTTP en {$sysNodo->idNodo}: " . $e->getMessage() . PHP_EOL;
                continue;
            }

            if (!$response->successful()) {
                echo "Fallo al obtener datos de $url - Código: " . $response->status() . PHP_EOL;
                continue;
            }

            $data = $response->json();
            DB::statement('SET @DISABLE_TRIGGER = 1;');

            foreach ($data['data'] as $item) {
                // 🔹 Limpia TODAS las fechas
                foreach ($item as $key => $value) {
                    if (Str::startsWith($key, 'fecha')) {
                        $item[$key] = $this->limpiarFecha($value);
                    }
                }

                // 🔹 Obtiene registro actual (por idMonitoreo o combinación única)
                $registroPadre = DB::table('monMonitoreo')
                    ->where('idMonitoreo', $item['idMonitoreo'])
                    ->orWhere(function ($q) use ($item) {
                        $q->where('idServicio', $item['idServicio'] ?? null)
                            ->where('idEquipo', $item['idEquipo'] ?? null)
                            ->where('idTipoServicio', $item['idTipoServicio'] ?? null)
                            ->where('idIp', $item['idIp'] ?? null);
                    })
                    ->first();

                // 🔹 flgSolucionado no retrocede
                $flgSolucionado = (string)($item['flgSolucionado'] ?? '0');
                if ($registroPadre && $registroPadre->flgSolucionado === '1') {
                    $flgSolucionado = '1';
                }

                // 🔹 Fechas coherentes
                $fechaSyncHijo = $this->limpiarFecha($item['fechaSyncHijo'] ?? null)
                    ? \Carbon\Carbon::parse($item['fechaSyncHijo'])
                    : now();
                $fechaSyncPadre = now();
                if ($fechaSyncPadre->lessThanOrEqualTo($fechaSyncHijo)) {
                    $fechaSyncPadre = $fechaSyncHijo->copy()->addSeconds(2);
                }

                // 🔹 Datos a insertar/actualizar
                $datos = [
                    'idNodoPerspectiva'         => $item['idNodoPerspectiva'] ?? null,
                    'idSync'                    => $item['idSync'] ?? null,
                    'idSyncNodo'                => $item['idSyncNodo'] ?? null,
                    'idServicio'                => $item['idServicio'] ?? null,
                    'idServicioNodo'            => $item['idServicioNodo'] ?? null,
                    'idEquipo'                  => $item['idEquipo'] ?? null,
                    'idEquipoNodo'              => $item['idEquipoNodo'] ?? null,
                    'idTipoServicio'            => $item['idTipoServicio'] ?? null,
                    'idTipoServicioNodo'        => $item['idTipoServicioNodo'] ?? null,
                    'idIp'                      => $item['idIp'] ?? null,
                    'idIpNodo'                  => $item['idIpNodo'] ?? null,
                    'idFrecuencia'              => $item['idFrecuencia'] ?? null,
                    'idFrecuenciaNodo'          => $item['idFrecuenciaNodo'] ?? null,
                    'idUsuario'                 => $item['idUsuario'] ?? null,
                    'idUsuarioNodo'             => $item['idUsuarioNodo'] ?? null,
                    'dscMonitoreo'              => $item['dscMonitoreo'] ?? null,
                    'etiqueta'                  => $item['etiqueta'] ?? null,
                    'numReintentos'             => $item['numReintentos'] ?? 0,
                    'paramametroScript'         => $item['paramametroScript'] ?? null,
                    'flgMonitoreoIp'            => $item['flgMonitoreoIp'] ?? '0',
                    'paramNumPort'              => $item['paramNumPort'] ?? null,
                    'paramNumPackets'           => $item['paramNumPackets'] ?? null,
                    'paramTimeout'              => $item['paramTimeout'] ?? null,
                    'paramWarningUmbral'        => $item['paramWarningUmbral'] ?? null,
                    'paramCriticalUmbral'       => $item['paramCriticalUmbral'] ?? null,
                    'flgRevision'               => $item['flgRevision'] ?? '0',
                    'anotacion'                 => $item['anotacion'] ?? null,
                    'cuentasNotificacion'       => $item['cuentasNotificacion'] ?? null,
                    'intervaloNotificacion'     => $item['intervaloNotificacion'] ?? null,
                    'fechaUltimaVerificacion'   => $item['fechaUltimaVerificacion'] ?? null,
                    'fechaUltimoCambio'         => $item['fechaUltimoCambio'] ?? null,
                    'fechaUltimaNotificacion'   => $item['fechaUltimaNotificacion'] ?? null,
                    'fechaActivacion'           => $item['fechaActivacion'] ?? null,
                    'fechaDesactivacion'        => $item['fechaDesactivacion'] ?? null,
                    'flgStatus'                 => $item['flgStatus'] ?? '0',
                    'flgStatusControl'          => $item['flgStatusControl'] ?? '0',
                    'flgCondicionSolucionado'   => $item['flgCondicionSolucionado'] ?? '0',
                    'flgOcultarMonitoreo'       => $item['flgOcultarMonitoreo'] ?? '0',
                    'flgSonido'                 => $item['flgSonido'] ?? '0',
                    'flgSolucionado'            => $flgSolucionado,
                    'flgEstado'                 => $item['flgEstado'] ?? '0',
                    'flgActivacionAutomatica'   => $item['flgActivacionAutomatica'] ?? '0',
                    'fechaActivacionAutomatica' => $item['fechaActivacionAutomatica'] ?? null,
                    'fechaModificacion'         => $item['fechaModificacion'] ?? null,
                    'fechaModificacionStatus'   => $item['fechaModificacionStatus'] ?? null,
                    'fechaCreacion'             => $item['fechaCreacion'] ?? null,
                    'fechaRegistro'             => $item['fechaRegistro'] ?? null,
                    'flgSync'                   => $item['flgSync'] ?? '0',
                    'flgSyncHijo'               => '1',
                    'flgSyncPadre'              => '1',
                    'fechaSyncHijo'             => $fechaSyncHijo,
                    'fechaSyncPadre'            => $fechaSyncPadre,
                    'temporal'                  => $item['temporal'] ?? '0',
                    'cantidad_alertas'          => $item['cantidad_alertas'] ?? '0',
                    'porcentaje_alertas'        => $item['porcentaje_alertas'] ?? '0',
                ];

                if ($registroPadre) {
                    // 🔹 Actualiza si existe
                    DB::table('monMonitoreo')->where('idMonitoreo', $registroPadre->idMonitoreo)->update($datos);
                } else {
                    // 🔹 Inserta si no existe
                    DB::table('monMonitoreo')->insert(array_merge(['idMonitoreo' => $item['idMonitoreo']], $datos));
                }

                $updatedRecords[] = [
                    "idNodo"         => $sysNodo->idNodo,
                    "idMonitoreo"    => $item['idMonitoreo'],
                    "flgSolucionado" => $flgSolucionado,
                    "fechaSyncHijo"  => $fechaSyncHijo->toDateTimeString(),
                    "fechaSyncPadre" => $fechaSyncPadre->toDateTimeString(),
                ];
            }
        }

        return response()->json([
            "status" => "success",
            "updated" => $updatedRecords
        ]);
    }





    // public function UpdateMonitoreoData()
    // {
    //     $idNodos = $this->getValidNodoIdForCybernetPrimary();

    //     if ($idNodos->isEmpty()) {
    //         return response()->json([
    //             "status" => "error",
    //             "message" => "No se encontraron nodos válidos para la sincronización."
    //         ], 400);
    //     }

    //     $sysNodos = SysNodo::whereIn('idNodo', $idNodos)->get();
    //     $updatedRecords = [];

    //     foreach ($sysNodos as $sysNodo) {
    //         $url = rtrim($sysNodo->urlWs, '/') . "/sync.php";
    //         $response = Http::get($url);

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             DB::statement('SET @DISABLE_TRIGGER = 1;'); // Desactivar triggers

    //             foreach ($data['data'] as $item) {
    //                 // Limpiar campos de fecha inválidos
    //                 foreach ($item as $key => $value) {
    //                     if (Str::startsWith($key, 'fecha')) {
    //                         $item[$key] = $this->limpiarFecha($value);
    //                     }
    //                 }

    //                 DB::table('monMonitoreo')->updateOrInsert(
    //                     ['idMonitoreo' => $item['idMonitoreo']],
    //                     [
    //                         'idNodoPerspectiva'        => $item['idNodoPerspectiva'],
    //                         'flgStatus'                => $item['flgStatus'],
    //                         'flgEstado'                => $item['flgEstado'],
    //                         'fechaUltimaVerificacion' => $item['fechaUltimaVerificacion'],
    //                         'fechaUltimoCambio'       => $item['fechaUltimoCambio'],
    //                         'flgSyncHijo'              => "1"
    //                     ]
    //                 );

    //                 $updatedRecords[] = [
    //                     "idNodo"            => $sysNodo->idNodo,
    //                     "idMonitoreo"       => $item['idMonitoreo'],
    //                     "idNodoPerspectiva" => $item['idNodoPerspectiva'],
    //                     "flgStatus"         => $item['flgStatus'],
    //                     'fechaUltimaVerificacion' => $item['fechaUltimaVerificacion'],
    //                     'fechaUltimoCambio'       => $item['fechaUltimoCambio'],
    //                     "flgEstado"         => $item['flgEstado']
    //                 ];
    //             }

    //             DB::statement('SET @DISABLE_TRIGGER = NULL;'); // Reactivar triggers
    //         } else {
    //             return response()->json([
    //                 "status"  => "error",
    //                 "message" => "No se pudo obtener los datos de $url"
    //             ], 500);
    //         }
    //     }

    //     return response()->json([
    //         "status"          => "success",
    //         "message"         => "Datos sincronizados correctamente.",
    //         "updated_records" => $updatedRecords,
    //     ]);
    // }

    // Función para limpiar fechas inválidas
    private function limpiarFecha($valor)
    {
        // Si el valor viene vacío, 0 o con formato inválido, devuelve null
        if (
            empty($valor) ||
            $valor === '0' ||
            $valor === 0 ||
            $valor === '0000-00-00 00:00:00' ||
            $valor === '0000-00-00' ||
            strtolower($valor) === 'null'
        ) {
            return null;
        }

        try {
            // Intenta parsear con Carbon y devolverlo en formato MySQL correcto
            return \Carbon\Carbon::parse($valor)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Si no se puede convertir, lo devolvemos como null
            return null;
        }
    }






    /**
     * Función pública para obtener datos filtrados de Monitoreo.
     */
    public function getFilteredMonitoreoData($idNodos)
    {
        return Monitoreo::where('flgEstado', "1")
            ->whereIn('idNodoPerspectiva', $idNodos) // Filtrar por múltiples nodos
            ->get();
    }



    public function DataMonitoreos($idNodo = null)
    {
        $idNodos = $this->getValidNodoId($idNodo); // Buscar el nodo si se envía o todos si es null

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos."
            ], 404);
        }

        $monitoreoData = $this->getFilteredMonitoreoData($idNodos);

        return response()->json([
            "Monitoreos" => $monitoreoData,
        ]);
    }




    public function getFilteredServicioData($idNodos)
    {
        return Servicio::where('flgEstado', "1")
            ->whereIn('idNodoPerspectiva', $idNodos)
            ->get();
    }

    public function getFilteredMaestroData($idNodos)
    {
        return Maestro::where('flgEstado', "1")
            ->whereIn('idNodoPerspectiva', $idNodos)
            ->get();
    }
    public function getFilteredIpData($idNodos)
    {
        return Ip::where('flgEstado', "1")
            ->whereIn('idNodoPerspectiva', $idNodos)
            ->get();
    }
    public function getFilteredEquiposData($idNodos)
    {
        return Equipo::where('flgEstado', "1")
            ->whereIn('idEquipoPerspectiva', $idNodos)
            ->get();
    }
    public function getFilteredFrecuenciaData($idNodos)
    {
        return Frecuencia::where('flgEstado', "1")
            ->whereIn('idNodoPerspectiva', $idNodos)
            ->get();
    }

    public function DataServicios($idNodo = null)
    {
        $idNodos = $this->getValidNodoId();
        $idNodos = $this->getValidNodoId($idNodo);

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos."
            ], 404);
        }

        $ServicioData = $this->getFilteredServicioData($idNodos);

        return response()->json([
            "Servicios" => $ServicioData,
        ]);
    }

    public function DataMaestro($idNodo = null)
    {
        $idNodos = $this->getValidNodoId();
        $idNodos = $this->getValidNodoId($idNodo);

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos."
            ], 404);
        }

        $MaestroData = $this->getFilteredMaestroData($idNodos);

        return response()->json([
            "Maestro" => $MaestroData,
        ]);
    }
    public function DataIP($idNodo = null)
    {
        $idNodos = $this->getValidNodoId();
        $idNodos = $this->getValidNodoId($idNodo);

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos."
            ], 404);
        }

        $DataIP = $this->getFilteredIpData($idNodos);

        return response()->json([
            "IP" => $DataIP,
        ]);
    }
    public function DataEquipo($idNodo = null)
    {
        $idNodos = $this->getValidNodoId();
        $idNodos = $this->getValidNodoId($idNodo);

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos."
            ], 404);
        }

        $DataEquipos = $this->getFilteredEquiposData($idNodos);

        return response()->json([
            "Equipos" => $DataEquipos,
        ]);
    }

    public function DataFrecuencia($idNodo = null)
    {
        $idNodos = $this->getValidNodoId();
        $idNodos = $this->getValidNodoId($idNodo);

        if ($idNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos válidos."
            ], 404);
        }

        $DataFrecuencia = $this->getFilteredFrecuenciaData($idNodos);

        return response()->json([
            "Frecuencia" => $DataFrecuencia,
        ]);
    }



    public function obtenerMonitoreosCliente()
    {
        // Obtener los datos desde el servicio externo
        $response = Http::get('https://backend.cyberline.com.pe/recover-monitoreo/' . env('ID_NODO_HIJO'));

        // Verificar si la solicitud fue exitosa
        if (!$response->successful()) {
            return response()->json(['error' => 'No se pudo obtener los datos del servicio externo'], 500);
        }

        $monitoreosExternos = $response->json()['Monitoreos'];

        // Obtener los idMonitoreo que ya existen en la base de datos
        $monitoreosLocales = MonitoreoSecundario::pluck('idMonitoreo')->toArray();

        // Filtrar los monitoreos que no están en la base de datos y cuyo idNodoPerspectiva sea "MIS"
        $monitoreosNuevos = array_filter($monitoreosExternos, function ($monitoreo) use ($monitoreosLocales) {
            return !in_array($monitoreo['idMonitoreo'], $monitoreosLocales) && $monitoreo['idNodoPerspectiva'] === env('ID_NODO_HIJO');
        });

        // Insertar los nuevos registros en la base de datos
        foreach ($monitoreosNuevos as $monitoreo) {
            MonitoreoSecundario::create([
                'idMonitoreo'               => $monitoreo['idMonitoreo'],
                'idMonitoreoNodo'           => $monitoreo['idMonitoreoNodo'],
                'idNodoPerspectiva'         => $monitoreo['idNodoPerspectiva'],
                'idSync'                    => $monitoreo['idSync'],
                'idSyncNodo'                => $monitoreo['idSyncNodo'],
                'idServicio'                => $monitoreo['idServicio'],
                'idServicioNodo'            => $monitoreo['idServicioNodo'],
                'idEquipo'                  => $monitoreo['idEquipo'],
                'idEquipoNodo'              => $monitoreo['idEquipoNodo'],
                'idTipoServicio'            => $monitoreo['idTipoServicio'],
                'idTipoServicioNodo'        => $monitoreo['idTipoServicioNodo'],
                'idIp'                      => $monitoreo['idIp'],
                'idIpNodo'                  => $monitoreo['idIpNodo'],
                'idFrecuencia'              => $monitoreo['idFrecuencia'],
                'idFrecuenciaNodo'          => $monitoreo['idFrecuenciaNodo'],
                'idUsuario'                 => $monitoreo['idUsuario'],
                'idUsuarioNodo'             => $monitoreo['idUsuarioNodo'],
                'dscMonitoreo'              => $monitoreo['dscMonitoreo'],
                'etiqueta'                  => $monitoreo['etiqueta'],
                'numReintentos'             => $monitoreo['numReintentos'],
                'paramametroScript'         => $monitoreo['paramametroScript'],
                'flgMonitoreoIp'            => $monitoreo['flgMonitoreoIp'],
                'paramNumPort'              => $monitoreo['paramNumPort'],
                'paramNumPackets'           => $monitoreo['paramNumPackets'],
                'paramTimeout'              => $monitoreo['paramTimeout'],
                'paramWarningUmbral'        => $monitoreo['paramWarningUmbral'],
                'paramCriticalUmbral'       => $monitoreo['paramCriticalUmbral'],
                'flgRevision'               => $monitoreo['flgRevision'],
                'anotacion'                 => $monitoreo['anotacion'],
                'cuentasNotificacion'       => $monitoreo['cuentasNotificacion'],
                'intervaloNotificacion'     => $monitoreo['intervaloNotificacion'],
                'fechaUltimaVerificacion'   => $monitoreo['fechaUltimaVerificacion'],
                'fechaUltimoCambio'         => $monitoreo['fechaUltimoCambio'],
                'fechaUltimaNotificacion'   => $monitoreo['fechaUltimaNotificacion'],
                'fechaActivacion'           => $monitoreo['fechaActivacion'],
                'fechaDesactivacion'        => $monitoreo['fechaDesactivacion'],
                'flgStatus'                 => $monitoreo['flgStatus'],
                'flgStatusControl'          => $monitoreo['flgStatusControl'],
                'flgCondicionSolucionado'   => $monitoreo['flgCondicionSolucionado'],
                'flgOcultarMonitoreo'       => $monitoreo['flgOcultarMonitoreo'],
                'flgSonido'                 => $monitoreo['flgSonido'],
                'flgSolucionado'            => $monitoreo['flgSolucionado'],
                'flgEstado'                 => $monitoreo['flgEstado'],
                'flgActivacionAutomatica'   => $monitoreo['flgActivacionAutomatica'],
                'fechaActivacionAutomatica' => $monitoreo['fechaActivacionAutomatica'],
                'fechaModificacion'         => $monitoreo['fechaModificacion'],
                'fechaModificacionStatus'   => $monitoreo['fechaModificacionStatus'],
                'fechaCreacion'             => $monitoreo['fechaCreacion'],
                'fechaRegistro'             => $monitoreo['fechaRegistro'],
                'flgSync'                   => $monitoreo['flgSync'],
                'flgSyncHijo'               => $monitoreo['flgSyncHijo'],
                'flgSyncPadre'              => $monitoreo['flgSyncPadre'],
                'fechaSyncHijo'             => $monitoreo['fechaSyncHijo'],
                'fechaSyncPadre'            => $monitoreo['fechaSyncPadre'],
                'temporal'                  => $monitoreo['temporal'],
                'cantidad_alertas'          => $monitoreo['cantidad_alertas'],
                'porcentaje_alertas'        => $monitoreo['porcentaje_alertas'],
            ]);
        }

        // Retornar los registros insertados
        return response()->json([
            'message' => 'Monitoreos insertados correctamente',
            'data' => array_values($monitoreosNuevos)
        ]);
    }

    public function obtenerServiciosCliente()
    {
        // Obtener los datos desde el servicio externo
        $response = Http::get('https://backend.cyberline.com.pe/recover-servicios/' . env('ID_NODO_HIJO'));


        // Verificar si la solicitud fue exitosa
        if (!$response->successful()) {
            return response()->json(['error' => 'No se pudo obtener los datos del servicio externo'], 500);
        }

        $ServiciosExternos = $response->json()['Servicios'];

        // Obtener los idServicio que ya existen en la base de datos
        $ServiciosLocales = ServicioSecundario::pluck('idServicio')->toArray();

        // Filtrar los servicios que no están en la base de datos y cuyo idNodoPerspectiva sea "MIS"
        $ServiciosNuevos = array_filter($ServiciosExternos, function ($servicio) use ($ServiciosLocales) {
            return !in_array($servicio['idServicio'], $ServiciosLocales) && $servicio['idNodoPerspectiva'] === env('ID_NODO_HIJO');
        });

        // Insertar los nuevos registros en la base de datos
        foreach ($ServiciosNuevos as $servicio) {
            ServicioSecundario::create([
                'idServicio'         => $servicio['idServicio'],
                'idServicioNodo'     => $servicio['idServicioNodo'],
                'idNodoPerspectiva'  => $servicio['idNodoPerspectiva'],
                'idSync' => $servicio['idSync'] ?? 0,
                'IdNodoSync' => $servicio['IdNodoSync'] ?? '',
                'idEquipo'           => $servicio['idEquipo'],
                'idEquipoNodo'       => $servicio['idEquipoNodo'],
                'idTipoServicio'     => $servicio['idTipoServicio'],
                'idTipoServicioNodo' => $servicio['idTipoServicioNodo'],
                'idIp'               => $servicio['idIp'],
                'idIpNodo'           => $servicio['idIpNodo'],
                'puerto'             => $servicio['puerto'],
                'fechaInicio'        => $servicio['fechaInicio'],
                'fechaTermino' => !empty($servicio['fechaTermino']) ? $servicio['fechaTermino'] : now(),
                'flgEstado'          => $servicio['flgEstado'],
                'fechaCreacion'      => $servicio['fechaCreacion'],
                'fechaRegistro'      => $servicio['fechaRegistro'],
                'fechaModificacion'  => $servicio['fechaModificacion'],
                'flgSync'            => $servicio['flgSync'],
                'flgSyncHijo'        => $servicio['flgSyncHijo'],
                'flgSyncPadre'       => $servicio['flgSyncPadre'],
                'fechaSyncHijo'      => $servicio['fechaSyncHijo'],
                'fechaSyncPadre'     => $servicio['fechaSyncPadre'],
                'temporal'           => $servicio['temporal'],
            ]);
        }

        // Retornar los registros insertados
        return response()->json([
            'message' => 'Servicios insertados correctamente',
            'data' => array_values($ServiciosNuevos)
        ]);
    }






    /**
     * Función privada para obtener el ID del nodo válido.
     */
    private function getValidNodoId($idNodo = null)
    {
        $query = SysNodo::whereNotNull('urlWs')
            ->where('SyncParche', 1);

        if ($idNodo) {
            $query->where('idNodo', $idNodo); // Filtra por idNodo si se proporciona
        }

        return $query->pluck('idNodo'); // Retorna una colección de IDs
    }


    private function getValidNodoIdForCybernetPrimary($idNodo = null)
    {
        $query = SysNodo::whereNotNull('urlWs')
            ->where('SyncParche', 1);

        if ($idNodo) {
            $query->where('idNodo', $idNodo);
        }

        return $query->pluck('idNodo'); // Retorna una colección de IDs
    }


    //Redis


}
