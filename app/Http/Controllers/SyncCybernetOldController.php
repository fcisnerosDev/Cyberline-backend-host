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
    // public function UpdateMonitoreoData()
    // {
    //     $idNodos = $this->getValidNodoIdForCybernetPrimary();

    //     if ($idNodos->isEmpty()) {
    //         echo "No se encontraron nodos válidos para la sincronización." . PHP_EOL;
    //         return response()->json([
    //             "status" => "error",
    //             "message" => "No se encontraron nodos válidos para la sincronización."
    //         ], 400);
    //     }

    //     $sysNodos = SysNodo::whereIn('idNodo', $idNodos)->get();
    //     $updatedRecords = [];

    //     foreach ($sysNodos as $sysNodo) {
    //         // echo "------" . PHP_EOL;
    //         // echo "Nodo: {$sysNodo->idNodo}" . PHP_EOL;
    //         // echo "urlWs original: {$sysNodo->urlWs}" . PHP_EOL;

    //         $url = rtrim($sysNodo->urlWs, '/') . "/sync.php";
    //         // echo "URL construida: $url" . PHP_EOL;

    //         try {
    //             $response = Http::get($url);
    //         } catch (\Exception $e) {
    //             // echo "Error en la solicitud HTTP: " . $e->getMessage() . PHP_EOL;
    //             continue;
    //         }

    //         if ($response->successful()) {
    //             // echo "Respuesta recibida correctamente." . PHP_EOL;
    //             $data = $response->json();

    //             DB::statement('SET @DISABLE_TRIGGER = 1;');

    //             foreach ($data['data'] as $item) {
    //                 foreach ($item as $key => $value) {
    //                     if (Str::startsWith($key, 'fecha')) {
    //                         $item[$key] = $this->limpiarFecha($value);
    //                     }
    //                 }
    //                 if ($item['flgStatus'] === 'C') {
    //                     // Si está en "C" (crítico o cerrado)
    //                     if (isset($item['flgCondicionSolucionado']) && $item['flgCondicionSolucionado'] === '1') {
    //                         // Mantiene el valor actual, no se cambia
    //                         $flgSolucionado = isset($item['flgSolucionado']) ? (string)$item['flgSolucionado'] : '0';
    //                     } else {
    //                         // Caso normal: se fuerza a 0
    //                         $flgSolucionado = '0';
    //                     }
    //                 } else {
    //                     // Si no está en "C", se conserva la lógica normal
    //                     if (isset($item['flgSolucionado']) && $item['flgSolucionado'] !== '') {
    //                         $flgSolucionado = (string)$item['flgSolucionado'];
    //                     } else {
    //                         $flgSolucionado = '0';
    //                     }
    //                 }


    //                 DB::table('monMonitoreo')->updateOrInsert(
    //                     ['idMonitoreo' => $item['idMonitoreo']],
    //                     [
    //                         'idNodoPerspectiva'        => $item['idNodoPerspectiva'],
    //                         'flgStatus'                => $item['flgStatus'],
    //                         'flgEstado'                => $item['flgEstado'],
    //                         'flgSolucionado'           => $flgSolucionado,
    //                         'fechaUltimaVerificacion' => $item['fechaUltimaVerificacion'] ?? now(),
    //                         'fechaUltimoCambio' => $item['fechaUltimoCambio'] ?? now(),

    //                         'flgSyncHijo'              => '1',
    //                     ]
    //                 );

    //                 $updatedRecords[] = [
    //                     "idNodo"            => $sysNodo->idNodo,
    //                     "idMonitoreo"       => $item['idMonitoreo'],
    //                     "idNodoPerspectiva" => $item['idNodoPerspectiva'],
    //                     "flgStatus"         => $item['flgStatus'],
    //                     'fechaUltimaVerificacion' => $item['fechaUltimaVerificacion'],
    //                     'fechaUltimoCambio'       => $item['fechaUltimoCambio'],
    //                     'flgSolucionado'           => $item['flgSolucionado'],
    //                     "flgEstado"         => $item['flgEstado']
    //                 ];
    //             }

    //             DB::statement('SET @DISABLE_TRIGGER = NULL;');
    //         } else {
    //             // echo "Fallo al obtener datos de $url - Código HTTP: " . $response->status() . PHP_EOL;
    //             return response()->json([
    //                 "status"  => "error",
    //                 "message" => "No se pudo obtener los datos de $url"
    //             ], 500);
    //         }
    //     }

    //     // echo "------" . PHP_EOL;
    //     // echo "Total de registros actualizados: " . count($updatedRecords) . PHP_EOL;

    //     return response()->json([
    //         "status"          => "success",
    //         "message"         => "Datos sincronizados correctamente.",
    //         "updated_records" => $updatedRecords,
    //     ]);
    // }

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
                $response = Http::get($url);
            } catch (\Exception $e) {
                continue;
            }

            if ($response->successful()) {
                $data = $response->json();

                DB::statement('SET @DISABLE_TRIGGER = 1;');

                foreach ($data['data'] as $item) {
                    // Limpiar fechas
                    foreach ($item as $key => $value) {
                        if (Str::startsWith($key, 'fecha')) {
                            $item[$key] = $this->limpiarFecha($value);
                        }
                    }

                    // Traer registro actual del padre
                    $registroPadre = DB::table('monMonitoreo')
                        ->where('idMonitoreo', $item['idMonitoreo'])
                        ->first();

                    $fechaHijo = isset($item['fechaSyncHijo']) ? \Carbon\Carbon::parse($item['fechaSyncHijo']) : now();
                    $fechaPadre = isset($registroPadre->fechaSyncPadre) ? \Carbon\Carbon::parse($registroPadre->fechaSyncPadre) : now();

                    // Lógica flgSolucionado
                    if ($fechaHijo->gt($fechaPadre)) {
                        // Si el registro hijo es más reciente
                        if ($item['flgStatus'] === 'C' && (!isset($item['flgCondicionSolucionado']) || $item['flgCondicionSolucionado'] !== '1')) {
                            // Solo en crítico y si no hay condición de solucionado, forzar a 0
                            $flgSolucionado = '0';
                        } else {
                            // Mantener el valor actual del hijo si ya estaba en 1 o no es crítico
                            $flgSolucionado = isset($item['flgSolucionado']) && $item['flgSolucionado'] !== ''
                                ? $item['flgSolucionado']
                                : '0';
                        }
                    } else {
                        // Padre tiene datos más recientes, mantener valor actual
                        $flgSolucionado = isset($registroPadre->flgSolucionado) && $registroPadre->flgSolucionado !== ''
                            ? $registroPadre->flgSolucionado
                            : '0';
                    }

                    // Forzar que sea siempre '0' o '1'
                    $flgSolucionado = ($flgSolucionado === '1') ? '1' : '0';

                    // Actualizar o insertar registro
                    DB::table('monMonitoreo')->updateOrInsert(
                        ['idMonitoreo' => $item['idMonitoreo']],
                        [
                            'idNodoPerspectiva'       => $item['idNodoPerspectiva'],
                            'flgStatus'               => $item['flgStatus'],
                            'flgEstado'               => $item['flgEstado'],
                            'flgSolucionado'          => $flgSolucionado,
                            'fechaUltimaVerificacion' => $item['fechaUltimaVerificacion'] ?? now(),
                            'fechaUltimoCambio'       => $item['fechaUltimoCambio'] ?? now(),
                            'fechaSyncPadre'          => now(),
                            'flgSyncHijo'             => '1',
                            'fechaSyncHijo'               => $item['fechaSyncHijo'],
                        ]
                    );

                    $updatedRecords[] = [
                        "idNodo" => $sysNodo->idNodo,
                        "idMonitoreo" => $item['idMonitoreo'],
                        "flgStatus" => $item['flgStatus'],
                        "flgEstado" => $item['flgEstado'],
                        "flgSolucionado" => $flgSolucionado,
                        'fechaSyncHijo' => $item['fechaSyncHijo'],
                        "fechaUltimaVerificacion" => $item['fechaUltimaVerificacion'],
                        "fechaUltimoCambio" => $item['fechaUltimoCambio']
                    ];
                }

                DB::statement('SET @DISABLE_TRIGGER = NULL;');
            } else {
                return response()->json([
                    "status" => "error",
                    "message" => "No se pudo obtener los datos de $url"
                ], 500);
            }
        }

        return response()->json([
            "status" => "success",
            "message" => "Datos sincronizados correctamente.",
            "updated_records" => $updatedRecords,
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
        if (
            $valor === '0000-00-00 00:00:00' ||
            $valor === '0' ||
            $valor === 0 ||
            $valor === null ||
            $valor === ''
        ) {
            return null;
        }
        return $valor;
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
