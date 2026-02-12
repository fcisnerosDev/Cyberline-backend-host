<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Frecuencia;
use App\Models\Ip;
use App\Models\Maestro;
use App\Models\SysIpsecNode;
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

    private function limpiarIntNullable($v)
    {
        return ($v !== null && $v !== '' && is_numeric($v)) ? (int) $v : null;
    }

    private function limpiarInt($v)
    {
        return ($v !== null && $v !== '' && is_numeric($v)) ? (int) $v : 0;
    }

    private function limpiarStringNullable($v)
    {
        return ($v !== null && $v !== '') ? $v : null;
    }

    private function limpiarString($v)
    {
        return ($v !== null) ? (string) $v : '';
    }


    public function UpdateMonitoreoData($idNodo = null)
    {
        $idNodos = $this->getValidNodoIdForCybernetPrimary($idNodo);

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

                // LIMPIEZA DE FECHAS INVÁLIDAS
                foreach ($item as $key => $value) {
                    if (Str::startsWith($key, 'fecha')) {
                        $item[$key] = $this->limpiarFecha($value);
                    }
                }

                // BUSCAR REGISTRO EXISTENTE
                $registroPadre = DB::table('monMonitoreo')
                    ->where('idMonitoreo', $item['idMonitoreo'])
                    ->orWhere(function ($q) use ($item) {
                        $q->where('idServicio', $item['idServicio'] ?? null)
                            ->where('idEquipo', $item['idEquipo'] ?? null)
                            ->where('idTipoServicio', $item['idTipoServicio'] ?? null)
                            ->where('idIp', $item['idIp'] ?? null);
                    })
                    ->first();

                // MANTENER flgSolucionado = 1 SI YA ESTABA RESUELTO
                $flgSolucionado = $this->limpiarFlg($item['flgSolucionado'] ?? 0);
                if ($registroPadre && $registroPadre->flgSolucionado === 1) {
                    $flgSolucionado = 1;
                }

                // IGNORAR CAMPOS VACÍOS O NULL PARA NO SOBRESCRIBIR
                if ($registroPadre) {
                    foreach ($item as $k => $v) {
                        if ($v === null || $v === '' || $v === '0000-00-00 00:00:00') {
                            unset($item[$k]);
                        }
                    }
                }

                // MANEJO DE FECHAS DE SINCRONIZACIÓN
                $fechaSyncHijo = $this->limpiarFecha($item['fechaSyncHijo'] ?? null)
                    ? \Carbon\Carbon::parse($item['fechaSyncHijo'])
                    : now();
                $fechaSyncPadre = now();

                if ($fechaSyncPadre->lessThanOrEqualTo($fechaSyncHijo)) {
                    $fechaSyncPadre = $fechaSyncHijo->copy()->addSeconds(2);
                }

                // DATOS PREPARADOS CON PREVENCIÓN DE NULOS
                $datos = [

                    // =============================
                    // IDS (NOT NULL NUMERICOS)
                    // =============================
                    'idNodoPerspectiva' => $this->limpiarString($item['idNodoPerspectiva'] ?? ''),
                    'idSync' => $this->limpiarIntNullable($item['idSync'] ?? null),
                    'idSyncNodo' => $this->limpiarStringNullable($item['idSyncNodo'] ?? null),

                    'idServicio' => $this->limpiarInt($item['idServicio'] ?? 0),
                    'idServicioNodo' => $this->limpiarString($item['idServicioNodo'] ?? ''),

                    'idEquipo' => $this->limpiarInt($item['idEquipo'] ?? 0),
                    'idEquipoNodo' => $this->limpiarString($item['idEquipoNodo'] ?? ''),

                    'idTipoServicio' => $this->limpiarInt($item['idTipoServicio'] ?? 0),
                    'idTipoServicioNodo' => $this->limpiarString($item['idTipoServicioNodo'] ?? ''),

                    'idIp' => $this->limpiarInt($item['idIp'] ?? 0),
                    'idIpNodo' => $this->limpiarString($item['idIpNodo'] ?? ''),

                    'idFrecuencia' => $this->limpiarInt($item['idFrecuencia'] ?? 0),
                    'idFrecuenciaNodo' => $this->limpiarString($item['idFrecuenciaNodo'] ?? ''),

                    'idUsuario' => $this->limpiarInt($item['idUsuario'] ?? 0),
                    'idUsuarioNodo' => $this->limpiarString($item['idUsuarioNodo'] ?? ''),

                    // =============================
                    // TEXTOS
                    // =============================
                    'dscMonitoreo' => $this->limpiarString($item['dscMonitoreo'] ?? ''),
                    'etiqueta' => $this->limpiarString($item['etiqueta'] ?? ''),
                    'numReintentos' => $this->limpiarInt($item['numReintentos'] ?? 0),

                    'paramametroScript' => $this->limpiarStringNullable($item['paramametroScript'] ?? null),

                    // =============================
                    // PARAMETROS (según tu tabla)
                    // =============================

                    // INT NULLABLE
                    'paramNumPort' => $this->limpiarIntNullable($item['paramNumPort'] ?? null),

                    // VARCHAR en BD → NO CASTEAR A INT
                    'paramNumPackets' => $this->limpiarStringNullable($item['paramNumPackets'] ?? null),
                    'paramTimeout' => $this->limpiarStringNullable($item['paramTimeout'] ?? null),
                    'paramWarningUmbral' => $this->limpiarStringNullable($item['paramWarningUmbral'] ?? null),
                    'paramCriticalUmbral' => $this->limpiarStringNullable($item['paramCriticalUmbral'] ?? null),

                    'anotacion' => $this->limpiarStringNullable($item['anotacion'] ?? null),
                    'cuentasNotificacion' => $this->limpiarStringNullable($item['cuentasNotificacion'] ?? null),

                    'intervaloNotificacion' => $this->limpiarInt($item['intervaloNotificacion'] ?? 0),

                    // =============================
                    // FECHAS
                    // =============================
                    'fechaUltimaVerificacion' => $item['fechaUltimaVerificacion'] ?? null,
                    'fechaUltimoCambio' => $item['fechaUltimoCambio'] ?? null,
                    'fechaUltimaNotificacion' => $item['fechaUltimaNotificacion'] ?? null,
                    'fechaActivacion' => $item['fechaActivacion'] ?? null,
                    'fechaDesactivacion' => $item['fechaDesactivacion'] ?? null,
                    'fechaActivacionAutomatica' => $item['fechaActivacionAutomatica'] ?? null,
                    'fechaModificacion' => $item['fechaModificacion'] ?? null,
                    'fechaModificacionStatus' => $item['fechaModificacionStatus'] ?? null,
                    'fechaCreacion' => $item['fechaCreacion'] ?? now(),
                    'fechaRegistro' => $item['fechaRegistro'] ?? now(),
                    'fechaSyncHijo' => $fechaSyncHijo,
                    'fechaSyncPadre' => $fechaSyncPadre,

                    // =============================
                    // FLAGS
                    // =============================
                    'flgMonitoreoIp' => $this->limpiarFlg($item['flgMonitoreoIp'] ?? 0),
                    'flgRevision' => $this->limpiarFlg($item['flgRevision'] ?? 0),
                    'flgStatus' => $item['flgStatus'] ?? 'O', // ENUM especial
                    'flgStatusControl' => $this->limpiarInt($item['flgStatusControl'] ?? 0),
                    'flgCondicionSolucionado' => $this->limpiarFlg($item['flgCondicionSolucionado'] ?? 0),
                    'flgOcultarMonitoreo' => $this->limpiarFlg($item['flgOcultarMonitoreo'] ?? 0),
                    'flgSonido' => $this->limpiarFlg($item['flgSonido'] ?? 0),
                    'flgSolucionado' => $flgSolucionado,
                    'flgEstado' => $item['flgEstado'] ?? '0',
                    'flgActivacionAutomatica' => $this->limpiarFlg($item['flgActivacionAutomatica'] ?? 0),
                    'flgSync' => $this->limpiarFlg($item['flgSync'] ?? 0),
                    'flgSyncHijo' => '1',
                    'flgSyncPadre' => '1',

                    // =============================
                    // OTROS
                    // =============================
                    'temporal' => $this->limpiarStringNullable($item['temporal'] ?? null),

                    'cantidad_alertas' => $this->limpiarInt($item['cantidad_alertas'] ?? 0),
                    'porcentaje_alertas' => $this->limpiarInt($item['porcentaje_alertas'] ?? 0),
                ];

                if ($registroPadre) {
                    DB::table('monMonitoreo')->where('idMonitoreo', $registroPadre->idMonitoreo)->update($datos);
                } else {
                    DB::table('monMonitoreo')->insert(array_merge(['idMonitoreo' => $item['idMonitoreo']], $datos));
                }

                $updatedRecords[] = [
                    "idNodo" => $sysNodo->idNodo,
                    "idMonitoreo" => $item['idMonitoreo'],
                    "flgSolucionado" => $flgSolucionado,
                    "fechaSyncHijo" => $fechaSyncHijo->toDateTimeString(),
                    "fechaSyncPadre" => $fechaSyncPadre->toDateTimeString(),
                ];
            }
        }

        return response()->json([
            "status" => "success",
            "updated" => $updatedRecords
        ]);
    }





    private function limpiarFlg($valor)
    {
        // Si viene null, vacío o no numérico → '0'
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return '0';
        }

        // Convertir a entero
        $valor = (int) $valor;

        // Solo permitir '1' o '0' como STRING
        return ($valor === 1) ? '1' : '0';
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
                'idMonitoreo' => $monitoreo['idMonitoreo'],
                'idMonitoreoNodo' => $monitoreo['idMonitoreoNodo'],
                'idNodoPerspectiva' => $monitoreo['idNodoPerspectiva'],
                'idSync' => $monitoreo['idSync'],
                'idSyncNodo' => $monitoreo['idSyncNodo'],
                'idServicio' => $monitoreo['idServicio'],
                'idServicioNodo' => $monitoreo['idServicioNodo'],
                'idEquipo' => $monitoreo['idEquipo'],
                'idEquipoNodo' => $monitoreo['idEquipoNodo'],
                'idTipoServicio' => $monitoreo['idTipoServicio'],
                'idTipoServicioNodo' => $monitoreo['idTipoServicioNodo'],
                'idIp' => $monitoreo['idIp'],
                'idIpNodo' => $monitoreo['idIpNodo'],
                'idFrecuencia' => $monitoreo['idFrecuencia'],
                'idFrecuenciaNodo' => $monitoreo['idFrecuenciaNodo'],
                'idUsuario' => $monitoreo['idUsuario'],
                'idUsuarioNodo' => $monitoreo['idUsuarioNodo'],
                'dscMonitoreo' => $monitoreo['dscMonitoreo'],
                'etiqueta' => $monitoreo['etiqueta'],
                'numReintentos' => $monitoreo['numReintentos'],
                'paramametroScript' => $monitoreo['paramametroScript'],
                'flgMonitoreoIp' => $monitoreo['flgMonitoreoIp'],
                'paramNumPort' => $monitoreo['paramNumPort'],
                'paramNumPackets' => $monitoreo['paramNumPackets'],
                'paramTimeout' => $monitoreo['paramTimeout'],
                'paramWarningUmbral' => $monitoreo['paramWarningUmbral'],
                'paramCriticalUmbral' => $monitoreo['paramCriticalUmbral'],
                'flgRevision' => $monitoreo['flgRevision'],
                'anotacion' => $monitoreo['anotacion'],
                'cuentasNotificacion' => $monitoreo['cuentasNotificacion'],
                'intervaloNotificacion' => $monitoreo['intervaloNotificacion'],
                'fechaUltimaVerificacion' => $monitoreo['fechaUltimaVerificacion'],
                'fechaUltimoCambio' => $monitoreo['fechaUltimoCambio'],
                'fechaUltimaNotificacion' => $monitoreo['fechaUltimaNotificacion'],
                'fechaActivacion' => $monitoreo['fechaActivacion'],
                'fechaDesactivacion' => $monitoreo['fechaDesactivacion'],
                'flgStatus' => $monitoreo['flgStatus'],
                'flgStatusControl' => $monitoreo['flgStatusControl'],
                'flgCondicionSolucionado' => $monitoreo['flgCondicionSolucionado'],
                'flgOcultarMonitoreo' => $monitoreo['flgOcultarMonitoreo'],
                'flgSonido' => $monitoreo['flgSonido'],
                'flgSolucionado' => $monitoreo['flgSolucionado'],
                'flgEstado' => $monitoreo['flgEstado'],
                'flgActivacionAutomatica' => $monitoreo['flgActivacionAutomatica'],
                'fechaActivacionAutomatica' => $monitoreo['fechaActivacionAutomatica'],
                'fechaModificacion' => $monitoreo['fechaModificacion'],
                'fechaModificacionStatus' => $monitoreo['fechaModificacionStatus'],
                'fechaCreacion' => $monitoreo['fechaCreacion'],
                'fechaRegistro' => $monitoreo['fechaRegistro'],
                'flgSync' => $monitoreo['flgSync'],
                'flgSyncHijo' => $monitoreo['flgSyncHijo'],
                'flgSyncPadre' => $monitoreo['flgSyncPadre'],
                'fechaSyncHijo' => $monitoreo['fechaSyncHijo'],
                'fechaSyncPadre' => $monitoreo['fechaSyncPadre'],
                'temporal' => $monitoreo['temporal'],
                'cantidad_alertas' => $monitoreo['cantidad_alertas'],
                'porcentaje_alertas' => $monitoreo['porcentaje_alertas'],
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
                'idServicio' => $servicio['idServicio'],
                'idServicioNodo' => $servicio['idServicioNodo'],
                'idNodoPerspectiva' => $servicio['idNodoPerspectiva'],
                'idSync' => $servicio['idSync'] ?? 0,
                'IdNodoSync' => $servicio['IdNodoSync'] ?? '',
                'idEquipo' => $servicio['idEquipo'],
                'idEquipoNodo' => $servicio['idEquipoNodo'],
                'idTipoServicio' => $servicio['idTipoServicio'],
                'idTipoServicioNodo' => $servicio['idTipoServicioNodo'],
                'idIp' => $servicio['idIp'],
                'idIpNodo' => $servicio['idIpNodo'],
                'puerto' => $servicio['puerto'],
                'fechaInicio' => $servicio['fechaInicio'],
                'fechaTermino' => !empty($servicio['fechaTermino']) ? $servicio['fechaTermino'] : now(),
                'flgEstado' => $servicio['flgEstado'],
                'fechaCreacion' => $servicio['fechaCreacion'],
                'fechaRegistro' => $servicio['fechaRegistro'],
                'fechaModificacion' => $servicio['fechaModificacion'],
                'flgSync' => $servicio['flgSync'],
                'flgSyncHijo' => $servicio['flgSyncHijo'],
                'flgSyncPadre' => $servicio['flgSyncPadre'],
                'fechaSyncHijo' => $servicio['fechaSyncHijo'],
                'fechaSyncPadre' => $servicio['fechaSyncPadre'],
                'temporal' => $servicio['temporal'],
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
    public function updateMonitoreoDataIpsec($idNodo = null)
    {
        // Traer solo nodos activos (flgEstado = 1) y listos para sincronizar
        $query = SysIpsecNode::where('flgEstado', '1')->listosParaSync();

        if ($idNodo) {
            $query->where('idNodo', $idNodo);
        }

        $sysNodos = $query->get();

        if ($sysNodos->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "No se encontraron nodos activos para la sincronización."
            ], 400);
        }

        $updatedRecords = [];

        foreach ($sysNodos as $nodo) {
            $url = rtrim($nodo->getBaseUrlAttribute(), '/') . '/sync.php';

            try {
                $response = Http::timeout($nodo->timeout ?? 15)->get($url);
            } catch (\Exception $e) {
                // Nodo temporalmente desconectado
                $nodo->update([
                    'flgConexion' => '0',
                    'fechaConexion' => now(),
                    'mensajeMonitoreo' => $e->getMessage(),
                    'fechaVerificacionMonitoreo' => now(),
                ]);
                echo "Error HTTP en {$nodo->idNodo}: " . $e->getMessage() . PHP_EOL;
                continue;
            }

            if (!$response->successful()) {
                $nodo->update([
                    'flgConexion' => '0',
                    'fechaConexion' => now(),
                    'mensajeMonitoreo' => "HTTP Error: " . $response->status(),
                    'fechaVerificacionMonitoreo' => now(),
                ]);
                echo "Fallo al obtener datos de $url - Código: " . $response->status() . PHP_EOL;
                continue;
            }

            $data = $response->json();

            // Nodo conectado correctamente
            $nodo->update([
                'flgConexion' => '1',
                'fechaConexion' => now(),
                'mensajeMonitoreo' => 'Conectado correctamente',
                'fechaVerificacionMonitoreo' => now(),
                'flgSyncHijo' => '1'
            ]);

            DB::statement('SET @DISABLE_TRIGGER = 1;');

            foreach ($data['data'] as $item) {
                // Limpieza de fechas
                foreach ($item as $key => $value) {
                    if (Str::startsWith($key, 'fecha')) {
                        $item[$key] = $this->limpiarFecha($value);
                    }
                }

                // Buscar registro existente usando PK compuesta
                $registroPadre = DB::table('monMonitoreo')
                    ->where('idMonitoreo', $item['idMonitoreo'])
                    ->where('idMonitoreoNodo', $item['idMonitoreoNodo'])
                    ->first();

                if (!$registroPadre) {
                    echo "Registro {$item['idMonitoreo']} - {$item['idMonitoreoNodo']} no existe, se omite.\n";
                    continue; // No se crea registro nuevo
                }

                // Mantener flgSolucionado si ya estaba
                $flgSolucionado = $this->limpiarFlg($item['flgSolucionado'] ?? 0);
                if ($registroPadre->flgSolucionado === 1) {
                    $flgSolucionado = 1;
                }

                // Manejo de fechas de sincronización
                $fechaSyncHijo = $this->limpiarFecha($item['fechaSyncHijo'] ?? null)
                    ? \Carbon\Carbon::parse($item['fechaSyncHijo'])
                    : now();
                $fechaSyncPadre = now();
                if ($fechaSyncPadre->lessThanOrEqualTo($fechaSyncHijo)) {
                    $fechaSyncPadre = $fechaSyncHijo->copy()->addSeconds(2);
                }

                // Datos preparados con prevención de nulos
                $datos = [
                    'idNodoPerspectiva' => $this->limpiarString($item['idNodoPerspectiva'] ?? ''),
                    'idSync' => $this->limpiarIntNullable($item['idSync'] ?? null),
                    'idSyncNodo' => $this->limpiarStringNullable($item['idSyncNodo'] ?? null),
                    'idServicio' => $this->limpiarInt($item['idServicio'] ?? 0),
                    'idServicioNodo' => $this->limpiarString($item['idServicioNodo'] ?? ''),
                    'idEquipo' => $this->limpiarInt($item['idEquipo'] ?? 0),
                    'idEquipoNodo' => $this->limpiarString($item['idEquipoNodo'] ?? ''),
                    'idTipoServicio' => $this->limpiarInt($item['idTipoServicio'] ?? 0),
                    'idTipoServicioNodo' => $this->limpiarString($item['idTipoServicioNodo'] ?? ''),
                    'idIp' => $this->limpiarInt($item['idIp'] ?? 0),
                    'idIpNodo' => $this->limpiarString($item['idIpNodo'] ?? ''),
                    'idFrecuencia' => $this->limpiarInt($item['idFrecuencia'] ?? 0),
                    'idFrecuenciaNodo' => $this->limpiarString($item['idFrecuenciaNodo'] ?? ''),
                    'idUsuario' => $this->limpiarInt($item['idUsuario'] ?? 0),
                    'idUsuarioNodo' => $this->limpiarString($item['idUsuarioNodo'] ?? ''),
                    'dscMonitoreo' => $this->limpiarString($item['dscMonitoreo'] ?? ''),
                    'etiqueta' => $this->limpiarString($item['etiqueta'] ?? ''),
                    'numReintentos' => $this->limpiarInt($item['numReintentos'] ?? 0),
                    'paramametroScript' => $this->limpiarString($item['paramametroScript'] ?? ''),
                    'flgMonitoreoIp' => $this->limpiarString($item['flgMonitoreoIp'] ?? '0'),
                    'paramNumPort' => $this->limpiarIntNullable($item['paramNumPort'] ?? null),
                    'paramNumPackets' => $this->limpiarStringNullable($item['paramNumPackets'] ?? null),
                    'paramTimeout' => $this->limpiarStringNullable($item['paramTimeout'] ?? null),
                    'paramWarningUmbral' => $this->limpiarStringNullable($item['paramWarningUmbral'] ?? null),
                    'paramCriticalUmbral' => $this->limpiarStringNullable($item['paramCriticalUmbral'] ?? null),
                    'flgRevision' => $this->limpiarString($item['flgRevision'] ?? '0'),
                    'anotacion' => $this->limpiarString($item['anotacion'] ?? ''),
                    'cuentasNotificacion' => $this->limpiarString($item['cuentasNotificacion'] ?? ''),
                    'intervaloNotificacion' => $this->limpiarInt($item['intervaloNotificacion'] ?? 0),
                    'fechaUltimaVerificacion' => $this->limpiarFecha($item['fechaUltimaVerificacion'] ?? null),
                    'fechaUltimoCambio' => $this->limpiarFecha($item['fechaUltimoCambio'] ?? null),
                    'fechaUltimaNotificacion' => $this->limpiarFecha($item['fechaUltimaNotificacion'] ?? null),
                    'fechaActivacion' => $this->limpiarFecha($item['fechaActivacion'] ?? null),
                    'fechaDesactivacion' => $this->limpiarFecha($item['fechaDesactivacion'] ?? null),
                    'flgStatus' => $this->limpiarString($item['flgStatus'] ?? 'O'),
                    'flgCondicionSolucionado' => $this->limpiarFlg($item['flgCondicionSolucionado'] ?? 0),
                    'flgSonido' => $this->limpiarFlg($item['flgSonido'] ?? 0),
                    'flgSolucionado' => $flgSolucionado,
                    'flgEstado' => $this->limpiarString($item['flgEstado'] ?? '1'),
                    'flgActivacionAutomatica' => $this->limpiarString($item['flgActivacionAutomatica'] ?? '1'),
                    'fechaActivacionAutomatica' => $this->limpiarFecha($item['fechaActivacionAutomatica'] ?? null),
                    'fechaModificacion' => $this->limpiarFecha($item['fechaModificacion'] ?? null),
                    'fechaModificacionStatus' => $this->limpiarFecha($item['fechaModificacionStatus'] ?? null),
                    'fechaCreacion' => $this->limpiarFecha($item['fechaCreacion'] ?? null),
                    'fechaRegistro' => $this->limpiarFecha($item['fechaRegistro'] ?? null),
                    'flgSync' => $this->limpiarString($item['flgSync'] ?? '0'),
                    'flgSyncHijo' => $this->limpiarString($item['flgSyncHijo'] ?? '0'),
                    'flgSyncPadre' => $this->limpiarString($item['flgSyncPadre'] ?? '0'),
                    'fechaSyncHijo' => $fechaSyncHijo,
                    'fechaSyncPadre' => $fechaSyncPadre,
                    'temporal' => $this->limpiarString($item['temporal'] ?? null),
                    'cantidad_alertas' => $this->limpiarInt($item['cantidad_alertas'] ?? 0),
                    'porcentaje_alertas' => $this->limpiarInt($item['porcentaje_alertas'] ?? 0),
                    'codigoGrupo' => $this->limpiarString($item['codigoGrupo'] ?? ''),
                ];

                // Actualizar solo si existe
                DB::table('monMonitoreo')
                    ->where('idMonitoreo', $registroPadre->idMonitoreo)
                    ->where('idMonitoreoNodo', $registroPadre->idMonitoreoNodo)
                    ->update($datos);

                $updatedRecords[] = [
                    "idNodo" => $nodo->idNodo,
                    "idMonitoreo" => $item['idMonitoreo'],
                    "flgSolucionado" => $flgSolucionado,
                     "flgStatus" => $item['flgStatus'],

                    "fechaSyncHijo" => $fechaSyncHijo->toDateTimeString(),
                    "fechaSyncPadre" => $fechaSyncPadre->toDateTimeString(),
                ];
            }
        }

        return response()->json([
            "status" => "success",
            "updated" => $updatedRecords
        ]);
    }



}
